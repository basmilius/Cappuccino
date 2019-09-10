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

namespace Cappuccino\TokenParser;

use Cappuccino\Node\Node;
use Cappuccino\Parser;
use Cappuccino\Token;

/**
 * Interface TokenParserInterface
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
interface TokenParserInterface
{

	/**
	 * Sets the {@see Parser}.
	 *
	 * @param Parser $parser
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setParser(Parser $parser): void;

	/**
	 * Parses a {@see Token} and returns a {@see Node}.
	 *
	 * @param Token $token
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function parse(Token $token): Node;

	/**
	 * Gets the tag name associated with the used {@see TokenParserInterface}.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getTag(): string;

}
