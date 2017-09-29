<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;

/**
 * Class FlushNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @version 1.0.0
 */
class FlushNode extends Node
{

	/**
	 * FlushNode constructor.
	 *
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (int $lineno, ?string $tag)
	{
		parent::__construct([], [], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->addDebugInfo($this)->write("flush();\n");
	}

}
