<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\AbstractExpression;
use Bas\Cappuccino\Node\Expression\AssignNameExpression;
use Bas\Cappuccino\Util\StaticMethods;

/**
 * Class ForNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @version 1.0.0
 */
class ForNode extends Node
{

	/**
	 * @var ForLoopNode
	 */
	private $loop;

	/**
	 * ForNode constructor.
	 *
	 * @param AssignNameExpression    $keyTarget
	 * @param AssignNameExpression    $valueTarget
	 * @param AbstractExpression      $seq
	 * @param AbstractExpression|null $ifexpr
	 * @param Node                    $body
	 * @param Node|null               $else
	 * @param int                     $lineno
	 * @param string|null             $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (AssignNameExpression $keyTarget, AssignNameExpression $valueTarget, AbstractExpression $seq, AbstractExpression $ifexpr = null, Node $body, Node $else = null, int $lineno, ?string $tag = null)
	{
		$body = new Node([$body, $this->loop = new ForLoopNode($lineno, $tag)]);

		if ($ifexpr !== null)
			$body = new IfNode(new Node([$ifexpr, $body]), null, $lineno, $tag);

		$nodes = ['key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq, 'body' => $body];

		if ($else !== null)
			$nodes['else'] = $else;

		parent::__construct($nodes, ['with_loop' => true, 'ifexpr' => null !== $ifexpr], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$classStaticMethods = StaticMethods::class;

		$compiler
			->addDebugInfo($this)
			->write("\$context['_parent'] = \$context;\n")
			->write("\$context['_seq'] = $classStaticMethods::ensureTraversable(")
			->subcompile($this->getNode('seq'))
			->raw(");\n");

		if ($this->hasNode('else'))
			$compiler->write("\$context['_iterated'] = false;\n");

		if ($this->getAttribute('with_loop'))
		{
			$compiler
				->write("\$context['loop'] = array(\n")
				->write("  'parent' => \$context['_parent'],\n")
				->write("  'index0' => 0,\n")
				->write("  'index'  => 1,\n")
				->write("  'first'  => true,\n")
				->write(");\n");

			if (!$this->getAttribute('ifexpr'))
				$compiler
					->write("if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof Countable)) {\n")
					->indent()
					->write("\$length = count(\$context['_seq']);\n")
					->write("\$context['loop']['revindex0'] = \$length - 1;\n")
					->write("\$context['loop']['revindex'] = \$length;\n")
					->write("\$context['loop']['length'] = \$length;\n")
					->write("\$context['loop']['last'] = 1 === \$length;\n")
					->outdent()
					->write("}\n");
		}

		$this->loop->setAttribute('else', $this->hasNode('else'));
		$this->loop->setAttribute('with_loop', $this->getAttribute('with_loop'));
		$this->loop->setAttribute('ifexpr', $this->getAttribute('ifexpr'));

		$compiler
			->write("foreach (\$context['_seq'] as ")
			->subcompile($this->getNode('key_target'))
			->raw(' => ')
			->subcompile($this->getNode('value_target'))
			->raw(") {\n")
			->indent()
			->subcompile($this->getNode('body'))
			->outdent()
			->write("}\n");

		if ($this->hasNode('else'))
			$compiler
				->write("if (!\$context['_iterated']) {\n")
				->indent()
				->subcompile($this->getNode('else'))
				->outdent()
				->write("}\n");

		$compiler->write("\$_parent = \$context['_parent'];\n");
		$compiler->write('unset($context[\'_seq\'], $context[\'_iterated\'], $context[\'' . $this->getNode('key_target')->getAttribute('name') . '\'], $context[\'' . $this->getNode('value_target')->getAttribute('name') . '\'], $context[\'_parent\'], $context[\'loop\']);' . "\n");
		$compiler->write("\$context = array_intersect_key(\$context, \$_parent) + \$_parent;\n");
	}

}
