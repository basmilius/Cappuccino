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

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use Cappuccino\Extension\SandboxExtension;
use Cappuccino\Node\Expression\FilterExpression;

/**
 * Class SandboxedPrintNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class SandboxedPrintNode extends PrintNode
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$classSandboxExtension = SandboxExtension::class;

		$compiler
			->addDebugInfo($this)
			->write("echo \$this->extensions['" . $classSandboxExtension . "']->ensureToStringAllowed(")
			->subcompile($this->getNode('expr'))
			->raw(");\n");
	}

	/**
	 * Removes node filters. This is mostly needed when another visitor adds filters (like the escaper one).
	 *
	 * @param Node $node
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function removeNodeFilter (Node $node): Node
	{
		if ($node instanceof FilterExpression)
			return $this->removeNodeFilter($node->getNode('node'));

		return $node;
	}

}
