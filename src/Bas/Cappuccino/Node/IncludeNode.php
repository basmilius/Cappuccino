<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Error\LoaderError;
use Bas\Cappuccino\Node\Expression\AbstractExpression;

/**
 * Class IncludeNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Node
 * @version 2.3.0
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
	 * @param int                     $lineno
	 * @param null|string             $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (AbstractExpression $expr, AbstractExpression $variables = null, bool $only = false, bool $ignoreMissing = false, int $lineno, ?string $tag = null)
	{
		$nodes = ['expr' => $expr];

		if ($variables !== null)
			$nodes['variables'] = $variables;

		parent::__construct($nodes, ['only' => (bool)$only, 'ignore_missing' => (bool)$ignoreMissing], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->addDebugInfo($this);

		if ($this->getAttribute('ignore_missing'))
			$compiler->write("try {\n")->indent();

		$this->addGetTemplate($compiler);

		$compiler->raw('->display(');

		$this->addTemplateArguments($compiler);

		$compiler->raw(");\n");

		$classLoaderError = LoaderError::class;

		if ($this->getAttribute('ignore_missing'))
			$compiler->outdent()->write("} catch (" . $classLoaderError . " \$e) {\n")->indent()->write("// ignore missing template\n")->outdent()->write("}\n\n");
	}

	/**
	 * Adds a get template?
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	protected function addGetTemplate (Compiler $compiler) : void
	{
		$compiler->write('$this->loadTemplate(')->subcompile($this->getNode('expr'))->raw(', ')->repr($this->getTemplateName())->raw(', ')->repr($this->getTemplateLine())->raw(')');
	}

	/**
	 * Adds template arguments.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	protected function addTemplateArguments (Compiler $compiler) : void
	{
		if (!$this->hasNode('variables'))
			$compiler->raw(false === $this->getAttribute('only') ? '$context' : 'array()');
		else if (false === $this->getAttribute('only'))
			$compiler->raw('array_merge($context, ')->subcompile($this->getNode('variables'))->raw(')');
		else
			$compiler->subcompile($this->getNode('variables'));
	}

}
