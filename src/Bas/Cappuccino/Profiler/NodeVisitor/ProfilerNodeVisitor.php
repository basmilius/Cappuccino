<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Profiler\NodeVisitor;

use Bas\Cappuccino\Environment;
use Bas\Cappuccino\Node\BlockNode;
use Bas\Cappuccino\Node\BodyNode;
use Bas\Cappuccino\Node\MacroNode;
use Bas\Cappuccino\Node\ModuleNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\NodeVisitor\AbstractNodeVisitor;
use Bas\Cappuccino\Profiler\Node\EnterProfileNode;
use Bas\Cappuccino\Profiler\Node\LeaveProfileNode;
use Bas\Cappuccino\Profiler\Profile;

/**
 * Class ProfilerNodeVisitor
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Profiler\NodeVisitor
 * @version 2.3.0
 */
final class ProfilerNodeVisitor extends AbstractNodeVisitor
{

	/**
	 * @var string
	 */
	private $extensionName;

	/**
	 * ProfilerNodeVisitor constructor.
	 *
	 * @param string $extensionName
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (string $extensionName)
	{
		$this->extensionName = $extensionName;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function doEnterNode (Node $node, Environment $environment) : Node
	{
		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function doLeaveNode (Node $node, Environment $env) : Node
	{
		if ($node instanceof ModuleNode)
		{
			$varName = $this->getVarName();
			$node->setNode('display_start', new Node([new EnterProfileNode($this->extensionName, Profile::TEMPLATE, $node->getTemplateName(), $varName), $node->getNode('display_start')]));
			$node->setNode('display_end', new Node([new LeaveProfileNode($varName), $node->getNode('display_end')]));
		}
		else if ($node instanceof BlockNode)
		{
			$varName = $this->getVarName();
			$node->setNode('body', new BodyNode([
				new EnterProfileNode($this->extensionName, Profile::BLOCK, $node->getAttribute('name'), $varName),
				$node->getNode('body'),
				new LeaveProfileNode($varName),
			]));
		}
		else if ($node instanceof MacroNode)
		{
			$varName = $this->getVarName();
			$node->setNode('body', new BodyNode([
				new EnterProfileNode($this->extensionName, Profile::MACRO, $node->getAttribute('name'), $varName),
				$node->getNode('body'),
				new LeaveProfileNode($varName),
			]));
		}

		return $node;
	}

	/**
	 * Gets the var name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	private function getVarName () : string
	{
		return sprintf('__internal_%s', hash('sha256', uniqid(mt_rand(), true), false));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getPriority () : int
	{
		return 0;
	}

}
