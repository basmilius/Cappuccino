<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Profiler\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Node;

/**
 * Class LeaveProfileNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Profiler\Node
 * @since 1.0.0
 */
class LeaveProfileNode extends Node
{

	/**
	 * LeaveProfileNode constructor.
	 *
	 * @param string $varName
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $varName)
	{
		parent::__construct([], ['var_name' => $varName]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler
			->write("\n")
			->write(sprintf("\$%s->leave(\$%s);\n\n", $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'));
	}

}
