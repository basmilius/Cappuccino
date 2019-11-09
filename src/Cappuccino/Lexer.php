<?php
/**
 * Copyright (c) 2017 - 2019 - Bas Milius <bas@mili.us>
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino;

use Cappuccino\Error\SyntaxError;
use LogicException;
use const PREG_OFFSET_CAPTURE;
use function array_combine;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function arsort;
use function count;
use function ctype_alpha;
use function ctype_digit;
use function end;
use function implode;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function sprintf;
use function str_replace;
use function stripcslashes;
use function strlen;
use function strpos;
use function strtr;
use function substr;
use function substr_count;

/**
 * Class Lexer
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino
 * @since 1.0.0
 */
class Lexer
{

	public const REGEX_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A';
	public const REGEX_NUMBER = '/[0-9]+(?:\.[0-9]+)?([Ee][\+\-][0-9]+)?/A';
	public const REGEX_STRING = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
	public const REGEX_DQ_STRING_DELIM = '/"/A';
	public const REGEX_DQ_STRING_PART = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
	public const PUNCTUATION = '()[]{}?:.,|';

	public const STATE_DATA = 0;
	public const STATE_BLOCK = 1;
	public const STATE_VAR = 2;
	public const STATE_STRING = 3;
	public const STATE_INTERPOLATION = 4;

	/**
	 * @var Token[]
	 */
	private $tokens;

	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var int
	 */
	private $cursor;

	/**
	 * @var int
	 */
	private $lineNumber;

	/**
	 * @var int
	 */
	private $end;

	/**
	 * @var int
	 */
	private $state;

	/**
	 * @var int[]
	 */
	private $states;

	/**
	 * @var array
	 */
	private $brackets;

	/**
	 * @var Cappuccino
	 */
	private $cappuccino;

	/**
	 * @var Source
	 */
	private $source;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var array
	 */
	private $regexes;

	/**
	 * @var int
	 */
	private $position;

	/**
	 * @var array
	 */
	private $positions;

	/**
	 * @var int
	 */
	private $currentVarBlockLine = 0;

