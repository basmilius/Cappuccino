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
use Cappuccino\Node\Node;
use ReflectionException;

/**
 * Class FilterExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
class FilterExpression extends CallExpression
{

	/**
	 * FilterExpression constructor.
	 *
	 * @param Node               $node
	 * @param ConstantExpression $filterName
	 * @param Node               $arguments
	 * @param int                $lineNumber
	 * @param string|null        $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(Node $node, ConstantExpression $filterName, Node $arguments, int $lineNumber, ?string $tag = null)
	{
		parent::__construct(['node' => $node, 'filter' => $filterName, 'arguments' => $arguments], [], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @throws ReflectionException
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function compile(Compiler $compiler): void
	{
		$name = $this->getNode('filter')->getAttribute('value');
		$filter = $compiler->getCappuccino()->getFilter($name);

		$this->setAttribute('name', $name);
		$this->setAttribute('type', 'filter');
		$this->setAttribute('needs_cappuccino', $filter->needsCappuccino());
		$this->setAttribute('needs_context', $filter->needsContext());
		$this->setAttribute('arguments', $filter->getArguments());
		$this->setAttribute('callable', $filter->getCallable());
		$this->setAttribute('is_variadic', $filter->isVariadic());

		$this->compileCallable($compiler);
	}

}
