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

namespace Bas\Cappuccino;

use Bas\Cappuccino\Node\Node;

/**
 * Interface NodeVisitorInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @since 1.0.0
 */
interface NodeVisitorInterface
{

	/**
	 * Called before child nodes are visited.
	 *
	 * @param Node       $node
	 * @param Cappuccino $env
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enterNode (Node $node, Cappuccino $env): Node;

	/**
	 * Called after child nodes are visited.
	 *
	 * @param Node       $node
	 * @param Cappuccino $env
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function leaveNode (Node $node, Cappuccino $env): Node;

	/**
	 * Returns the priority for this visitor.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPriority (): int;

}
