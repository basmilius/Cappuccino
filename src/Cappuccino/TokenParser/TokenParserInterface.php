<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\TokenParser;

use Cappuccino\Error\RuntimeError;
use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\Node;
use Cappuccino\Parser;
use Cappuccino\Token;

/**
 * Interface TokenParserInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
interface TokenParserInterface
{

	/**
	 * Sets the Parser associated with this TokenParser.
	 *
	 * @param Parser $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setParser(Parser $parser): void;

	/**
	 * Parses a token and returns a Node.
	 *
	 * @param Token $token
	 *
	 * @return Node|null
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse(Token $token): ?Node;

	/**
	 * Gets the tag name associated with this token parser.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string;

}
