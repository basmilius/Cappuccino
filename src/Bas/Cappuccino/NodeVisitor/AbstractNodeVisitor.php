<?php
declare(strict_types=1);

namespace Bas\Cappuccino\NodeVisitor;

use Bas\Cappuccino\Environment;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\NodeVisitorInterface;

/**
 * Class AbstractNodeVisitor
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino
 * @since 2.3.0
 */
abstract class AbstractNodeVisitor implements NodeVisitorInterface
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	final public function enterNode (Node $node, Environment $env) : Node
	{
		return $this->doEnterNode($node, $env);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	final public function leaveNode (Node $node, Environment $env) : Node
	{
		return $this->doLeaveNode($node, $env);
	}

	/**
	 * Does enterMode function.
	 *
	 * @param Node        $node
	 * @param Environment $environment
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @see AbstractNodeVisitor::enterNode()
	 * @since 2.3.0
	 */
	protected abstract function doEnterNode (Node $node, Environment $environment) : Node;

	/**
	 * Does leaveMode function.
	 *
	 * @param Node        $node
	 * @param Environment $env
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @see AbstractNodeVisitor::leaveNode()
	 * @since 2.3.0
	 */
	protected abstract function doLeaveNode (Node $node, Environment $env) : Node;

}

