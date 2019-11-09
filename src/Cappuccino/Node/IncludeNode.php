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
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Template;
use function sprintf;

/**
 * Class IncludeNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class IncludeNode extends Node implements NodeOutputInterface
{

	/**
	 * IncludeNode constructor.
	 *
	 * @param AbstractExpression      $expr
	 * @param AbstractExpression|null $variables
	 * @param bool                    $only
	 * @param bool                    $ignoreMissing
	 * @param int                     $lineNumber
	 * @param string|null             $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $expr, AbstractExpression $variables = null, bool $only = false, bool $ignoreMissing = false, int $lineNumber = 0, ?string $tag = null)
	{
		$nodes = ['expr' => $expr];

		if ($variables !== null)
			$nodes['variables'] = $variables;

		parent::__construct($nodes, ['only' => (bool)$only, 'ignore_missing' => (bool)$ignoreMissing], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->addDebugInfo($this);

		if ($this->getAttribute('ignore_missing'))
		{
			$template = $compiler->getVarName();

			$compiler
				->write(sprintf("$%s = null;\n", $template))
				->write("try {\n")
				->indent()
				->write(sprintf('$%s = ', $template));

			$this->addGetTemplate($compiler);

			$compiler
				->raw(";\n")
				->outdent()
				->write("} catch (LoaderError \$e) {\n")
				->indent()
				->write("// ignore missing template\n")
				->outdent()
				->write("}\n")
				->write(sprintf("if ($%s) {\n", $template))
				->indent()
				->write(sprintf('$%s->display(', $template));
			$this->addTemplateArguments($compiler);
			$compiler
				->raw(");\n")
				->outdent()
				->write("}\n");
		}
		else
		{
			$this->addGetTemplate($compiler);
			$compiler->raw('->display(');
			$this->addTemplateArguments($compiler);
			$compiler->raw(");\n");
		}
	}

	/**
	 * Adds the {@see Template::loadTemplate()} call.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	protected function addGetTemplate(Compiler $compiler): void
	{
		$compiler
			->write('$this->loadTemplate(')
			->subcompile($this->getNode('expr'))
			->raw(', ')
			->repr($this->getTemplateName())
			->raw(', ')
			->repr($this->getTemplateLine())
			->raw(')');
	}

	/**
	 * Adds template arguments to the {@see Template::loadTemplate()} call.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	protected function addTemplateArguments(Compiler $compiler): void
	{
		if (!$this->hasNode('variables'))
		{
			$compiler->raw(false === $this->getAttribute('only') ? '$context' : '[]');
		}
		else if ($this->getAttribute('only') === false)
		{
			$compiler
				->raw('StaticMethods::arrayMerge($context, ')
				->subcompile($this->getNode('variables'))
				->raw(')');
		}
		else
		{
			$compiler->raw('StaticMethods::toArray(');
			$compiler->subcompile($this->getNode('variables'));
			$compiler->raw(')');
		}
	}

}
