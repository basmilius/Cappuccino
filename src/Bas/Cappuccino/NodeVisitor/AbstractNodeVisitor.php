<?php
/**
 * This file is part of the Bas\Cappuccino package.
 *
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\NodeVisitor;

use Bas\Cappuccino\Cappuccino;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\NodeVisitorInterface;

/**
 * Class AbstractNodeVisitor
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @since 1.0.0
 */
abstract class AbstractNodeVisitor implements NodeVisitorInterface
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	final public function enterNode (Node $node, Cappuccino $env): Node
	{
		return $this->doEnterNode($node, $env);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	final public function leaveNode (Node $node, Cappuccino $env): Node
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
	 * @author Bas Milius <bas@mili.us>
	 * @see AbstractNodeVisitor::enterNode()
	 * @since 1.0.0
	 */
	protected abstract function doEnterNode (Node $node, Cappuccino $cappuccino): Node;

	/**
	 * Does leaveMode function.
	 *
	 * @param Node       $node
	 * @param Cappuccino $env
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @see AbstractNodeVisitor::leaveNode()
	 * @since 1.0.0
	 */
	protected abstract function doLeaveNode (Node $node, Cappuccino $env): Node;

}

