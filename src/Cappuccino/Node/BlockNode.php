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

/**
 * Class BlockNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class BlockNode extends Node
{

	/**
	 * BlockNode constructor.
	 *
	 * @param string      $name
	 * @param Node        $body
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(string $name, Node $body, int $lineno, ?string $tag = null)
	{
		parent::__construct(['body' => $body], ['name' => $name], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->addDebugInfo($this)->write(sprintf("public function block_%s(\$context, array \$blocks = [])\n", $this->getAttribute('name')), "{\n")->indent();
		$compiler->subcompile($this->getNode('body'))->outdent()->write("}\n\n");
	}

}
