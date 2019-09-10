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

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;

/**
 * Class MethodCallExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
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
	 * @param int                $lineNumber
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $node, string $method, ArrayExpression $arguments, int $lineNumber)
	{
		parent::__construct(['node' => $node, 'arguments' => $arguments], ['method' => $method, 'safe' => false, 'is_defined_test' => false], $lineNumber);

		if ($node instanceof NameExpression)
			$node->setAttribute('always_defined', true);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		if ($this->getAttribute('is_defined_test'))
		{
			$compiler
				->raw('method_exists($macros[')
				->repr($this->getNode('node')->getAttribute('name'))
				->raw('], ')
				->repr($this->getAttribute('method'))
				->raw(')');

			return;
		}

		$compiler
			->raw('StaticMethods::callMacro($macros[')
			->repr($this->getNode('node')->getAttribute('name'))
			->raw('], ')
			->repr($this->getAttribute('method'))
			->raw(', [');

		$first = true;
		/** @var ArrayExpression $nodes */
		$nodes = $this->getNode('arguments');

		foreach ($nodes->getKeyValuePairs() as $pair)
		{
			if (!$first)
				$compiler->raw(', ');

			$first = false;

			$compiler->subcompile($pair['value']);
		}

		$compiler
			->raw('], ')
			->repr($this->getTemplateLine())
			->raw(', $context, $this->getSourceContext())');
	}

}
