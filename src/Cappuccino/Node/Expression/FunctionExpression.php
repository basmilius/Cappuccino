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
use Cappuccino\Util\StaticMethods;
use ReflectionException;

/**
 * Class FunctionExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
class FunctionExpression extends CallExpression
{

	/**
	 * FunctionExpression constructor.
	 *
	 * @param string $name
	 * @param Node   $arguments
	 * @param int    $lineNumber
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $name, Node $arguments, int $lineNumber)
	{
		parent::__construct(['arguments' => $arguments], ['name' => $name, 'is_defined_test' => false], $lineNumber);
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
		$function = $compiler->getCappuccino()->getFunction($name);

		$this->setAttribute('name', $name);
		$this->setAttribute('type', 'function');
		$this->setAttribute('needs_cappuccino', $function->needsCappuccino());
		$this->setAttribute('needs_context', $function->needsContext());
		$this->setAttribute('arguments', $function->getArguments());
		$callable = $function->getCallable();

		if ($name === 'constant' && $this->getAttribute('is_defined_test'))
			$callable = [StaticMethods::class, 'constantIsDefined'];

		$this->setAttribute('callable', $callable);
		$this->setAttribute('is_variadic', $function->isVariadic());

		$this->compileCallable($compiler);
	}

}
