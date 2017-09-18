<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Node;

/**
 * Class BlockReferenceExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 2.3.0
 */
class BlockReferenceExpression extends AbstractExpression
{

	/**
	 * BlockReferenceExpression constructor.
	 *
	 * @param Node      $name
	 * @param Node|null $template
	 * @param int       $lineno
	 * @param mixed     $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (Node $name, Node $template = null, int $lineno, $tag = null)
	{
		$nodes = ['name' => $name];

		if ($template !== null)
		{
			$nodes['template'] = $template;
		}

		parent::__construct($nodes, ['is_defined_test' => false, 'output' => false], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		if ($this->getAttribute('is_defined_test'))
		{
			$this->compileTemplateCall($compiler, 'hasBlock');
		}
		else
		{
			if ($this->getAttribute('output'))
			{
				$compiler->addDebugInfo($this);

				$this->compileTemplateCall($compiler, 'displayBlock')->raw(";\n");
			}
			else
			{
				$this->compileTemplateCall($compiler, 'renderBlock');
			}
		}
	}

	private function compileTemplateCall (Compiler $compiler, $method) : Compiler
	{
		if (!$this->hasNode('template'))
		{
			$compiler->write('$this');
		}
		else
		{
			$compiler->write('$this->loadTemplate(')->subcompile($this->getNode('template'))->raw(', ')->repr($this->getTemplateName())->raw(', ')->repr($this->getTemplateLine())->raw(')');
		}

		$compiler->raw(sprintf('->%s', $method));
		$this->compileBlockArguments($compiler);

		return $compiler;
	}

	private function compileBlockArguments (Compiler $compiler) : Compiler
	{
		$compiler->raw('(')->subcompile($this->getNode('name'))->raw(', $context');

		if (!$this->hasNode('template'))
			$compiler->raw(', $blocks');

		return $compiler->raw(')');
	}

}
