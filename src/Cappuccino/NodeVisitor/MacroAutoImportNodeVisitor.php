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
use Cappuccino\Node\Expression\ArrayExpression;
use Cappuccino\Node\Expression\AssignNameExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\GetAttrExpression;
use Cappuccino\Node\Expression\MethodCallExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\ImportNode;
use Cappuccino\Node\ModuleNode;
use Cappuccino\Node\Node;

/**
 * Class MacroAutoImportNodeVisitor
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\NodeVisitor
 * @since 2.0.0
 */
final class MacroAutoImportNodeVisitor implements NodeVisitorInterface
{

	/**
	 * @var bool
	 */
	private $inAModule = false;

	/**
	 * @var bool
	 */
	private $hasMacroCalls = false;

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function enterNode(Node $node, Cappuccino $env): Node
	{
		if ($node instanceof ModuleNode)
		{
			$this->inAModule = true;
			$this->hasMacroCalls = false;
		}

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function leaveNode(Node $node, Cappuccino $env): Node
	{
		if ($node instanceof ModuleNode)
		{
			$this->inAModule = false;

			if ($this->hasMacroCalls)
				$node->getNode('constructor_end')->setNode('_auto_macro_import', new ImportNode(new NameExpression('_self', 0), new AssignNameExpression('_self', 0), 0, 'import', true));
		}
		else if ($this->inAModule)
		{
			if ($node instanceof GetAttrExpression && $node->getNode('node') instanceof NameExpression && '_self' === $node->getNode('node')->getAttribute('name') && $node->getNode('attribute') instanceof ConstantExpression)
			{
				$this->hasMacroCalls = true;

				/** @var ArrayExpression $arguments */
				$arguments = $node->getNode('arguments');

				$name = $node->getNode('attribute')->getAttribute('value');
				$node = new MethodCallExpression($node->getNode('node'), 'macro_' . $name, $arguments, $node->getTemplateLine());
				$node->setAttribute('safe', true);
			}
		}

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function getPriority(): int
	{
		return -10;
	}

}