	/**
	 * Lexer constructor.
	 *
	 * @param Cappuccino $cappuccino
	 * @param array      $options
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(Cappuccino $cappuccino, array $options = [])
	{
		$this->cappuccino = $cappuccino;

		$this->options = array_merge([
			'tag_comment' => ['{#', '#}'],
			'tag_block' => ['{%', '%}'],
			'tag_variable' => ['{{', '}}'],
			'whitespace_trim' => '-',
			'whitespace_line_trim' => '~',
			'whitespace_line_chars' => ' \t\0\x0B',
			'interpolation' => ['#{', '}'],
		], $options);

		// when PHP 7.3 is the min version, we will be able to remove the '#' part in preg_quote as it's part of the default
		$this->regexes = [
			// }}
			'lex_var' => '{
                \s*
                (?:' .
				preg_quote($this->options['whitespace_trim'] . $this->options['tag_variable'][1], '#') . '\s*' . // -}}\s*
				'|' .
				preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_variable'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . // ~}}[ \t\0\x0B]*
				'|' .
				preg_quote($this->options['tag_variable'][1], '#') . // }}
				')
            }Ax',

			// %}
			'lex_block' => '{
                \s*
                (?:' .
				preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '#') . '\s*\n?' . // -%}\s*\n?
				'|' .
				preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_block'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . // ~%}[ \t\0\x0B]*
				'|' .
				preg_quote($this->options['tag_block'][1], '#') . '\n?' . // %}\n?
				')
            }Ax',

			// {% endverbatim %}
			'lex_raw_data' => '{' .
				preg_quote($this->options['tag_block'][0], '#') . // {%
				'(' .
				$this->options['whitespace_trim'] . // -
				'|' .
				$this->options['whitespace_line_trim'] . // ~
				')?\s*endverbatim\s*' .
				'(?:' .
				preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '#') . '\s*' . // -%}
				'|' .
				preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_block'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . // ~%}[ \t\0\x0B]*
				'|' .
				preg_quote($this->options['tag_block'][1], '#') . // %}
				')
            }sx',

			'operator' => $this->getOperatorRegex(),

			// #}
			'lex_comment' => '{
                (?:' .
				preg_quote($this->options['whitespace_trim']) . preg_quote($this->options['tag_comment'][1], '#') . '\s*\n?' . // -#}\s*\n?
				'|' .
				preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_comment'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . // ~#}[ \t\0\x0B]*
				'|' .
				preg_quote($this->options['tag_comment'][1], '#') . '\n?' . // #}\n?
				')
            }sx',

			// verbatim %}
			'lex_block_raw' => '{
                \s*verbatim\s*
                (?:' .
				preg_quote($this->options['whitespace_trim'] . $this->options['tag_block'][1], '#') . '\s*' . // -%}\s*
				'|' .
				preg_quote($this->options['whitespace_line_trim'] . $this->options['tag_block'][1], '#') . '[' . $this->options['whitespace_line_chars'] . ']*' . // ~%}[ \t\0\x0B]*
				'|' .
				preg_quote($this->options['tag_block'][1], '#') . // %}
				')
            }Asx',

			'lex_block_line' => '{\s*line\s+(\d+)\s*' . preg_quote($this->options['tag_block'][1], '#') . '}As',

			// {{ or {% or {#
			'lex_tokens_start' => '{
                (' .
				preg_quote($this->options['tag_variable'][0], '#') . // {{
				'|' .
				preg_quote($this->options['tag_block'][0], '#') . // {%
				'|' .
				preg_quote($this->options['tag_comment'][0], '#') . // {#
				')(' .
				preg_quote($this->options['whitespace_trim'], '#') . // -
				'|' .
				preg_quote($this->options['whitespace_line_trim'], '#') . // ~
				')?
            }sx',
			'interpolation_start' => '{' . preg_quote($this->options['interpolation'][0], '#') . '\s*}A',
			'interpolation_end' => '{\s*' . preg_quote($this->options['interpolation'][1], '#') . '}A',
		];
	}

	/**
	 * Tokenize the source.
	 *
	 * @param Source $source
	 *
	 * @return TokenStream
	 * @throws SyntaxError
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function tokenize(Source $source): TokenStream
	{
		$this->source = $source;
		$this->code = str_replace(["\r\n", "\r"], "\n", $source->getCode());
		$this->cursor = 0;
		$this->lineNumber = 1;
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
			[$expect, $lineNumber] = array_pop($this->brackets);
			throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $lineNumber, $this->source);
		}

		return new TokenStream($this->tokens, $this->source);
	}

	/**
	 * Lex data.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @throws SyntaxError
	 * @since 1.0.0
	 */
	private function lexData(): void
	{
		if ($this->position === count($this->positions[0]) - 1)
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
		{
			if ($this->options['whitespace_trim'] === $this->positions[2][$this->position][0])
				$text = rtrim($text);
			else if ($this->options['whitespace_line_trim'] === $this->positions[2][$this->position][0])
				$text = rtrim($text, " \t\0\x0B");
		}

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
					$this->lineNumber = (int)$match[1];
				}
				else
				{
					$this->pushToken(Token::BLOCK_START_TYPE);
					$this->pushState(self::STATE_BLOCK);
					$this->currentVarBlockLine = $this->lineNumber;
				}
				break;

