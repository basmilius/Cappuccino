<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Error\SyntaxError;

/**
 * Class TokenStream
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 1.0.0
 */
final class TokenStream
{

	/**
	 * @var Token[]
	 */
	private $tokens;

	/**
	 * @var int
	 */
	private $current = 0;

	/**
	 * @var Source
	 */
	private $source;

	/**
	 * TokenStream constructor.
	 *
	 * @param Token[]     $tokens
	 * @param Source|null $source
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (array $tokens, ?Source $source = null)
	{
		$this->tokens = $tokens;
		$this->source = $source ?: new Source('', '');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.30.
	 */
	public function __toString ()
	{
		return implode("\n", $this->tokens);
	}

	/**
	 * Injects tokens.
	 *
	 * @param array $tokens
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function injectTokens (array $tokens) : void
	{
		$this->tokens = array_merge(array_slice($this->tokens, 0, $this->current), $tokens, array_slice($this->tokens, $this->current));
	}

	/**
	 * Sets the pointer to the next token and returns the old one.
	 *
	 * @return Token
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function next () : Token
	{
		if (!isset($this->tokens[++$this->current]))
			throw new SyntaxError('Unexpected end of template.', $this->tokens[$this->current - 1]->getLine(), $this->source);

		return $this->tokens[$this->current - 1];
	}

	/**
	 * Tests a token, sets the pointer to the next one and returns it or throws a SyntaxError.
	 *
	 * @param int|int[]       $primary
	 * @param string|string[] $secondary
	 *
	 * @return Token|null
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function nextIf ($primary, $secondary = null) : ?Token
	{
		if ($this->tokens[$this->current]->test($primary, $secondary))
			return $this->next();

		return null;
	}

	/**
	 * Tests a token and returns it or throws a SyntaxError.
	 *
	 * @param string|int  $type
	 * @param string|null $value
	 * @param string|null $message
	 *
	 * @return Token
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function expect ($type, ?string $value = null, ?string $message = null) : Token
	{
		$token = $this->tokens[$this->current];

		if (!$token->test($type, $value))
		{
			$line = $token->getLine();

			throw new SyntaxError(sprintf('%sUnexpected token "%s" of value "%s" ("%s" expected%s).',
				$message ? $message . '. ' : '',
				Token::typeToEnglish($token->getType()), $token->getValue(),
				Token::typeToEnglish($type), $value ? sprintf(' with value "%s"', $value) : ''),
				$line,
				$this->source
			);
		}
		$this->next();

		return $token;
	}

	/**
	 * Looks at the next token.
	 *
	 * @param int $number
	 *
	 * @return Token
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function look (int $number = 1) : Token
	{
		if (!isset($this->tokens[$this->current + $number]))
			throw new SyntaxError('Unexpected end of template.', $this->tokens[$this->current + $number - 1]->getLine(), $this->source);

		return $this->tokens[$this->current + $number];
	}

	/**
	 * Tests the current token.
	 *
	 * @param int|int[]       $primary
	 * @param string|string[] $secondary
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function test ($primary, $secondary = null) : bool
	{
		return $this->tokens[$this->current]->test($primary, $secondary);
	}

	/**
	 * Checks if end of stream was reached.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isEOF () : bool
	{
		return $this->tokens[$this->current]->getType() === Token::EOF_TYPE;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCurrent () : Token
	{
		return $this->tokens[$this->current];
	}

	/**
	 * Gets the source associated with this stream.
	 *
	 * @return Source
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext () : Source
	{
		return $this->source;
	}

}
