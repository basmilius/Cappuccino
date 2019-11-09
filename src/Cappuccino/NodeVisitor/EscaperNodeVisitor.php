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

namespace Cappuccino\NodeVisitor;

use Cappuccino\Cappuccino;
use Cappuccino\Extension\EscaperExtension;
use Cappuccino\Node\AutoEscapeNode;
use Cappuccino\Node\BlockNode;
use Cappuccino\Node\BlockReferenceNode;
use Cappuccino\Node\DoNode;
use Cappuccino\Node\Expression\ConditionalExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\FilterExpression;
use Cappuccino\Node\Expression\InlinePrint;
use Cappuccino\Node\ImportNode;
use Cappuccino\Node\ModuleNode;
use Cappuccino\Node\Node;
use Cappuccino\Node\PrintNode;
use Cappuccino\NodeTraverser;
use function array_pop;
use function count;
use function get_class;
use function in_array;

/**
 * Class EscaperNodeVisitor
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\NodeVisitor
 * @since 1.0.0
 */
final class EscaperNodeVisitor implements NodeVisitorInterface
{

	/**
	 * @var array
	 */
	private $statusStack = [];

	/**
	 * @var array
	 */
	private $blocks = [];

	/**
	 * @var SafeAnalysisNodeVisitor
	 */
	private $safeAnalysis;

	/**
	 * @var NodeTraverser
	 */
	private $traverser;

	/**
	 * @var bool
	 */
	private $defaultStrategy = false;

	/**
	 * @var array
	 */
	private $safeVars = [];

	/**
	 * EscaperNodeVisitor constructor.
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->safeAnalysis = new SafeAnalysisNodeVisitor();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enterNode(Node $node, Cappuccino $cappuccino): Node
	{
		if ($node instanceof ModuleNode)
		{
			if ($cappuccino->hasExtension(EscaperExtension::class) && $defaultStrategy = $cappuccino->getExtension(EscaperExtension::class)->getDefaultStrategy($node->getTemplateName()))
				$this->defaultStrategy = $defaultStrategy;

			$this->safeVars = [];
			$this->blocks = [];
		}
		else if ($node instanceof AutoEscapeNode)
		{
			$this->statusStack[] = $node->getAttribute('value');
		}
		else if ($node instanceof BlockNode)
		{
			$this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping($cappuccino);
		}
		else if ($node instanceof ImportNode)
		{
			$this->safeVars[] = $node->getNode('var')->getAttribute('name');
		}

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function leaveNode(Node $node, Cappuccino $cappuccino): ?Node
	{
		if ($node instanceof ModuleNode)
		{
			$this->defaultStrategy = false;
			$this->safeVars = [];
			$this->blocks = [];
		}
		else if ($node instanceof FilterExpression)
		{
			return $this->preEscapeFilterNode($node, $cappuccino);
		}
		else if ($node instanceof PrintNode && ($type = $this->needEscaping($cappuccino)) !== false)
		{
			/** @var string $type */

			$expression = $node->getNode('expr');

			if ($expression instanceof ConditionalExpression && $this->shouldUnwrapConditional($expression, $cappuccino, $type))
				return new DoNode($this->unwrapConditional($expression, $cappuccino, $type), $expression->getTemplateLine());

