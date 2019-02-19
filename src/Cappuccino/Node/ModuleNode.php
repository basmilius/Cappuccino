<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 * This file is part of the Cappuccino package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\Node;

use Cappuccino\Cappuccino;
use Cappuccino\Compiler;
use Cappuccino\Error\Error;
use Cappuccino\Error\LoaderError;
use Cappuccino\Error\RuntimeError;
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Source;
use Cappuccino\Template;
use LogicException;

/**
 * Class ModuleNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
final class ModuleNode extends Node
{

	private $source;

	/**
	 * ModuleNode constructor.
	 *
	 * @param Node                    $body
	 * @param AbstractExpression|null $parent
	 * @param Node                    $blocks
	 * @param Node                    $macros
	 * @param Node                    $traits
	 * @param array                   $embeddedTemplates
	 * @param Source                  $source
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(Node $body, ?AbstractExpression $parent, Node $blocks, Node $macros, Node $traits, array $embeddedTemplates, Source $source)
	{
		$this->source = $source;

		$nodes = [
			'body' => $body,
			'blocks' => $blocks,
			'macros' => $macros,
			'traits' => $traits,
			'display_start' => new Node(),
			'display_end' => new Node(),
			'constructor_start' => new Node(),
			'constructor_end' => new Node(),
			'class_end' => new Node(),
		];

		if (null !== $parent)
			$nodes['parent'] = $parent;

		parent::__construct($nodes, ['index' => null, 'embedded_templates' => $embeddedTemplates,], 1);

		$this->setTemplateName($this->source->getName());
	}

	/**
	 * Sets the index.
	 *
	 * @param int $index
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setIndex(int $index): void
	{
		$this->setAttribute('index', $index);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$this->compileTemplate($compiler);

		foreach ($this->getAttribute('embedded_templates') as $template)
			$compiler->subcompile($template);
	}

	/**
	 * Compiles the template.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileTemplate(Compiler $compiler): void
	{
		if (!$this->getAttribute('index'))
			$compiler->write('<?php');

		$this->compileClassHeader($compiler);
		$this->compileConstructor($compiler);
		$this->compileGetParent($compiler);
		$this->compileDisplay($compiler);

		$compiler->subcompile($this->getNode('blocks'));

		$this->compileMacros($compiler);
		$this->compileGetTemplateName($compiler);
		$this->compileIsTraitable($compiler);
		$this->compileDebugInfo($compiler);
		$this->compileGetSourceContext($compiler);
		$this->compileClassFooter($compiler);
	}

	/**
	 * Compile get parent.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileGetParent(Compiler $compiler): void
	{
		if (!$this->hasNode('parent'))
			return;

		$parent = $this->getNode('parent');

		$compiler
			->write("protected function doGetParent(array \$context)\n", "{\n")
			->indent()
			->addDebugInfo($parent)
			->write('return ');

		if ($parent instanceof ConstantExpression)
			$compiler->subcompile($parent);
		else
			$compiler
				->raw('$this->loadTemplate(')
				->subcompile($parent)
				->raw(', ')
				->repr($this->source->getName())
				->raw(', ')
				->repr($parent->getTemplateLine())
				->raw(')');

		$compiler
			->raw(";\n")
			->outdent()
			->write("}\n\n");
	}

	/**
	 * Compile class header.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileClassHeader(Compiler $compiler): void
	{
		$templateClassName = $compiler->getCappuccino()->getTemplateClass($this->source->getName(), $this->getAttribute('index'));

		$compiler
			->write("\n\n")
			->write('/* ' . str_replace('*/', '* /', $this->source->getName()) . " */\n")
			->write(sprintf('class %s extends %s', $templateClassName, Template::class))
			->write("\n{\n\n")
			->indent()
			->write("private \$source;\n\n");
	}

	/**
	 * Compile constructor.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileConstructor(Compiler $compiler): void
	{
		$classCappuccino = Cappuccino::class;
		$classRuntimeError = RuntimeError::class;

		$compiler
			->write("public function __construct($classCappuccino \$cappuccino)\n", "{\n")
			->indent()
			->subcompile($this->getNode('constructor_start'))
			->write("parent::__construct(\$cappuccino);\n\n")
			->write("\$this->source = \$this->getSourceContext();\n");

		if (!$this->hasNode('parent'))
			$compiler->write("\$this->parent = false;\n\n");
		else if (($parent = $this->getNode('parent')) && $parent instanceof ConstantExpression)
			$compiler
				->addDebugInfo($parent)
				->write('$this->parent = $this->loadTemplate(')
				->subcompile($parent)
				->raw(', ')
				->repr($this->source->getName())
				->raw(', ')
				->repr($parent->getTemplateLine())
				->raw(");\n");

		$countTraits = count($this->getNode('traits'));

		if ($countTraits)
		{
			foreach ($this->getNode('traits') as $i => $trait)
			{
				$node = $trait->getNode('template');

				$compiler
					->write(sprintf('$_trait_%s = $this->loadTemplate(', $i))
					->subcompile($node)
					->raw(', ')
					->repr($node->getTemplateName())
					->raw(', ')
					->repr($node->getTemplateLine())
					->raw(");\n");

				$compiler
					->addDebugInfo($trait->getNode('template'))
					->write(sprintf("if (!\$_trait_%s->isTraitable()) {\n", $i))
					->indent()
					->write("throw new $classRuntimeError('Template \"'.")
					->subcompile($trait->getNode('template'))
					->raw(".'\" cannot be used as a trait.', ")
					->repr($node->getTemplateLine())
					->raw(", \$this->source);\n")
					->outdent()
					->write("}\n")
					->write(sprintf("\$_trait_%s_blocks = \$_trait_%s->getBlocks();\n\n", $i, $i));

				foreach ($trait->getNode('targets') as $key => $value)
					$compiler
						->write(sprintf('if (!isset($_trait_%s_blocks[', $i))
						->string($key)
						->raw("])) {\n")
						->indent()
						->write("throw new $classRuntimeError('Block ")
						->string($key)
						->raw(' is not defined in trait ')
						->subcompile($trait->getNode('template'))
						->raw(".', ")
						->repr($node->getTemplateLine())
						->raw(", \$this->source);\n")
						->outdent()
						->write("}\n\n")
						->write(sprintf('$_trait_%s_blocks[', $i))
						->subcompile($value)
						->raw(sprintf('] = $_trait_%s_blocks[', $i))
						->string($key)
						->raw(sprintf(']; unset($_trait_%s_blocks[', $i))
						->string($key)
						->raw("]);\n\n");
			}

			if ($countTraits > 1)
			{
				$compiler
					->write("\$this->traits = array_merge(\n")
					->indent();

				for ($i = 0; $i < $countTraits; ++$i)
					$compiler->write(sprintf('$_trait_%s_blocks' . ($i == $countTraits - 1 ? '' : ',') . "\n", $i));

				$compiler
					->outdent()
					->write(");\n\n");
			}
			else
			{
				$compiler->write("\$this->traits = \$_trait_0_blocks;\n\n");
			}

			$compiler
				->write("\$this->blocks = array_merge(\n")
				->indent()
				->write("\$this->traits,\n")
				->write("[\n");
		}
		else
		{
			$compiler
				->write("\$this->blocks = [\n");
		}

		$compiler
			->indent();

		foreach ($this->getNode('blocks') as $name => $node)
			$compiler->write(sprintf("'%s' => [\$this, 'block_%s'],\n", $name, $name));

		if ($countTraits)
		{
			$compiler
				->outdent()
				->write("]\n");

			$compiler
				->outdent()
				->write(");\n")
				->outdent()
				->subcompile($this->getNode('constructor_end'))
				->write("}\n\n");
		}
		else
		{
			$compiler
				->outdent()
				->write("];\n")
				->outdent()
				->subcompile($this->getNode('constructor_end'))
				->write("}\n\n");
		}
	}

	/**
	 * Compile display.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileDisplay(Compiler $compiler): void
	{
		$compiler
			->write("protected function doDisplay(array \$context, array \$blocks = []): void\n", "{\n")
			->indent()
			->subcompile($this->getNode('display_start'))
			->subcompile($this->getNode('body'));

		if ($this->hasNode('parent'))
		{
			$parent = $this->getNode('parent');
			$compiler->addDebugInfo($parent);

			if ($parent instanceof ConstantExpression)
				$compiler->write('$this->parent');
			else
				$compiler->write('$this->getParent($context)');

			$compiler->raw("->display(\$context, array_merge(\$this->blocks, \$blocks));\n");
		}

		$compiler
			->subcompile($this->getNode('display_end'))
			->outdent()
			->write("}\n\n");
	}

	/**
	 * Compile class footer.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileClassFooter(Compiler $compiler): void
	{
		$compiler
			->subcompile($this->getNode('class_end'))
			->outdent()
			->write("\n}\n");
	}

	/**
	 * Compile macros.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileMacros(Compiler $compiler): void
	{
		$compiler->subcompile($this->getNode('macros'));
	}

	/**
	 * Compile get template name.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileGetTemplateName(Compiler $compiler): void
	{
		$compiler
			->write("public function getTemplateName(): string\n", "{\n")
			->indent()
			->write('return ')
			->repr($this->source->getName())
			->raw(";\n")
			->outdent()
			->write("}\n\n");
	}

	/**
	 * Compile is traitable.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileIsTraitable(Compiler $compiler): void
	{
		$traitable = !$this->hasNode('parent') && 0 === count($this->getNode('macros'));

		if ($traitable)
		{
			if ($this->getNode('body') instanceof BodyNode)
				$nodes = $this->getNode('body')->getNode(0);
			else
				$nodes = $this->getNode('body');

			if (!count($nodes))
				$nodes = new Node([$nodes]);

			foreach ($nodes as $node)
			{
				if (!count($node))
					continue;

				if ($node instanceof TextNode && ctype_space($node->getAttribute('data')))
					continue;

				if ($node instanceof BlockReferenceNode)
					continue;

				$traitable = false;
				break;
			}
		}

		if ($traitable)
			return;

		$compiler
			->write("public function isTraitable(): bool\n", "{\n")
			->indent()
			->write(sprintf("return %s;\n", $traitable ? 'true' : 'false'))
			->outdent()
			->write("}\n\n");
	}

	/**
	 * Compile debug info.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileDebugInfo(Compiler $compiler): void
	{
		$compiler
			->write("public function getDebugInfo(): array\n", "{\n")
			->indent()
			->write(sprintf("return %s;\n", str_replace("\n", '', var_export(array_reverse($compiler->getDebugInfo(), true), true))))
			->outdent()
			->write("}\n\n");
	}

	/**
	 * Get source context.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileGetSourceContext(Compiler $compiler): void
	{
		$classSource = Source::class;

		$compiler
			->write("public function getSourceContext(): $classSource\n", "{\n")
			->indent()
			->write("return new $classSource(")
			->string($compiler->getCappuccino()->isDebug() ? $this->source->getCode() : '')
			->raw(', ')
			->string($this->source->getName())
			->raw(', ')
			->string($this->source->getPath())
			->raw(");\n")
			->outdent()
			->write("}\n");
	}

	/**
	 * Compile and load template.
	 *
	 * @param Compiler $compiler
	 * @param Node     $node
	 * @param string   $var
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileLoadTemplate(Compiler $compiler, Node $node, string $var): void
	{
		if ($node instanceof ConstantExpression)
			$compiler
				->write(sprintf('%s = $this->loadTemplate(', $var))
				->subcompile($node)
				->raw(', ')
				->repr($node->getTemplateName())
				->raw(', ')
				->repr($node->getTemplateLine())
				->raw(");\n");
		else
			throw new LogicException('Trait templates can only be constant nodes.');
	}

}
