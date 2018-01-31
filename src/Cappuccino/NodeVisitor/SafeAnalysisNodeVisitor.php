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
use Cappuccino\Node\Expression\BlockReferenceExpression;
use Cappuccino\Node\Expression\ConditionalExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\FilterExpression;
use Cappuccino\Node\Expression\FunctionExpression;
use Cappuccino\Node\Expression\GetAttrExpression;
use Cappuccino\Node\Expression\MethodCallExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\Expression\ParentExpression;
use Cappuccino\Node\Node;

/**
 * Class SafeAnalysisNodeVisitor
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\NodeVisitor
 * @since 1.0.0
 */
final class SafeAnalysisNodeVisitor extends AbstractNodeVisitor
{

	private $data = [];
	private $safeVars = [];

	/**
	 * Sets save vars.
	 *
	 * @param array $safeVars
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setSafeVars (array $safeVars)
	{
		$this->safeVars = $safeVars;
	}

	/**
	 * Gets save.
	 *
	 * @param Node $node
	 *
	 * @return array|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSafe (Node $node): ?array
	{
		$hash = spl_object_hash($node);

		if (!isset($this->data[$hash]))
			return null;

		foreach ($this->data[$hash] as $bucket)
		{
			if ($bucket['key'] !== $node)
				continue;

			if (!is_array($bucket['value']))
				$bucket['value'] = [$bucket['value']];

			if (in_array('html_attr', $bucket['value']))
				$bucket['value'][] = 'html';

			return $bucket['value'];
		}

		return null;
	}

	/**
	 * Sets save.
	 *
	 * @param Node        $node
	 * @param bool|bool[] $safe
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function setSafe (Node $node, $safe)
	{
		$hash = spl_object_hash($node);

		if (isset($this->data[$hash]))
			foreach ($this->data[$hash] as &$bucket)
				if ($bucket['key'] === $node)
				{
					$bucket['value'] = $safe;

					return;
				}

		$this->data[$hash][] = [
			'key' => $node,
			'value' => $safe,
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function doEnterNode (Node $node, Cappuccino $cappuccino): Node
	{
		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function doLeaveNode (Node $node, Cappuccino $env): Node
	{
		if ($node instanceof ConstantExpression)
		{
			$this->setSafe($node, ['all']);
		}
		else if ($node instanceof BlockReferenceExpression)
		{
			$this->setSafe($node, ['all']);
		}
		else if ($node instanceof ParentExpression)
		{
			$this->setSafe($node, ['all']);
		}
		else if ($node instanceof ConditionalExpression)
		{
			$safe = $this->intersectSafe($this->getSafe($node->getNode('expr2')), $this->getSafe($node->getNode('expr3')));
			$this->setSafe($node, $safe);
		}
		else if ($node instanceof FilterExpression)
		{
			$name = $node->getNode('filter')->getAttribute('value');
			$args = $node->getNode('arguments');

			if (false !== $filter = $env->getFilter($name))
			{
				$safe = $filter->getSafe($args);

				if ($safe === null)
					$safe = $this->intersectSafe($this->getSafe($node->getNode('node')), $filter->getPreservesSafety());

				$this->setSafe($node, $safe);
			}
			else
			{
				$this->setSafe($node, []);
			}
		}
		else if ($node instanceof FunctionExpression)
		{
			$name = $node->getAttribute('name');
			$args = $node->getNode('arguments');
			$function = $env->getFunction($name);

			if ($function !== false)
				$this->setSafe($node, $function->getSafe($args));
			else
				$this->setSafe($node, []);
		}
		else if ($node instanceof MethodCallExpression)
		{
			if ($node->getAttribute('safe'))
				$this->setSafe($node, ['all']);
			else
				$this->setSafe($node, []);
		}
		else if ($node instanceof GetAttrExpression && $node->getNode('node') instanceof NameExpression)
		{
			$name = $node->getNode('node')->getAttribute('name');

			if (in_array($name, $this->safeVars))
				$this->setSafe($node, ['all']);
			else
				$this->setSafe($node, []);
		}
		else
		{
			$this->setSafe($node, []);
		}

		return $node;
	}

	/**
	 * Intersect Save.
	 *
	 * @param array|null $a
	 * @param array|null $b
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function intersectSafe (array $a = null, array $b = null)
	{
		if (null === $a || null === $b)
			return [];

		if (in_array('all', $a))
			return $b;

		if (in_array('all', $b))
			return $a;

		return array_intersect($a, $b);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPriority (): int
	{
		return 0;
	}

}
