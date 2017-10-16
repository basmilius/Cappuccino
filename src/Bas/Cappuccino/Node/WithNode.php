<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Error\RuntimeError;

/**
 * Class WithNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @since 1.0.0
 */
class WithNode extends Node
{

	/**
	 * WithNode constructor.
	 *
	 * @param Node        $body
	 * @param Node|null   $variables
	 * @param bool        $only
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Node $body, ?Node $variables = null, bool $only = false, int $lineno, ?string $tag = null)
	{
		$nodes = ['body' => $body];

		if ($variables !== null)
			$nodes['variables'] = $variables;

		parent::__construct($nodes, ['only' => (bool)$only], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->addDebugInfo($this);

		if ($this->hasNode('variables'))
		{
			$varsName = $compiler->getVarName();

			$classRuntimeError = RuntimeError::class;

			$compiler
				->write(sprintf('$%s = ', $varsName))
				->subcompile($this->getNode('variables'))
				->raw(";\n")
				->write(sprintf("if (!is_array(\$%s)) {\n", $varsName))
				->indent()
				->write("throw new " . $classRuntimeError . "('Variables passed to the \"with\" tag must be a hash.');\n")
				->outdent()
				->write("}\n");

			if ($this->getAttribute('only'))
				$compiler->write("\$context = array('_parent' => \$context);\n");
			else
				$compiler->write("\$context['_parent'] = \$context;\n");

			$compiler->write(sprintf("\$context = array_merge(\$context, \$%s);\n", $varsName));
		}
		else
		{
			$compiler->write("\$context['_parent'] = \$context;\n");
		}

		$compiler
			->subcompile($this->getNode('body'))
			->write("\$context = \$context['_parent'];\n");
	}

}
