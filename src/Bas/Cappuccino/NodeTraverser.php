<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Node\Node;

/**
 * Class NodeTraverser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 2.3.0
 */
final class NodeTraverser
{

	private $environment;
	private $visitors = [];

	/**
	 * NodeTraverser constructor.
	 *
	 * @param Environment            $environment
	 * @param NodeVisitorInterface[] $visitors
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (Environment $environment, array $visitors = [])
	{
		$this->environment = $environment;

		foreach ($visitors as $visitor)
			$this->addVisitor($visitor);
	}

	/**
	 * Adds a visitor.
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function addVisitor (NodeVisitorInterface $visitor)
	{
		if (!isset($this->visitors[$visitor->getPriority()]))
			$this->visitors[$visitor->getPriority()] = [];

		$this->visitors[$visitor->getPriority()][] = $visitor;
	}

	/**
	 * Traverses a node and calls the registered visitors.
	 *
	 * @param Node $node
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function traverse (Node $node)
	{
		ksort($this->visitors);
		foreach ($this->visitors as $visitors)
		{
			foreach ($visitors as $visitor)
			{
				$node = $this->traverseForVisitor($visitor, $node);
			}
		}

		return $node;
	}

	/**
	 * Traverses for a visitor.
	 *
	 * @param NodeVisitorInterface $visitor
	 * @param Node                 $node
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	private function traverseForVisitor (NodeVisitorInterface $visitor, Node $node)
	{
		$node = $visitor->enterNode($node, $this->environment);

		foreach ($node as $k => $n)
			if (false !== $n = $this->traverseForVisitor($visitor, $n))
				$node->setNode($k, $n);
			else
				$node->removeNode($k);

		return $visitor->leaveNode($node, $this->environment);
	}
}
