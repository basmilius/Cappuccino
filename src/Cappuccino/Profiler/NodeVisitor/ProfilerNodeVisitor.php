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

namespace Cappuccino\Profiler\NodeVisitor;

use Cappuccino\Cappuccino;
use Cappuccino\Node\BlockNode;
use Cappuccino\Node\BodyNode;
use Cappuccino\Node\MacroNode;
use Cappuccino\Node\ModuleNode;
use Cappuccino\Node\Node;
use Cappuccino\NodeVisitor\NodeVisitorInterface;
use Cappuccino\Profiler\Node\EnterProfileNode;
use Cappuccino\Profiler\Node\LeaveProfileNode;
use Cappuccino\Profiler\Profile;
use function hash;
use function sprintf;

/**
 * Class ProfilerNodeVisitor
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler\NodeVisitor
 * @since 1.0.0
 */
final class ProfilerNodeVisitor implements NodeVisitorInterface
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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $extensionName)
	{
		$this->extensionName = $extensionName;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enterNode(Node $node, Cappuccino $env): Node
	{
		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function leaveNode(Node $node, Cappuccino $env): ?Node
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
	 * Gets the variable name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function getVarName(): string
	{
		return sprintf('__internal_%s', hash('sha256', $this->extensionName));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPriority(): int
	{
		return 0;
	}

}
