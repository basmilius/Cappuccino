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
use Cappuccino\Node\Expression\AssignNameExpression;

/**
 * Class ForNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
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
	 * @param AssignNameExpression $keyTarget
	 * @param AssignNameExpression $valueTarget
	 * @param AbstractExpression   $seq
	 * @param Node|null            $body
	 * @param Node|null            $else
	 * @param int                  $lineNumber
	 * @param string|null          $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AssignNameExpression $keyTarget, AssignNameExpression $valueTarget, AbstractExpression $seq, ?Node $body = null, ?Node $else = null, int $lineNumber = 0, ?string $tag = null)
	{
		$body = new Node([$body, $this->loop = new ForLoopNode($lineNumber, $tag)]);
		$nodes = ['key_target' => $keyTarget, 'value_target' => $valueTarget, 'seq' => $seq, 'body' => $body];

		if ($else !== null)
			$nodes['else'] = $else;

		parent::__construct($nodes, ['with_loop' => true], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->write("\$context['_parent'] = \$context;\n")
			->write("\$context['_seq'] = StaticMethods::ensureTraversable(")
			->subcompile($this->getNode('seq'))
			->raw(");\n");

		if ($this->hasNode('else'))
			$compiler->write("\$context['_iterated'] = false;\n");

		if ($this->getAttribute('with_loop'))
		{
			$compiler
				->write("\$context['loop'] = [\n")
				->write("  'parent' => \$context['_parent'],\n")
				->write("  'index0' => 0,\n")
				->write("  'index'  => 1,\n")
				->write("  'first'  => true,\n")
				->write("];\n")
				->write("if (is_array(\$context['_seq']) || (is_object(\$context['_seq']) && \$context['_seq'] instanceof \Countable)) {\n")
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
		{
			$compiler
				->write("if (!\$context['_iterated']) {\n")
				->indent()
				->subcompile($this->getNode('else'))
				->outdent()
				->write("}\n");
		}

		$compiler->write("\$_parent = \$context['_parent'];\n");
		$compiler->write('unset($context[\'_seq\'], $context[\'_iterated\'], $context[\'' . $this->getNode('key_target')->getAttribute('name') . '\'], $context[\'' . $this->getNode('value_target')->getAttribute('name') . '\'], $context[\'_parent\'], $context[\'loop\']);' . "\n");
		$compiler->write("\$context = array_intersect_key(\$context, \$_parent) + \$_parent;\n");
	}

}
