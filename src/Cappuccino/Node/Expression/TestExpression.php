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
 * Class TestExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
class TestExpression extends CallExpression
{

	/**
	 * TestExpression constructor.
	 *
	 * @param Node      $node
	 * @param string    $name
	 * @param Node|null $arguments
	 * @param int       $lineNumber
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(Node $node, string $name, Node $arguments = null, int $lineNumber = 0)
	{
		$nodes = ['node' => $node];

		if ($arguments !== null)
			$nodes['arguments'] = $arguments;

		parent::__construct($nodes, ['name' => $name], $lineNumber);
	}

	/**
	 * {@inheritdoc}
	 * @throws ReflectionException
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function compile(Compiler $compiler): void
	{
		$name = $this->getAttribute('name');
		$test = $compiler->getCappuccino()->getTest($name);

		$this->setAttribute('name', $name);
		$this->setAttribute('type', 'test');
		$this->setAttribute('arguments', $test->getArguments());
		$this->setAttribute('callable', $test->getCallable());
		$this->setAttribute('is_variadic', $test->isVariadic());

		$this->compileCallable($compiler);
	}

}
