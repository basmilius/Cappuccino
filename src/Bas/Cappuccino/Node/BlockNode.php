<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;

/**
 * Class BlockNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @version 2.3.0
 */
class BlockNode extends Node
{

	/**
	 * BlockNode constructor.
	 *
	 * @param string $name
	 * @param Node   $body
	 * @param int    $lineno
	 * @param null   $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (string $name, Node $body, $lineno, $tag = null)
	{
		parent::__construct(['body' => $body], ['name' => $name], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->addDebugInfo($this)->write(sprintf("public function block_%s(\$context, array \$blocks = array())\n", $this->getAttribute('name')), "{\n")->indent();
		$compiler->subcompile($this->getNode('body'))->outdent()->write("}\n\n");
	}

}
