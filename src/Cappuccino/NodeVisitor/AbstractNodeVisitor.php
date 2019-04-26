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
use Cappuccino\Error\Error;
use Cappuccino\Node\Node;
use Cappuccino\NodeVisitorInterface;

/**
 * Class AbstractNodeVisitor
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
abstract class AbstractNodeVisitor implements NodeVisitorInterface
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	final public function enterNode(Node $node, Cappuccino $env): Node
	{
		return $this->doEnterNode($node, $env);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	final public function leaveNode(Node $node, Cappuccino $env): Node
	{
		return $this->doLeaveNode($node, $env);
	}

	/**
	 * Does enterMode function.
	 *
	 * @param Node       $node
	 * @param Cappuccino $cappuccino
	 *
	 * @return Node
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @see AbstractNodeVisitor::enterNode()
	 */
	protected abstract function doEnterNode(Node $node, Cappuccino $cappuccino): Node;

	/**
	 * Does leaveMode function.
	 *
	 * @param Node       $node
	 * @param Cappuccino $env
	 *
	 * @return Node
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @see AbstractNodeVisitor::leaveNode()
	 */
	protected abstract function doLeaveNode(Node $node, Cappuccino $env): Node;

}

