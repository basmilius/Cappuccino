<?php
declare(strict_types=1);

namespace Bas\Cappuccino\NodeVisitor;

use Bas\Cappuccino\Cappuccino;
use Bas\Cappuccino\Node\CheckSecurityNode;
use Bas\Cappuccino\Node\Expression\AbstractExpression;
use Bas\Cappuccino\Node\Expression\FilterExpression;
use Bas\Cappuccino\Node\Expression\FunctionExpression;
use Bas\Cappuccino\Node\ModuleNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\PrintNode;
use Bas\Cappuccino\Node\SandboxedPrintNode;

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
	protected function doEnterNode (Node $node, Cappuccino $cappuccino): Node
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
			{
				$this->tags[$node->getNodeTag()] = $node;
			}

			if ($node instanceof FilterExpression && !isset($this->filters[$node->getNode('filter')->getAttribute('value')]))
			{
				$this->filters[$node->getNode('filter')->getAttribute('value')] = $node;
			}

			if ($node instanceof FunctionExpression && !isset($this->functions[$node->getAttribute('name')]))
			{
				$this->functions[$node->getAttribute('name')] = $node;
			}

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
	protected function doLeaveNode (Node $node, Cappuccino $env): Node
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
	public function getPriority (): int
	{
		return 0;
	}

}
