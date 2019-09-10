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
use Cappuccino\Node\Node;

/**
 * Class AbstractNodeVisitor
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\NodeVisitor
 * @since 1.0.0
 */
abstract class AbstractNodeVisitor implements NodeVisitorInterface
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function enterNode(Node $node, Cappuccino $env): Node
	{
		return $this->doEnterNode($node, $env);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function leaveNode(Node $node, Cappuccino $env): ?Node
	{
		return $this->doLeaveNode($node, $env);
	}

	/**
	 * Enter the node.
	 *
	 * @param Node       $node
	 * @param Cappuccino $env
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected abstract function doEnterNode(Node $node, Cappuccino $env): Node;

	/**
	 * Leave the node.
	 *
	 * @param Node       $node
	 * @param Cappuccino $env
	 *
	 * @return Node|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected abstract function doLeaveNode(Node $node, Cappuccino $env): ?Node;

}
