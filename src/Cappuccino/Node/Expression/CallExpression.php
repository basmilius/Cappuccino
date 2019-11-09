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
use Cappuccino\Error\SyntaxError;
use Cappuccino\Extension\ExtensionInterface;
use Cappuccino\Node\Node;
use Cappuccino\Util\StaticMethods;
use Closure;
use LogicException;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_shift;
use function count;
use function end;
use function get_class;
use function implode;
use function is_array;
use function is_int;
use function is_string;
use function ltrim;
use function method_exists;
use function preg_replace;
use function sprintf;
use function strpos;
use function substr;
use function ucfirst;

/**
 * Class CallExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
abstract class CallExpression extends AbstractExpression
{

	/**
	 * @var array|null
	 */
	private $reflector;

	/**
	 * Compiles the callable.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws ReflectionException
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileCallable(Compiler $compiler): void
	{
		$callable = $this->getAttribute('callable');

		$closingParenthesis = false;
		$isArray = false;

		if (is_string($callable) && strpos($callable, '::') === false)
		{
			$compiler->raw($callable);
		}
		else
		{
			[$r, $callable] = $this->reflectCallable($callable);

			if ($r instanceof ReflectionMethod && is_string($callable[0]))
			{
				if ($r->isStatic())
					$compiler->raw(sprintf('%s::%s', $callable[0] === StaticMethods::class ? 'StaticMethods' : $callable[0], $callable[1]));
				else
					$compiler->raw(sprintf('$this->cappuccino->getRuntime(\'%s\')->%s', $callable[0], $callable[1]));
			}
			else if ($r instanceof ReflectionMethod && $callable[0] instanceof ExtensionInterface)
			{
				$class = get_class($callable[0]);

				if (!$compiler->getCappuccino()->hasExtension($class))
					$compiler->raw(sprintf('$this->cappuccino->getExtension(\'%s\')', $class));
				else
					$compiler->raw(sprintf('$this->extensions[\'%s\']', ltrim($class, '\\')));

				$compiler->raw(sprintf('->%s', $callable[1]));
			}
			else
			{
				$closingParenthesis = true;
				$isArray = true;
				$compiler->raw(sprintf('call_user_func_array($this->cappuccino->get%s(\'%s\')->getCallable(), ', ucfirst($this->getAttribute('type')), $this->getAttribute('name')));
			}
		}

		$this->compileArguments($compiler, $isArray);

		if ($closingParenthesis)
			$compiler->raw(')');
	}

	/**
	 * Compiles the arguments.
	 *
	 * @param Compiler $compiler
	 * @param bool     $isArray
	 *
	 * @throws ReflectionException
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function compileArguments(Compiler $compiler, $isArray = false): void
	{
		$compiler->raw($isArray ? '[' : '(');

		$first = true;

		if ($this->hasAttribute('needs_cappuccino') && $this->getAttribute('needs_cappuccino'))
		{
			$compiler->raw('$this->cappuccino');
			$first = false;
		}

		if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context'))
		{
			if (!$first)
				$compiler->raw(', ');

			$compiler->raw('$context');
			$first = false;
		}

		if ($this->hasAttribute('arguments'))
		{
			foreach ($this->getAttribute('arguments') as $argument)
			{
				if (!$first)
					$compiler->raw(', ');

				$compiler->string($argument);
				$first = false;
			}
		}

		if ($this->hasNode('node'))
		{
			if (!$first)
				$compiler->raw(', ');

			$compiler->subcompile($this->getNode('node'));
			$first = false;
		}

		if ($this->hasNode('arguments'))
		{
			$callable = $this->getAttribute('callable');
			$arguments = $this->getArguments($callable, $this->getNode('arguments'));

			foreach ($arguments as $node)
			{
				if (!$first)
					$compiler->raw(', ');

				$compiler->subcompile($node);
				$first = false;
			}
		}

		$compiler->raw($isArray ? ']' : ')');
	}

	/**
	 * Gets arguments.
	 *
	 * @param callable|object $callable
	 * @param array           $arguments
	 *
	 * @return array
	 * @throws ReflectionException
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function getArguments($callable, $arguments = []): array
	{
		$callType = $this->getAttribute('type');
		$callName = $this->getAttribute('name');

		$parameters = [];
		$named = false;

		foreach ($arguments as $name => $node)
		{
			if (!is_int($name))
			{
				$named = true;
				$name = $this->normalizeName($name);
			}
			else if ($named)
			{
				throw new SyntaxError(sprintf('Positional arguments cannot be used after named arguments for %s "%s".', $callType, $callName), $this->getTemplateLine(), $this->getSourceContext());
			}

			$parameters[$name] = $node;
		}

		$isVariadic = $this->hasAttribute('is_variadic') && $this->getAttribute('is_variadic');

		if (!$named && !$isVariadic)
			return $parameters;

		if (!$callable)
		{
			if ($named)
				$message = sprintf('Named arguments are not supported for %s "%s".', $callType, $callName);
			else
				$message = sprintf('Arbitrary positional arguments are not supported for %s "%s".', $callType, $callName);

			throw new LogicException($message);
		}

		[$callableParameters, $isPhpVariadic] = $this->getCallableParameters($callable, $isVariadic);
		$arguments = [];
		$names = [];
		$missingArguments = [];
		$optionalArguments = [];
		$pos = 0;

		foreach ($callableParameters as $callableParameter)
		{
			$names[] = $name = $this->normalizeName($callableParameter->name);

			if (isset($parameters[$name]))
			{
				if (isset($parameters[$pos]))
					throw new SyntaxError(sprintf('Argument "%s" is defined twice for %s "%s".', $name, $callType, $callName), $this->getTemplateLine(), $this->getSourceContext());

				if (count($missingArguments))
					throw new SyntaxError(sprintf('Argument "%s" could not be assigned for %s "%s(%s)" because it is mapped to an internal PHP function which cannot determine default value for optional argument%s "%s".', $name, $callType, $callName, implode(', ', $names), count($missingArguments) > 1 ? 's' : '', implode('", "', $missingArguments)), $this->getTemplateLine(), $this->getSourceContext());

				$arguments = array_merge($arguments, $optionalArguments);
				$arguments[] = $parameters[$name];
				unset($parameters[$name]);
				$optionalArguments = [];
			}
			else if (array_key_exists($pos, $parameters))
			{
				$arguments = array_merge($arguments, $optionalArguments);
				$arguments[] = $parameters[$pos];
				unset($parameters[$pos]);
				$optionalArguments = [];
				++$pos;
			}
			else if ($callableParameter->isDefaultValueAvailable())
			{
				$optionalArguments[] = new ConstantExpression($callableParameter->getDefaultValue(), -1);
			}
			else if ($callableParameter->isOptional())
			{
				if (empty($parameters))
					break;
				else
					$missingArguments[] = $name;
			}
			else
			{
				throw new SyntaxError(sprintf('Value for argument "%s" is required for %s "%s".', $name, $callType, $callName), $this->getTemplateLine(), $this->getSourceContext());
			}
		}

		if ($isVariadic)
		{
			$arbitraryArguments = $isPhpVariadic ? new VariadicExpression([], -1) : new ArrayExpression([], -1);

			foreach ($parameters as $key => $value)
			{
				if (is_int($key))
					$arbitraryArguments->addElement($value);
				else
					$arbitraryArguments->addElement($value, new ConstantExpression($key, -1));

				unset($parameters[$key]);
			}

			if ($arbitraryArguments->count())
			{
				$arguments = array_merge($arguments, $optionalArguments);
				$arguments[] = $arbitraryArguments;
			}
		}

		if (!empty($parameters))
		{
			$unknownParameter = null;

			foreach ($parameters as $parameter)
			{
				if ($parameter instanceof Node)
				{
					$unknownParameter = $parameter;
					break;
				}
			}

			throw new SyntaxError(sprintf('Unknown argument%s "%s" for %s "%s(%s)".', count($parameters) > 1 ? 's' : '', implode('", "', array_keys($parameters)), $callType, $callName, implode(', ', $names)), $unknownParameter ? $unknownParameter->getTemplateLine() : $this->getTemplateLine(), $unknownParameter ? $unknownParameter->getSourceContext() : $this->getSourceContext());
		}

		return $arguments;
	}

	/**
	 * Normalizes the given name.
	 *
	 * @param string $name
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function normalizeName(string $name): string
	{
		return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $name));
	}

	/**
	 * Gets parameters of a callable.
	 *
	 * @param callable|object $callable
	 * @param bool            $isVariadic
	 *
	 * @return array
	 * @throws ReflectionException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getCallableParameters($callable, bool $isVariadic): array
	{
		[$r] = $this->reflectCallable($callable);

		if ($r === null)
			return [[], false];

		$parameters = $r->getParameters();

		if ($this->hasNode('node'))
			array_shift($parameters);

		if ($this->hasAttribute('needs_cappuccino') && $this->getAttribute('needs_cappuccino'))
			array_shift($parameters);

		if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context'))
			array_shift($parameters);

		if ($this->hasAttribute('arguments') && null !== $this->getAttribute('arguments'))
			/** @noinspection PhpUnusedLocalVariableInspection */
			foreach ($this->getAttribute('arguments') as $argument)
				array_shift($parameters);

		$isPhpVariadic = false;

		if ($isVariadic)
		{
			$argument = end($parameters);

			if ($argument && $argument->isArray() && $argument->isDefaultValueAvailable() && [] === $argument->getDefaultValue())
			{
				array_pop($parameters);
			}
			else if ($argument && $argument->isVariadic())
			{
				array_pop($parameters);
				$isPhpVariadic = true;
			}
			else
			{
				$callableName = $r->name;

				if ($r instanceof ReflectionMethod)
					$callableName = $r->getDeclaringClass()->name . '::' . $callableName;

				throw new LogicException(sprintf('The last parameter of "%s" for %s "%s" must be an array with default value, eg. "array $arg = []".', $callableName, $this->getAttribute('type'), $this->getAttribute('name')));
			}
		}

		return [$parameters, $isPhpVariadic];
	}

	/**
	 * Gets the reflector.
	 *
	 * @param callable|object $callable
	 *
	 * @return array
	 * @throws ReflectionException
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function reflectCallable($callable): array
	{
		if ($this->reflector !== null)
			return $this->reflector;

		if (is_array($callable))
		{
			if (!method_exists($callable[0], $callable[1]))
				return [null, []];

			$r = new ReflectionMethod($callable[0], $callable[1]);
		}
		else if (is_object($callable) && !$callable instanceof Closure)
		{
			$r = new ReflectionObject($callable);
			$r = $r->getMethod('__invoke');
			$callable = [$callable, '__invoke'];
		}
		else if (is_string($callable) && ($pos = strpos($callable, '::')) !== false)
		{
			$class = substr($callable, 0, $pos);
			$method = substr($callable, $pos + 2);

			if (!method_exists($class, $method))
				return [null, []];

			$r = new ReflectionMethod($callable);
			$callable = [$class, $method];
		}
		else
		{
			$r = new ReflectionFunction($callable);
		}

		return $this->reflector = [$r, $callable];
	}

}
