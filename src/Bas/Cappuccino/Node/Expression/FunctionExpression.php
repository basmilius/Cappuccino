<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Util\StaticMethods;

/**
 * Class FunctionExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 1.0.0
 */
class FunctionExpression extends CallExpression
{

	/**
	 * FunctionExpression constructor.
	 *
	 * @param string $name
	 * @param Node   $arguments
	 * @param int    $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $name, Node $arguments, int $lineno)
	{
		parent::__construct(['arguments' => $arguments], ['name' => $name, 'is_defined_test' => false], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$name = $this->getAttribute('name');
		$function = $compiler->getEnvironment()->getFunction($name);

		$this->setAttribute('name', $name);
		$this->setAttribute('type', 'function');
		$this->setAttribute('needs_environment', $function->needsEnvironment());
		$this->setAttribute('needs_context', $function->needsContext());
		$this->setAttribute('arguments', $function->getArguments());

		$callable = $function->getCallable();

		if ($name === 'constant' && $this->getAttribute('is_defined_test'))
			$callable = [StaticMethods::class, 'isConstantDefined'];

		$this->setAttribute('callable', $callable);
		$this->setAttribute('is_variadic', $function->isVariadic());

		$this->compileCallable($compiler);
	}

}
