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

namespace Cappuccino\Node;

use Cappuccino\Compiler;

/**
 * Class SandboxNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class SandboxNode extends Node
{

	/**
	 * SandboxNode constructor.
	 *
	 * @param Node        $body
	 * @param int         $lineNumber
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(Node $body, int $lineNumber, ?string $tag = null)
	{
		parent::__construct(['body' => $body], [], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->write("if (!\$alreadySandboxed = \$this->sandbox->isSandboxed()) {\n")
			->indent()
			->write("\$this->sandbox->enableSandbox();\n")
			->outdent()
			->write("}\n")
			->subcompile($this->getNode('body'))
			->write("if (!\$alreadySandboxed) {\n")
			->indent()
			->write("\$this->sandbox->disableSandbox();\n")
			->outdent()
			->write("}\n");
	}

}
