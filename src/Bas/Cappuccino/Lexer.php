<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Error\SyntaxError;
use LogicException;

/**
 * Class Lexer
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 1.0.0
 */
class Lexer
{

	public const STATE_DATA = 0;
	public const STATE_BLOCK = 1;
	public const STATE_VAR = 2;
	public const STATE_STRING = 3;
	public const STATE_INTERPOLATION = 4;
	public const REGEX_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A';
	public const REGEX_NUMBER = '/[0-9]+(?:\.[0-9]+)?/A';
	public const REGEX_STRING = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
	public const REGEX_DQ_STRING_DELIM = '/"/A';
	public const REGEX_DQ_STRING_PART = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
	public const PUNCTUATION = '()[]{}?:.,|';

	private $tokens;
	private $code;
	private $cursor;
	private $lineno;
	private $end;
	private $state;
	private $states;
	private $brackets;
	private $env;
	private $source;
	private $options;
	private $regexes;
	private $position;
	private $positions;
	private $currentVarBlockLine;

	/**
	 * Lexer constructor.
	 *
	 * @param Cappuccino $env
	 * @param array      $options
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Cappuccino $env, array $options = [])
	{
		$this->env = $env;
		$this->options = array_merge([
			'tag_comment' => ['{#', '#}'],
			'tag_block' => ['{%', '%}'],
			'tag_variable' => ['{{', '}}'],
			'whitespace_trim' => '-',
			'interpolation' => ['#{', '}'],
		], $options);
		$this->regexes = [
			'lex_var' => '/\s*' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_variable'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_variable'][1], '/') . '/A',
			'lex_block' => '/\s*(?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_block'][1], '/') . ')\n?/A',
			'lex_raw_data' => '/(' . preg_quote($this->options['tag_block'][0] . $this->options['whitespace_trim'], '/') . '|' . preg_quote($this->options['tag_block'][0], '/') . ')\s*(?:endverbatim)\s*(?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_block'][1], '/') . ')/s',
			'operator' => $this->getOperatorRegex(),
			'lex_comment' => '/(?:' . preg_quote($this->options['whitespace_trim'], '/') . preg_quote($this->options['tag_comment'][1], '/') . '\s*|' . preg_quote($this->options['tag_comment'][1], '/') . ')\n?/s',
			'lex_block_raw' => '/\s*verbatim\s*(?:' . preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '/') . '\s*|\s*' . preg_quote($this->options['tag_block'][1], '/') . ')/As',
			'lex_block_line' => '/\s*line\s+(\d+)\s*' . preg_quote($this->options['tag_block'][1], '/') . '/As',
			'lex_tokens_start' => '/(' . preg_quote($this->options['tag_variable'][0], '/') . '|' . preg_quote($this->options['tag_block'][0], '/') . '|' . preg_quote($this->options['tag_comment'][0], '/') . ')(' . preg_quote($this->options['whitespace_trim'], '/') . ')?/s',
			'interpolation_start' => '/' . preg_quote($this->options['interpolation'][0], '/') . '\s*/A',
			'interpolation_end' => '/\s*' . preg_quote($this->options['interpolation'][1], '/') . '/A',
		];
	}

	/**
	 * @param Source $source
	 *
	 * @return TokenStream
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function tokenize (Source $source) : TokenStream
	{
		$this->source = $source;
		$this->code = str_replace(["\r\n", "\r"], "\n", $source->getCode());
		$this->cursor = 0;
		$this->lineno = 1;
		$this->end = strlen($this->code);
		$this->tokens = [];
		$this->state = self::STATE_DATA;
		$this->states = [];
		$this->brackets = [];
		$this->position = -1;

		preg_match_all($this->regexes['lex_tokens_start'], $this->code, $matches, PREG_OFFSET_CAPTURE);
		$this->positions = $matches;

		while ($this->cursor < $this->end)
		{
			// dispatch to the lexing functions depending
			// on the current state
			switch ($this->state)
			{
				case self::STATE_DATA:
					$this->lexData();
					break;
				case self::STATE_BLOCK:
					$this->lexBlock();
					break;
				case self::STATE_VAR:
					$this->lexVar();
					break;
				case self::STATE_STRING:
					$this->lexString();
					break;
				case self::STATE_INTERPOLATION:
					$this->lexInterpolation();
					break;
			}
		}

		$this->pushToken(Token::EOF_TYPE);

		if (!empty($this->brackets))
		{
			[$expect, $lineno] = array_pop($this->brackets);

			throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
		}

		return new TokenStream($this->tokens, $this->source);
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexData () : void
	{
		if ($this->position == count($this->positions[0]) - 1)
		{
			$this->pushToken(Token::TEXT_TYPE, substr($this->code, $this->cursor));
			$this->cursor = $this->end;

			return;
		}

		$position = $this->positions[0][++$this->position];

		while ($position[1] < $this->cursor)
		{
			if ($this->position == count($this->positions[0]) - 1)
				return;

			$position = $this->positions[0][++$this->position];
		}

		$text = $textContent = substr($this->code, $this->cursor, $position[1] - $this->cursor);

		if (isset($this->positions[2][$this->position][0]))
			$text = rtrim($text);

		$this->pushToken(Token::TEXT_TYPE, $text);
		$this->moveCursor($textContent . $position[0]);

		switch ($this->positions[1][$this->position][0])
		{
			case $this->options['tag_comment'][0]:
				$this->lexComment();
				break;

			case $this->options['tag_block'][0]:
				if (preg_match($this->regexes['lex_block_raw'], $this->code, $match, 0, $this->cursor))
				{
					$this->moveCursor($match[0]);
					$this->lexRawData();
				}
				else if (preg_match($this->regexes['lex_block_line'], $this->code, $match, 0, $this->cursor))
				{
					$this->moveCursor($match[0]);
					$this->lineno = (int)$match[1];
				}
				else
				{
					$this->pushToken(Token::BLOCK_START_TYPE);
					$this->pushState(self::STATE_BLOCK);
					$this->currentVarBlockLine = $this->lineno;
				}
				break;

			case $this->options['tag_variable'][0]:
				$this->pushToken(Token::VAR_START_TYPE);
				$this->pushState(self::STATE_VAR);
				$this->currentVarBlockLine = $this->lineno;
				break;
		}
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexBlock () : void
	{
		if (empty($this->brackets) && preg_match($this->regexes['lex_block'], $this->code, $match, 0, $this->cursor))
		{
			$this->pushToken(Token::BLOCK_END_TYPE);
			$this->moveCursor($match[0]);
			$this->popState();
		}
		else
		{
			$this->lexExpression();
		}
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexVar () : void
	{
		if (empty($this->brackets) && preg_match($this->regexes['lex_var'], $this->code, $match, 0, $this->cursor))
		{
			$this->pushToken(Token::VAR_END_TYPE);
			$this->moveCursor($match[0]);
			$this->popState();
		}
		else
		{
			$this->lexExpression();
		}
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexExpression () : void
	{
		if (preg_match('/\s+/A', $this->code, $match, 0, $this->cursor))
		{
			$this->moveCursor($match[0]);

			if ($this->cursor >= $this->end)
				throw new SyntaxError(sprintf('Unclosed "%s".', $this->state === self::STATE_BLOCK ? 'block' : 'variable'), $this->currentVarBlockLine, $this->source);
		}
		if (preg_match($this->regexes['operator'], $this->code, $match, 0, $this->cursor))
		{
			$this->pushToken(Token::OPERATOR_TYPE, preg_replace('/\s+/', ' ', $match[0]));
			$this->moveCursor($match[0]);
		}
		else if (preg_match(self::REGEX_NAME, $this->code, $match, 0, $this->cursor))
		{
			$this->pushToken(Token::NAME_TYPE, $match[0]);
			$this->moveCursor($match[0]);
		}
		else if (preg_match(self::REGEX_NUMBER, $this->code, $match, 0, $this->cursor))
		{
			$number = (float)$match[0];  // floats
			if (ctype_digit($match[0]) && $number <= PHP_INT_MAX)
			{
				$number = (int)$match[0]; // integers lower than the maximum
			}
			$this->pushToken(Token::NUMBER_TYPE, strval($number));
			$this->moveCursor($match[0]);
		}
		else if (false !== strpos(self::PUNCTUATION, $this->code[$this->cursor]))
		{
			if (false !== strpos('([{', $this->code[$this->cursor]))
			{
				$this->brackets[] = [$this->code[$this->cursor], $this->lineno];
			}
			else if (false !== strpos(')]}', $this->code[$this->cursor]))
			{
				if (empty($this->brackets))
				{
					throw new SyntaxError(sprintf('Unexpected "%s".', $this->code[$this->cursor]), $this->lineno, $this->source);
				}

				[$expect, $lineno] = array_pop($this->brackets);

				if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}'))
				{
					throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $lineno, $this->source);
				}
			}
			$this->pushToken(Token::PUNCTUATION_TYPE, $this->code[$this->cursor]);
			++$this->cursor;
		}
		else if (preg_match(self::REGEX_STRING, $this->code, $match, 0, $this->cursor))
		{
			$this->pushToken(Token::STRING_TYPE, stripcslashes(substr($match[0], 1, -1)));
			$this->moveCursor($match[0]);
		}
		else if (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, 0, $this->cursor))
		{
			$this->brackets[] = ['"', $this->lineno];
			$this->pushState(self::STATE_STRING);
			$this->moveCursor($match[0]);
		}
		else
		{
			throw new SyntaxError(sprintf('Unexpected character "%s".', $this->code[$this->cursor]), $this->lineno, $this->source);
		}
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexRawData () : void
	{
		if (!preg_match($this->regexes['lex_raw_data'], $this->code, $match, PREG_OFFSET_CAPTURE, $this->cursor))
			throw new SyntaxError('Unexpected end of file: Unclosed "verbatim" block.', $this->lineno, $this->source);

		$text = substr($this->code, $this->cursor, $match[0][1] - $this->cursor);
		$this->moveCursor($text . $match[0][0]);

		if (false !== strpos($match[1][0], $this->options['whitespace_trim']))
			$text = rtrim($text);

		$this->pushToken(Token::TEXT_TYPE, $text);
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexComment () : void
	{
		if (!preg_match($this->regexes['lex_comment'], $this->code, $match, PREG_OFFSET_CAPTURE, $this->cursor))
			throw new SyntaxError('Unclosed comment.', $this->lineno, $this->source);

		$this->moveCursor(substr($this->code, $this->cursor, $match[0][1] - $this->cursor) . $match[0][0]);
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexString () : void
	{
		if (preg_match($this->regexes['interpolation_start'], $this->code, $match, null, $this->cursor))
		{
			$this->brackets[] = [$this->options['interpolation'][0], $this->lineno];
			$this->pushToken(Token::INTERPOLATION_START_TYPE);
			$this->moveCursor($match[0]);
			$this->pushState(self::STATE_INTERPOLATION);
		}
		else if (preg_match(self::REGEX_DQ_STRING_PART, $this->code, $match, null, $this->cursor) && strlen($match[0]) > 0)
		{
			$this->pushToken(Token::STRING_TYPE, stripcslashes($match[0]));
			$this->moveCursor($match[0]);
		}
		else if (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor))
		{
			[$expect, $lineno] = array_pop($this->brackets);

			if ($this->code[$this->cursor] != '"')
				throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $lineno, $this->source);

			$this->popState();
			++$this->cursor;
		}
	}

	/**
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexInterpolation () : void
	{
		$bracket = end($this->brackets);

		if ($this->options['interpolation'][0] === $bracket[0] && preg_match($this->regexes['interpolation_end'], $this->code, $match, null, $this->cursor))
		{
			array_pop($this->brackets);
			$this->pushToken(Token::INTERPOLATION_END_TYPE);
			$this->moveCursor($match[0]);
			$this->popState();
		}
		else
		{
			$this->lexExpression();
		}
	}

	/**
	 * @param int    $type
	 * @param string $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function pushToken (int $type, string $value = '') : void
	{
		if (Token::TEXT_TYPE === $type && $value === '')
			return;

		$this->tokens[] = new Token($type, $value, $this->lineno);
	}

	/**
	 * @param $text
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function moveCursor ($text) : void
	{
		$this->cursor += strlen($text);
		$this->lineno += substr_count($text, "\n");
	}

	/**
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getOperatorRegex () : string
	{
		$operators = array_merge(
			['='],
			array_keys($this->env->getUnaryOperators()),
			array_keys($this->env->getBinaryOperators())
		);

		$operators = array_combine($operators, array_map('strlen', $operators));
		arsort($operators);
		$regex = [];

		foreach ($operators as $operator => $length)
		{
			if (ctype_alpha($operator[$length - 1]))
				$r = preg_quote($operator, '/') . '(?=[\s()])';
			else
				$r = preg_quote($operator, '/');

			$regex[] = preg_replace('/\s+/', '\s+', $r);
		}

		return '/' . implode('|', $regex) . '/A';
	}

	/**
	 * @param $state
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function pushState ($state) : void
	{
		$this->states[] = $this->state;
		$this->state = $state;
	}

	/**
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function popState () : void
	{
		if (0 === count($this->states))
			throw new LogicException('Cannot pop state without a previous state.');

		$this->state = array_pop($this->states);
	}

}