			case $this->options['tag_variable'][0]:
				$this->pushToken(Token::VAR_START_TYPE);
				$this->pushState(self::STATE_VAR);
				$this->currentVarBlockLine = $this->lineNumber;
				break;
		}
	}

	/**
	 * Lex block.
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexBlock(): void
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
	 * Lex variable.
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexVar(): void
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
	 * Lex expression.
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexExpression(): void
	{
		if (preg_match('/\s+/A', $this->code, $match, 0, $this->cursor))
		{
			$this->moveCursor($match[0]);

			if ($this->cursor >= $this->end)
				throw new SyntaxError(sprintf('Unclosed "%s".', self::STATE_BLOCK === $this->state ? 'block' : 'variable'), $this->currentVarBlockLine, $this->source);
		}

		if ($this->code[$this->cursor] === '=' && $this->code[$this->cursor + 1] === '>')
		{
			$this->pushToken(Token::ARROW_TYPE, '=>');
			$this->moveCursor('=>');
		}
		else if (preg_match($this->regexes['operator'], $this->code, $match, 0, $this->cursor))
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
			$number = (float)$match[0];

			if (ctype_digit($match[0]) && $number <= PHP_INT_MAX)
				$number = (int)$match[0];

			$this->pushToken(Token::NUMBER_TYPE, $number);
			$this->moveCursor($match[0]);
		}
		else if (strpos(self::PUNCTUATION, $this->code[$this->cursor]) !== false)
		{
			if (strpos('([{', $this->code[$this->cursor]) !== false)
			{
				$this->brackets[] = [$this->code[$this->cursor], $this->lineNumber];
			}
			else if (strpos(')]}', $this->code[$this->cursor]) !== false)
			{
				if (empty($this->brackets))
					throw new SyntaxError(sprintf('Unexpected "%s".', $this->code[$this->cursor]), $this->lineNumber, $this->source);

				[$expect, $lineNumber] = array_pop($this->brackets);

				if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}'))
					throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $lineNumber, $this->source);
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
			$this->brackets[] = ['"', $this->lineNumber];
			$this->pushState(self::STATE_STRING);
			$this->moveCursor($match[0]);
		}
		else
		{
			throw new SyntaxError(sprintf('Unexpected character "%s".', $this->code[$this->cursor]), $this->lineNumber, $this->source);
		}
	}

	/**
	 * Lex raw data.
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexRawData(): void
	{
		if (!preg_match($this->regexes['lex_raw_data'], $this->code, $match, PREG_OFFSET_CAPTURE, $this->cursor))
			throw new SyntaxError('Unexpected end of file: Unclosed "verbatim" block.', $this->lineNumber, $this->source);

		$text = substr($this->code, $this->cursor, $match[0][1] - $this->cursor);
		$this->moveCursor($text . $match[0][0]);

		if (isset($match[1][0]))
		{
			if ($this->options['whitespace_trim'] === $match[1][0])
				$text = rtrim($text);
			else
				$text = rtrim($text, " \t\0\x0B");
		}

		$this->pushToken(Token::TEXT_TYPE, $text);
	}

	/**
	 * Lex comment.
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexComment(): void
	{
		if (!preg_match($this->regexes['lex_comment'], $this->code, $match, PREG_OFFSET_CAPTURE, $this->cursor))
			throw new SyntaxError('Unclosed comment.', $this->lineNumber, $this->source);

		$this->moveCursor(substr($this->code, $this->cursor, $match[0][1] - $this->cursor) . $match[0][0]);
	}

	/**
	 * Lex string.
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexString(): void
	{
		if (preg_match($this->regexes['interpolation_start'], $this->code, $match, 0, $this->cursor))
		{
			$this->brackets[] = [$this->options['interpolation'][0], $this->lineNumber];
			$this->pushToken(Token::INTERPOLATION_START_TYPE);
			$this->moveCursor($match[0]);
			$this->pushState(self::STATE_INTERPOLATION);
		}
		else if (preg_match(self::REGEX_DQ_STRING_PART, $this->code, $match, 0, $this->cursor) && strlen($match[0]) > 0)
		{
			$this->pushToken(Token::STRING_TYPE, stripcslashes($match[0]));
			$this->moveCursor($match[0]);
		}
		else if (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, 0, $this->cursor))
		{
			[$expect, $lineNumber] = array_pop($this->brackets);

			if ($this->code[$this->cursor] !== '"')
				throw new SyntaxError(sprintf('Unclosed "%s".', $expect), $lineNumber, $this->source);

			$this->popState();

			++$this->cursor;
		}
		else
		{
			throw new SyntaxError(sprintf('Unexpected character "%s".', $this->code[$this->cursor]), $this->lineNumber, $this->source);
		}
	}

	/**
	 * Lex interpolation.
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function lexInterpolation(): void
	{
		$bracket = end($this->brackets);

		if ($this->options['interpolation'][0] === $bracket[0] && preg_match($this->regexes['interpolation_end'], $this->code, $match, 0, $this->cursor))
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
	 * Pushes a token.
	 *
	 * @param int   $type
	 * @param mixed $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function pushToken(int $type, $value = ''): void
	{
		if (Token::TEXT_TYPE === $type && $value === '')
			return;

		$this->tokens[] = new Token($type, $value, $this->lineNumber);
	}

	/**
	 * Moves the cursor.
	 *
	 * @param string $text
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function moveCursor(string $text): void
	{
		$this->cursor += strlen($text);
		$this->lineNumber += substr_count($text, "\n");
	}

	/**
	 * Gets the regex pattern used for operators.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getOperatorRegex(): string
	{
		$operators = array_merge(
			['='],
			array_keys($this->cappuccino->getUnaryOperators()),
			array_keys($this->cappuccino->getBinaryOperators())
		);

		$operators = array_combine($operators, array_map('strlen', $operators));
		$regex = [];

		arsort($operators);

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
	 * Pushes a state.
	 *
	 * @param int $state
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since
	 */
	private function pushState(int $state): void
	{
		$this->states[] = $this->state;
		$this->state = $state;
	}

	/**
	 * Pops the state.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function popState(): void
	{
		if (count($this->states) === 0)
			throw new LogicException('Cannot pop state without a previous state.');

		$this->state = array_pop($this->states);
	}

}
