<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Parser;

/**
 * Class AbstractTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 1.0.0
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
