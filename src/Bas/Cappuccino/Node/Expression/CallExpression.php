<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Closure;
use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Extension\ExtensionInterface;
use Bas\Cappuccino\Node\Node;
use LogicException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;

/**
 * Class CallExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 2.3.0
 */
abstract class CallExpression extends AbstractExpression
{

	private $reflector;

	/**
	 * Compiles a callable.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function compileCallable (Compiler $compiler) : void
	{
		$callable = $this->getAttribute('callable');

		$closingParenthesis = false;

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
					$compiler->raw(sprintf('%s::%s', $callable[0], $callable[1]));
				else
					$compiler->raw(sprintf('$this->environment->getRuntime(\'%s\')->%s', $callable[0], $callable[1]));
			}
			else if ($r instanceof ReflectionMethod && $callable[0] instanceof ExtensionInterface)
			{
				$compiler->raw(sprintf('$this->environment->getExtension(\'%s\')->%s', get_class($callable[0]), $callable[1]));
			}
			else
			{
				$closingParenthesis = true;
				$compiler->raw(sprintf('call_user_func_array($this->environment->get%s(\'%s\')->getCallable(), array', ucfirst($this->getAttribute('type')), $this->getAttribute('name')));
			}
		}

		$this->compileArguments($compiler);

		if ($closingParenthesis)
			$compiler->raw(')');
	}

	/**
	 * Compiles arguments.
	 *
	 * @param Compiler $compiler
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function compileArguments (Compiler $compiler) : void
	{
		$compiler->raw('(');

		$first = true;

		if ($this->hasAttribute('needs_environment') && $this->getAttribute('needs_environment'))
		{
			$compiler->raw('$this->environment');
			$first = false;
		}

		if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context'))
		{
			if (!$first)
			{
				$compiler->raw(', ');
			}
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

		$compiler->raw(')');
	}

	/**
	 * Gets arguments.
	 *
	 * @param callable|null $callable
	 * @param mixed         $arguments
	 *
	 * @return array
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function getArguments (?callable $callable = null, $arguments) : array
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
				throw new SyntaxError(sprintf('Positional arguments cannot be used after named arguments for %s "%s".', $callType, $callName));
			}

			$parameters[$name] = $node;
		}

		$isVariadic = $this->hasAttribute('is_variadic') && $this->getAttribute('is_variadic');
		if (!$named && !$isVariadic)
		{
			return $parameters;
		}

		if (!$callable)
		{
			if ($named)
			{
				$message = sprintf('Named arguments are not supported for %s "%s".', $callType, $callName);
			}
			else
			{
				$message = sprintf('Arbitrary positional arguments are not supported for %s "%s".', $callType, $callName);
			}

			throw new LogicException($message);
		}

		/** @var ReflectionParameter[] $callableParameters */
		$callableParameters = $this->getCallableParameters($callable, $isVariadic);
		$arguments = [];
		$names = [];
		$missingArguments = [];
		$optionalArguments = [];
		$pos = 0;

		foreach ($callableParameters as $callableParameter)
		{
			$names[] = $name = $this->normalizeName($callableParameter->name);

			if (array_key_exists($name, $parameters))
			{
				if (array_key_exists($pos, $parameters))
				{
					throw new SyntaxError(sprintf('Argument "%s" is defined twice for %s "%s".', $name, $callType, $callName));
				}

				if (count($missingArguments))
				{
					throw new SyntaxError(sprintf(
							'Argument "%s" could not be assigned for %s "%s(%s)" because it is mapped to an internal PHP function which cannot determine default value for optional argument%s "%s".',
							$name, $callType, $callName, implode(', ', $names), count($missingArguments) > 1 ? 's' : '', implode('", "', $missingArguments))
					);
				}

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
				{
					break;
				}
				else
				{
					$missingArguments[] = $name;
				}
			}
			else
			{
				throw new SyntaxError(sprintf('Value for argument "%s" is required for %s "%s".', $name, $callType, $callName));
			}
		}

		if ($isVariadic)
		{
			$arbitraryArguments = new ArrayExpression([], -1);
			foreach ($parameters as $key => $value)
			{
				if (is_int($key))
				{
					$arbitraryArguments->addElement($value);
				}
				else
				{
					$arbitraryArguments->addElement($value, new ConstantExpression($key, -1));
				}
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

			throw new SyntaxError(sprintf(
				'Unknown argument%s "%s" for %s "%s(%s)".',
				count($parameters) > 1 ? 's' : '', implode('", "', array_keys($parameters)), $callType, $callName, implode(', ', $names)
			), $unknownParameter ? $unknownParameter->getTemplateLine() : -1);
		}

		return $arguments;
	}

	/**
	 * Normalizes the name.
	 *
	 * @param string $name
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function normalizeName (string $name) : string
	{
		return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $name));
	}

	/**
	 * Gets callable parameters.
	 *
	 * @param string|string $callable
	 * @param bool          $isVariadic
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	private function getCallableParameters (?string $callable, bool $isVariadic) : array
	{
		[$r] = $this->reflectCallable($callable);

		if ($r === null)
			return [];

		$parameters = $r->getParameters();

		if ($this->hasNode('node'))
		{
			array_shift($parameters);
		}

		if ($this->hasAttribute('needs_environment') && $this->getAttribute('needs_environment'))
		{
			array_shift($parameters);
		}

		if ($this->hasAttribute('needs_context') && $this->getAttribute('needs_context'))
		{
			array_shift($parameters);
		}

		if ($this->hasAttribute('arguments') && null !== $this->getAttribute('arguments'))
		{
			/** @noinspection PhpUnusedLocalVariableInspection */
			foreach ($this->getAttribute('arguments') as $argument)
				array_shift($parameters);
		}

		if ($isVariadic)
		{
			$argument = end($parameters);

			if ($argument && $argument->isArray() && $argument->isDefaultValueAvailable() && [] === $argument->getDefaultValue())
			{
				array_pop($parameters);
			}
			else
			{
				$callableName = $r->name;

				if ($r instanceof ReflectionMethod)
					$callableName = $r->getDeclaringClass()->name . '::' . $callableName;

				throw new LogicException(sprintf('The last parameter of "%s" for %s "%s" must be an array with default value, eg. "array $arg = array()".', $callableName, $this->getAttribute('type'), $this->getAttribute('name')));
			}
		}

		return $parameters;
	}

	/**
	 * Reflect callable.
	 *
	 * @param mixed $callable
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	private function reflectCallable ($callable) : array
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
		else if (is_string($callable) && false !== $pos = strpos($callable, '::'))
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
