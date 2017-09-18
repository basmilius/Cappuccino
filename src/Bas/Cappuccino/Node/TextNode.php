<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;

/**
 * Class TextNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Node
 * @version 2.3.0
 */
class TextNode extends Node implements NodeOutputInterface
{

	/**
	 * TextNode constructor.
	 *
	 * @param string $data
	 * @param int    $lineno
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (string $data, int $lineno)
	{
		parent::__construct([], ['data' => $data], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler
			->addDebugInfo($this)
			->write('echo ')
			->string($this->getAttribute('data'))
			->raw(";\n");
	}

}
