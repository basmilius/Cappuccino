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

use Cappuccino\Parser;

/**
 * Class AbstractTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
abstract class AbstractTokenParser implements TokenParserInterface
{

	/**
	 * @var Parser
	 */
	protected $parser;

	/**
	 * Sets the parser.
	 *
	 * @param Parser $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setParser (Parser $parser): void
	{
		$this->parser = $parser;
	}

}
