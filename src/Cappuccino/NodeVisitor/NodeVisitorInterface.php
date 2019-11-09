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
use Cappuccino\Error\Error;
use Cappuccino\Node\Node;

/**
 * Interface NodeVisitorInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\NodeVisitor
 * @since 1.0.0
 */
interface NodeVisitorInterface
{

	/**
	 * Called before child nodes are visited.
	 *
	 * @param Node       $node
	 * @param Cappuccino $cappuccino
	 *
	 * @return Node
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enterNode(Node $node, Cappuccino $cappuccino): Node;

	/**
	 * Called after child nodes are visited.
	 *
	 * @param Node       $node
	 * @param Cappuccino $cappuccino
	 *
	 * @return Node|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function leaveNode(Node $node, Cappuccino $cappuccino): ?Node;

	/**
	 * Returns the priority for this visitor.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPriority(): int;

}
