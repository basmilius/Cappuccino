<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\NodeVisitor;

use Cappuccino\Cappuccino;
use Cappuccino\Node\CheckSecurityNode;
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Node\Expression\Binary\RangeBinary;
use Cappuccino\Node\Expression\FilterExpression;
use Cappuccino\Node\Expression\FunctionExpression;
use Cappuccino\Node\ModuleNode;
use Cappuccino\Node\Node;
use Cappuccino\Node\PrintNode;
use Cappuccino\Node\SandboxedPrintNode;

final class SandboxNodeVisitor extends AbstractNodeVisitor
{

	private $inAModule = false;
	private $tags;
	private $filters;
	private $functions;

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function doEnterNode(Node $node, Cappuccino $cappuccino): Node
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
				/** @var AbstractExpression $expression */
				$expression = $node->getNode('expr');

				return new SandboxedPrintNode($expression, $node->getTemplateLine(), $node->getNodeTag());
			}
		}

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function doLeaveNode(Node $node, Cappuccino $env): Node
	{
		if ($node instanceof ModuleNode)
		{
			$this->inAModule = false;

			$node->setNode('display_start', new Node([new CheckSecurityNode($this->filters, $this->tags, $this->functions), $node->getNode('display_start')]));
		}

		return $node;
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
