<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Parser;
use Bas\Cappuccino\Token;

/**
 * Interface TokenParserInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
interface TokenParserInterface
{

	/**
	 * Sets the Parser associated with this TokenParser.
	 *
	 * @param Parser $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function setParser (Parser $parser) : void;

	/**
	 * Parses a token and returns a Node.
	 *
	 * @param Token $token
	 *
	 * @return Node|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : ?Node;

	/**
	 * Gets the tag name associated with this token parser.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getTag () : string;

}
