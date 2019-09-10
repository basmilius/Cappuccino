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
use Cappuccino\Node\BlockReferenceNode;
use Cappuccino\Node\Expression\BlockReferenceExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\FilterExpression;
use Cappuccino\Node\Expression\FunctionExpression;
use Cappuccino\Node\Expression\GetAttrExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\Expression\ParentExpression;
use Cappuccino\Node\ForNode;
use Cappuccino\Node\IncludeNode;
use Cappuccino\Node\Node;
use Cappuccino\Node\PrintNode;
use InvalidArgumentException;

/**
 * Class OptimizerNodeVisitor
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\NodeVisitor
 * @since 1.0.0
 */
final class OptimizerNodeVisitor implements NodeVisitorInterface
{

	public const OPTIMIZE_ALL = -1;
	public const OPTIMIZE_NONE = 0;
	public const OPTIMIZE_FOR = 2;
	public const OPTIMIZE_RAW_FILTER = 4;

	/**
	 * @var array
	 */
	private $loops = [];

	/**
	 * @var array
	 */
	private $loopsTargets = [];

	/**
	 * @var int
	 */
	private $optimizers;

	/**
	 * OptimizerNodeVisitor constructor.
	 *
	 * @param int $optimizers
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(int $optimizers = -1)
	{
		if (!is_int($optimizers) || $optimizers > (self::OPTIMIZE_FOR | self::OPTIMIZE_RAW_FILTER))
			throw new InvalidArgumentException(sprintf('Optimizer mode "%s" is not valid.', $optimizers));

		$this->optimizers = $optimizers;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enterNode(Node $node, Cappuccino $cappuccino): Node
	{
		if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers))
			$this->enterOptimizeFor($node, $cappuccino);

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function leaveNode(Node $node, Cappuccino $cappuccino): ?Node
	{
		if (self::OPTIMIZE_FOR === (self::OPTIMIZE_FOR & $this->optimizers))
			$this->leaveOptimizeFor($node, $cappuccino);

		if (self::OPTIMIZE_RAW_FILTER === (self::OPTIMIZE_RAW_FILTER & $this->optimizers))
			$node = $this->optimizeRawFilter($node, $cappuccino);

		$node = $this->optimizePrintNode($node, $cappuccino);

		return $node;
	}

	/**
	 * Optimizes print nodes.
	 *
	 * @param Node       $node
	 * @param Cappuccino $cappuccino
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function optimizePrintNode(Node $node, Cappuccino $cappuccino): Node
	{
		if (!$node instanceof PrintNode)
			return $node;

		$exprNode = $node->getNode('expr');

		if ($exprNode instanceof BlockReferenceExpression || $exprNode instanceof ParentExpression)
		{
			$exprNode->setAttribute('output', true);

			return $exprNode;
		}

		return $node;
	}

	/**
	 * Removes "raw" filters.
	 *
	 * @param Node       $node
	 * @param Cappuccino $cappuccino
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function optimizeRawFilter(Node $node, Cappuccino $cappuccino): Node
	{
		if ($node instanceof FilterExpression && 'raw' == $node->getNode('filter')->getAttribute('value'))
			return $node->getNode('node');

		return $node;
	}

	/**
	 * Optimizes "for" tag by removing the "loop" variable creation whenever possible.
	 *
	 * @param Node       $node
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function enterOptimizeFor(Node $node, Cappuccino $cappuccino): void
	{
		if ($node instanceof ForNode)
		{
			$node->setAttribute('with_loop', false);

			array_unshift($this->loops, $node);
			array_unshift($this->loopsTargets, $node->getNode('value_target')->getAttribute('name'));
			array_unshift($this->loopsTargets, $node->getNode('key_target')->getAttribute('name'));
		}
		else if (!$this->loops)
		{
			return;
		}
		else if ($node instanceof NameExpression && 'loop' === $node->getAttribute('name'))
		{
			$node->setAttribute('always_defined', true);
			$this->addLoopToCurrent();
		}
		else if ($node instanceof NameExpression && in_array($node->getAttribute('name'), $this->loopsTargets))
		{
			$node->setAttribute('always_defined', true);
		}
		else if ($node instanceof BlockReferenceNode || $node instanceof BlockReferenceExpression)
		{
			$this->addLoopToCurrent();
		}
		else if ($node instanceof IncludeNode && !$node->getAttribute('only'))
		{
			$this->addLoopToAll();
		}
		else if ($node instanceof FunctionExpression && 'include' === $node->getAttribute('name') && (!$node->getNode('arguments')->hasNode('with_context') || false !== $node->getNode('arguments')->getNode('with_context')->getAttribute('value')))
		{
			$this->addLoopToAll();
		}
		else if ($node instanceof GetAttrExpression && (!$node->getNode('attribute') instanceof ConstantExpression || 'parent' === $node->getNode('attribute')->getAttribute('value')) && (true === $this->loops[0]->getAttribute('with_loop') || ($node->getNode('node') instanceof NameExpression && 'loop' === $node->getNode('node')->getAttribute('name'))))
		{
			$this->addLoopToAll();
		}
	}

	/**
	 * Optimizes "for" tag by removing the "loop" variable creation whenever possible.
	 *
	 * @param Node       $node
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function leaveOptimizeFor(Node $node, Cappuccino $cappuccino): void
	{
		if ($node instanceof ForNode)
		{
			array_shift($this->loops);
			array_shift($this->loopsTargets);
			array_shift($this->loopsTargets);
		}
	}

	/**
	 * Adds loop to current.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function addLoopToCurrent(): void
	{
		$this->loops[0]->setAttribute('with_loop', true);
	}

	/**
	 * Adds loop to all.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function addLoopToAll(): void
	{
		foreach ($this->loops as $loop)
			$loop->setAttribute('with_loop', true);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPriority(): int
	{
		return 255;
	}
}
