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
use Cappuccino\Error\RuntimeError;

/**
 * Class WithNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
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
	public function __construct(Node $body, ?Node $variables = null, bool $only = false, int $lineno = -1, ?string $tag = null)
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
	public function compile(Compiler $compiler): void
	{
		$classRuntimeError = RuntimeError::class;
		$compiler->addDebugInfo($this);

		if ($this->hasNode('variables'))
		{
			$varsName = $compiler->getVarName();

			$compiler
				->write(sprintf('$%s = ', $varsName))
				->subcompile($this->getNode('variables'))
				->raw(";\n")
				->write(sprintf("if (\$%s instanceof \\Traversable) {\n", $varsName))
				->indent()
				->write(sprintf("\$%s = iterator_to_array(\$%s);\n", $varsName, $varsName))
				->outdent()
				->write("}\n")
				->write(sprintf("if (!is_array(\$%s)) {\n", $varsName))
				->indent()
				->write("throw new $classRuntimeError('Variables passed to the \"with\" tag must be a hash.');\n")
				->outdent()
				->write("}\n");

			if ($this->getAttribute('only'))
				$compiler->write("\$context = ['_parent' => \$context];\n");
			else
				$compiler->write("\$context['_parent'] = \$context;\n");

			$compiler->write(sprintf("\$context = \$this->cappuccino->mergeGlobals(array_merge(\$context, \$%s));\n", $varsName));
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
