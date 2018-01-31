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

namespace Bas\Cappuccino\Node\Expression\Test;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\CallExpression;
use Bas\Cappuccino\Node\Node;

/**
 * Class TestExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
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
	 * @param int       $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Node $node, string $name, Node $arguments = null, int $lineno)
	{
		$nodes = ['node' => $node];
		if (null !== $arguments)
		{
			$nodes['arguments'] = $arguments;
		}

		parent::__construct($nodes, ['name' => $name], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$name = $this->getAttribute('name');
		$test = $compiler->getCappuccino()->getTest($name);

		$this->setAttribute('name', $name);
		$this->setAttribute('type', 'test');
		$this->setAttribute('callable', $test->getCallable());
		$this->setAttribute('is_variadic', $test->isVariadic());

		$this->compileCallable($compiler);
	}

}
