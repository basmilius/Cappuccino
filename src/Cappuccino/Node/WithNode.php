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
 * Class WithNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
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
	 * @param int         $lineNumber
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(Node $body, Node $variables = null, bool $only = false, int $lineNumber = 0, ?string $tag = null)
	{
		$nodes = ['body' => $body];

		if ($variables !== null)
		{
			$nodes['variables'] = $variables;
		}

		parent::__construct($nodes, ['only' => $only], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->addDebugInfo($this);

		$parentContextName = $compiler->getVarName();

		$compiler->write(sprintf("\$%s = \$context;\n", $parentContextName));

		if ($this->hasNode('variables'))
		{
			$node = $this->getNode('variables');
			$varsName = $compiler->getVarName();
			$compiler
				->write(sprintf('$%s = ', $varsName))
				->subcompile($node)
				->raw(";\n")
				->write(sprintf("if (!StaticMethods::testIterable(\$%s)) {\n", $varsName))
				->indent()
				->write("throw new RuntimeError('Variables passed to the \"with\" tag must be a hash.', ")
				->repr($node->getTemplateLine())
				->raw(", \$this->getSourceContext());\n")
				->outdent()
				->write("}\n")
				->write(sprintf("\$%s = StaticMethods::toArray(\$%s);\n", $varsName, $varsName));

			if ($this->getAttribute('only'))
				$compiler->write("\$context = [];\n");

			$compiler->write(sprintf("\$context = \$this->cappuccino->mergeGlobals(array_merge(\$context, \$%s));\n", $varsName));
		}

		$compiler
			->subcompile($this->getNode('body'))
			->write(sprintf("\$context = \$%s;\n", $parentContextName));
	}

}
