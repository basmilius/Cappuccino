<?php
/**
 * Copyright (c) 2017 - 2019 - Bas Milius <bas@mili.us>
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=0); // TODO(Bas): Figure out if we can enable this again.

namespace Cappuccino;

use Cappuccino\Node\Node;
use Cappuccino\NodeVisitor\NodeVisitorInterface;
use function ksort;

/**
 * Class NodeTraverser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
final class NodeTraverser
{

	/**
	 * @var Cappuccino
	 */
	private $cappuccino;

	/**
	 * @var NodeVisitorInterface[]
	 */
	private $visitors = [];

	/**
	 * NodeTraverser constructor.
	 *
	 * @param Cappuccino             $cappuccino
	 * @param NodeVisitorInterface[] $visitors
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since
	 */
	public function __construct(Cappuccino $cappuccino, array $visitors = [])
	{
		$this->cappuccino = $cappuccino;

		foreach ($visitors as $visitor)
			$this->addVisitor($visitor);
	}

	/**
	 * Adds a {@see NodeVisitorInterface}.
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addVisitor(NodeVisitorInterface $visitor): void
	{
		$this->visitors[$visitor->getPriority()][] = $visitor;
	}

	/**
	 * Traverses a node and calls the registered visitors.
	 *
	 * @param Node $node
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function traverse(Node $node): Node
	{
		ksort($this->visitors);

		foreach ($this->visitors as $visitors)
			foreach ($visitors as $visitor)
				$node = $this->traverseForVisitor($visitor, $node);

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
	 * @since 1.0.0
	 */
	private function traverseForVisitor(NodeVisitorInterface $visitor, Node $node): ?Node
	{
		$node = $visitor->enterNode($node, $this->cappuccino);

		foreach ($node as $k => $n)
		{
			if (($m = $this->traverseForVisitor($visitor, $n)) !== null)
			{
				if ($m !== $n)
					$node->setNode($k, $m);
			}
			else
			{
				$node->removeNode($k);
			}
		}

		return $visitor->leaveNode($node, $this->cappuccino);
	}

}
