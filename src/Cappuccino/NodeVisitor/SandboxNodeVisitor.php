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
use Cappuccino\Node\CheckSecurityNode;
use Cappuccino\Node\CheckToStringNode;
use Cappuccino\Node\Expression\Binary\ConcatBinary;
use Cappuccino\Node\Expression\Binary\RangeBinary;
use Cappuccino\Node\Expression\FilterExpression;
use Cappuccino\Node\Expression\FunctionExpression;
use Cappuccino\Node\Expression\GetAttrExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\ModuleNode;
use Cappuccino\Node\Node;
use Cappuccino\Node\PrintNode;
use Cappuccino\Node\SetNode;

/**
 * Class SandboxNodeVisitor
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\NodeVisitor
 * @since 1.0.0
 */
final class SandboxNodeVisitor implements NodeVisitorInterface
{

	/**
	 * @var bool
	 */
	private $inAModule = false;

	/**
	 * @var array
	 */
	private $tags;

	/**
	 * @var array
	 */
	private $filters;

	/**
	 * @var array
	 */
	private $functions;

	/**
	 * @var bool
	 */
	private $needsToStringWrap = false;

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enterNode(Node $node, Cappuccino $env): Node
	{
		if ($node instanceof ModuleNode)
		{
			$this->inAModule = true;
			$this->tags = [];
			$this->filters = [];
			$this->functions = [];

			return $node;
		}
		else if ($this->inAModule)
		{
			if ($node->getNodeTag() && !isset($this->tags[$node->getNodeTag()]))
				$this->tags[$node->getNodeTag()] = $node;

			if ($node instanceof FilterExpression && !isset($this->filters[$node->getNode('filter')->getAttribute('value')]))
				$this->filters[$node->getNode('filter')->getAttribute('value')] = $node;

			if ($node instanceof FunctionExpression && !isset($this->functions[$node->getAttribute('name')]))
				$this->functions[$node->getAttribute('name')] = $node;

			if ($node instanceof RangeBinary && !isset($this->functions['range']))
				$this->functions['range'] = $node;

			if ($node instanceof PrintNode)
			{
				$this->needsToStringWrap = true;
				$this->wrapNode($node, 'expr');
			}

			if ($node instanceof SetNode && !$node->getAttribute('capture'))
				$this->needsToStringWrap = true;

			if ($this->needsToStringWrap)
			{
				if ($node instanceof ConcatBinary)
				{
					$this->wrapNode($node, 'left');
					$this->wrapNode($node, 'right');
				}

				if ($node instanceof FilterExpression)
				{
					$this->wrapNode($node, 'node');
					$this->wrapArrayNode($node, 'arguments');
				}

				if ($node instanceof FunctionExpression)
					$this->wrapArrayNode($node, 'arguments');
			}
		}

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function leaveNode(Node $node, Cappuccino $env): ?Node
	{
		if ($node instanceof ModuleNode)
		{
			$this->inAModule = false;

			$node->getNode('constructor_end')->setNode('_security_check', new Node([new CheckSecurityNode($this->filters, $this->tags, $this->functions), $node->getNode('display_start')]));
		}
		else if ($this->inAModule)
		{
			if ($node instanceof PrintNode || $node instanceof SetNode)
				$this->needsToStringWrap = false;
		}

		return $node;
	}

	/**
	 * Wraps a node.
	 *
	 * @param Node   $node
	 * @param string $name
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	private function wrapNode(Node $node, string $name): void
	{
		$expr = $node->getNode($name);

		if ($expr instanceof NameExpression || $expr instanceof GetAttrExpression)
			$node->setNode($name, new CheckToStringNode($expr));
	}

	/**
	 * Wraps an array node.
	 *
	 * @param Node   $node
	 * @param string $name
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	private function wrapArrayNode(Node $node, string $name): void
	{
		$args = $node->getNode($name);

		foreach ($args as $name => $_)
			$this->wrapNode($args, $name);
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
