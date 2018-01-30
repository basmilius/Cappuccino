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

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class MethodCallExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @since 1.0.0
 */
class MethodCallExpression extends AbstractExpression
{

	/**
	 * MethodCallExpression constructor.
	 *
	 * @param AbstractExpression $node
	 * @param string             $method
	 * @param ArrayExpression    $arguments
	 * @param int                $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (AbstractExpression $node, string $method, ArrayExpression $arguments, int $lineno)
	{
		parent::__construct(['node' => $node, 'arguments' => $arguments], ['method' => $method, 'safe' => false], $lineno);

		if ($node instanceof NameExpression)
			$node->setAttribute('always_defined', true);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->subcompile($this->getNode('node'))->raw('->')->raw($this->getAttribute('method'))->raw('(');
		$first = true;

		/** @var ArrayExpression $arguments */
		$arguments = $this->getNode('arguments');

		foreach ($arguments->getKeyValuePairs() as $pair)
		{
			if (!$first)
			{
				$compiler->raw(', ');
			}
			$first = false;

			$compiler->subcompile($pair['value']);
		}
		$compiler->raw(')');
	}

}
