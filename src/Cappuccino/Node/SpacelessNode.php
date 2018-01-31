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
 * Class SpacelessNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class SpacelessNode extends Node
{

	/**
	 * SpacelessNode constructor.
	 *
	 * @param Node        $body
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Node $body, int $lineno, ?string $tag = 'spaceless')
	{
		parent::__construct(['body' => $body], [], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->write("ob_start();\n")
			->subcompile($this->getNode('body'))
			->write("echo trim(preg_replace('/>\s+</', '><', ob_get_clean()));\n");
	}

}
