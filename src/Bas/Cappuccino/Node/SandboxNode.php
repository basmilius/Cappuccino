<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Extension\SandboxExtension;

/**
 * Class SandboxNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @version 1.0.0
 */
class SandboxNode extends Node
{

	/**
	 * SandboxNode constructor.
	 *
	 * @param Node        $body
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Node $body, int $lineno, ?string $tag = null)
	{
		parent::__construct(['body' => $body], [], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$classSandboxExtension = SandboxExtension::class;

		$compiler
			->addDebugInfo($this)
			->write("\$sandbox = \$this->environment->getExtension(" . $classSandboxExtension . "::class);\n")
			->write("if (!\$alreadySandboxed = \$sandbox->isSandboxed()) {\n")
			->indent()
			->write("\$sandbox->enableSandbox();\n")
			->outdent()
			->write("}\n")
			->subcompile($this->getNode('body'))
			->write("if (!\$alreadySandboxed) {\n")
			->indent()
			->write("\$sandbox->disableSandbox();\n")
			->outdent()
			->write("}\n");
	}

}
