<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Node\Node;

/**
 * Interface NodeVisitorInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @since 2.3.0
 */
interface NodeVisitorInterface
{

	/**
	 * Called before child nodes are visited.
	 *
	 * @param Node        $node
	 * @param Environment $env
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function enterNode (Node $node, Environment $env) : Node;

	/**
	 * Called after child nodes are visited.
	 *
	 * @param Node        $node
	 * @param Environment $env
	 *
	 * @return Node
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function leaveNode (Node $node, Environment $env) : Node;

	/**
	 * Returns the priority for this visitor.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getPriority () : int;

}