			return $this->escapePrintNode($node, $cappuccino, $type);
		}

		if ($node instanceof AutoEscapeNode || $node instanceof BlockNode)
			array_pop($this->statusStack);
		else if ($node instanceof BlockReferenceNode)
			$this->blocks[$node->getAttribute('name')] = $this->needEscaping($cappuccino);

		return $node;
	}

	/**
	 * Returns TRUE if a we should unwrap a conditional expression.
	 *
	 * @param ConditionalExpression $expression
	 * @param Cappuccino            $cappuccino
	 * @param string                $type
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function shouldUnwrapConditional(ConditionalExpression $expression, Cappuccino $cappuccino, string $type): bool
	{
		$expr2Safe = $this->isSafeFor($type, $expression->getNode('expr2'), $cappuccino);
		$expr3Safe = $this->isSafeFor($type, $expression->getNode('expr3'), $cappuccino);

		return $expr2Safe !== $expr3Safe;
	}

	/**
	 * Unwraps a conditional expression.
	 *
	 * @param ConditionalExpression $expression
	 * @param Cappuccino            $cappuccino
	 * @param string                $type
	 *
	 * @return ConditionalExpression
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function unwrapConditional(ConditionalExpression $expression, Cappuccino $cappuccino, string $type): ConditionalExpression
	{
		$expr2 = $expression->getNode('expr2');

		if ($expr2 instanceof ConditionalExpression && $this->shouldUnwrapConditional($expr2, $cappuccino, $type))
			$expr2 = $this->unwrapConditional($expr2, $cappuccino, $type);
		else
			$expr2 = $this->escapeInlinePrintNode(new InlinePrint($expr2, $expr2->getTemplateLine()), $cappuccino, $type);

		$expr3 = $expression->getNode('expr3');

		if ($expr3 instanceof ConditionalExpression && $this->shouldUnwrapConditional($expr3, $cappuccino, $type))
			$expr3 = $this->unwrapConditional($expr3, $cappuccino, $type);
		else
			$expr3 = $this->escapeInlinePrintNode(new InlinePrint($expr3, $expr3->getTemplateLine()), $cappuccino, $type);

		return new ConditionalExpression($expression->getNode('expr1'), $expr2, $expr3, $expression->getTemplateLine());
	}

	/**
	 * Escapes a {@see InlinePrint}.
	 *
	 * @param InlinePrint $node
	 * @param Cappuccino  $cappuccino
	 * @param string      $type
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function escapeInlinePrintNode(InlinePrint $node, Cappuccino $cappuccino, string $type): Node
	{
		$expression = $node->getNode('node');

		if ($this->isSafeFor($type, $expression, $cappuccino))
			return $node;

		return new InlinePrint($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
	}

	/**
	 * Escapes a {@see PrintNode}.
	 *
	 * @param PrintNode  $node
	 * @param Cappuccino $cappuccino
	 * @param string     $type
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function escapePrintNode(PrintNode $node, Cappuccino $cappuccino, string $type): Node
	{
		if ($type === false)
			return $node;

		$expression = $node->getNode('expr');

		if ($this->isSafeFor($type, $expression, $cappuccino))
			return $node;

		$class = get_class($node);

		return new $class($this->getEscaperFilter($type, $expression), $node->getTemplateLine());
	}

	/**
	 * Pre escapes the filter node.
	 *
	 * @param FilterExpression $filter
	 * @param Cappuccino       $cappuccino
	 *
	 * @return FilterExpression
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function preEscapeFilterNode(FilterExpression $filter, Cappuccino $cappuccino): FilterExpression
	{
		$name = $filter->getNode('filter')->getAttribute('value');
		$type = $cappuccino->getFilter($name)->getPreEscape();

		if ($type === null)
			return $filter;

		$node = $filter->getNode('node');

		if ($this->isSafeFor($type, $node, $cappuccino))
			return $filter;

		$filter->setNode('node', $this->getEscaperFilter($type, $node));

		return $filter;
	}

	/**
	 * Returns TRUE the given expression is save.
	 *
	 * @param string     $type
	 * @param Node       $expression
	 * @param Cappuccino $cappuccino
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function isSafeFor(string $type, Node $expression, Cappuccino $cappuccino): bool
	{
		$safe = $this->safeAnalysis->getSafe($expression);

		if ($safe === null)
		{
			if ($this->traverser === null)
				$this->traverser = new NodeTraverser($cappuccino, [$this->safeAnalysis]);

			$this->safeAnalysis->setSafeVars($this->safeVars);

			$this->traverser->traverse($expression);
			$safe = $this->safeAnalysis->getSafe($expression);
		}

		return in_array($type, $safe) || in_array('all', $safe);
	}

	/**
	 * Returns TRUE if escaping is needed.
	 *
	 * @param Cappuccino $cappuccino
	 *
	 * @return bool|mixed
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function needEscaping(Cappuccino $cappuccino)
	{
		if (count($this->statusStack))
			return $this->statusStack[count($this->statusStack) - 1];

		return $this->defaultStrategy ? $this->defaultStrategy : false;
	}

	/**
	 * Gets the filter expression.
	 *
	 * @param string $type
	 * @param Node   $node
	 *
	 * @return FilterExpression
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function getEscaperFilter(string $type, Node $node): FilterExpression
	{
		$line = $node->getTemplateLine();
		$name = new ConstantExpression('escape', $line);
		$args = new Node([new ConstantExpression((string)$type, $line), new ConstantExpression(null, $line), new ConstantExpression(true, $line)]);

		return new FilterExpression($node, $name, $args, $line);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPriority(): int
	{
		return 0;
	}

}
