<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Util;

use ArrayAccess;
use BadMethodCallException;
use Bas\Cappuccino\Cappuccino;
use Bas\Cappuccino\Error\RuntimeError;
use Bas\Cappuccino\Extension\SandboxExtension;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedMethodError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedPropertyError;
use Bas\Cappuccino\Source;
use Bas\Cappuccino\Template;
use Countable;
use Traversable;

/**
 * Class StaticMethods
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Util
 * @version ${CARET}
 */
class StaticMethods
{

	/**
	 * Converts the encoding of a string.
	 *
	 * @param string $str
	 * @param string $to
	 * @param string $from
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function convertEncoding (string $str, string $to, string $from) : string
	{
		return iconv($from, $to, $str);
	}

	/**
	 * Ensures traversable.
	 *
	 * @param $seq
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function ensureTraversable ($seq)
	{
		if ($seq instanceof Traversable || is_array($seq))
		{
			return $seq;
		}

		return [];
	}

	/**
	 * Returns the attribute value for a given array/object.
	 *
	 * @param Cappuccino $env
	 * @param Source     $source
	 * @param mixed      $object
	 * @param mixed      $item
	 * @param array      $arguments
	 * @param string     $type
	 * @param bool       $isDefinedTest
	 * @param bool       $ignoreStrictCheck
	 *
	 * @return mixed
	 * @throws RuntimeError
	 * @throws SecurityNotAllowedMethodError
	 * @throws SecurityNotAllowedPropertyError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function getAttribute (Cappuccino $env, Source $source, $object, $item, array $arguments = [], $type = Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
	{
		static $cache = [];

		if (Template::METHOD_CALL !== $type)
		{
			$arrayItem = is_bool($item) || is_float($item) ? (int)$item : $item;

			if ((is_array($object) && (isset($object[$arrayItem]) || array_key_exists($arrayItem, $object))) || ($object instanceof ArrayAccess && isset($object[$arrayItem])))
			{
				if ($isDefinedTest)
					return true;

				return $object[$arrayItem];
			}

			if (Template::ARRAY_CALL === $type || !is_object($object))
			{
				if ($isDefinedTest)
					return false;

				if ($ignoreStrictCheck || !$env->isStrictVariables())
					return null;

				if ($object instanceof ArrayAccess)
				{
					$message = sprintf('Key "%s" in object with ArrayAccess of class "%s" does not exist.', $arrayItem, get_class($object));
				}
				else if (is_object($object))
				{
					$message = sprintf('Impossible to access a key "%s" on an object of class "%s" that does not implement ArrayAccess interface.', $item, get_class($object));
				}
				else if (is_array($object))
				{
					if (empty($object))
						$message = sprintf('Key "%s" does not exist as the array is empty.', $arrayItem);
					else
						$message = sprintf('Key "%s" for array with keys "%s" does not exist.', $arrayItem, implode(', ', array_keys($object)));
				}
				else if (Template::ARRAY_CALL === $type)
				{
					if (null === $object)
						$message = sprintf('Impossible to access a key ("%s") on a null variable.', $item);
					else
						$message = sprintf('Impossible to access a key ("%s") on a %s variable ("%s").', $item, gettype($object), $object);
				}
				else if (null === $object)
				{
					$message = sprintf('Impossible to access an attribute ("%s") on a null variable.', $item);
				}
				else
				{
					$message = sprintf('Impossible to access an attribute ("%s") on a %s variable ("%s").', $item, gettype($object), $object);
				}

				throw new RuntimeError($message, -1, $source);
			}
		}

		if (!is_object($object))
		{
			if ($isDefinedTest)
				return false;

			if ($ignoreStrictCheck || !$env->isStrictVariables())
				return null;

			if ($object === null)
				$message = sprintf('Impossible to invoke a method ("%s") on a null variable.', $item);
			else
				$message = sprintf('Impossible to invoke a method ("%s") on a %s variable ("%s").', $item, gettype($object), $object);

			throw new RuntimeError($message, -1, $source);
		}

		if ($object instanceof Template)
			throw new RuntimeError('Accessing Template attributes is forbidden.');

		if (Template::METHOD_CALL !== $type)
		{
			if (isset($object->{$item}) || array_key_exists((string)$item, $object))
			{
				if ($isDefinedTest)
					return true;

				if ($env->hasExtension(SandboxExtension::class))
				{
					/** @var SandboxExtension $ext */
					$ext = $env->getExtension(SandboxExtension::class);
					$ext->checkPropertyAllowed($object, (string)$item);
				}

				return $object->{$item};
			}
		}

		$class = get_class($object);

		if (!isset($cache[$class]))
		{
			$methods = get_class_methods($object);
			sort($methods);
			$lcMethods = array_map('strtolower', $methods);
			$classCache = [];

			/** @var string $method */
			foreach ($methods as $i => $method)
			{
				$classCache[$method] = $method;
				$classCache[$lcName = $lcMethods[$i]] = $method;

				if ('g' === $lcName[0] && 0 === strpos($lcName, 'get'))
				{
					$name = substr($method, 3);
					$lcName = substr($lcName, 3);
				}
				else if ('i' === $lcName[0] && 0 === strpos($lcName, 'is'))
				{
					$name = substr($method, 2);
					$lcName = substr($lcName, 2);
				}
				else if ('h' === $lcName[0] && 0 === strpos($lcName, 'has'))
				{
					$name = substr($method, 3);
					$lcName = substr($lcName, 3);

					if (in_array('is' . $lcName, $lcMethods))
						continue;
				}
				else
				{
					continue;
				}

				if ($name)
				{
					if (!isset($classCache[$name]))
						$classCache[$name] = $method;

					if (!isset($classCache[$lcName]))
						$classCache[$lcName] = $method;
				}
			}
			$cache[$class] = $classCache;
		}

		$call = false;
		if (isset($cache[$class][$item]))
		{
			$method = $cache[$class][$item];
		}
		else if (isset($cache[$class][$lcItem = strtolower($item)]))
		{
			$method = $cache[$class][$lcItem];
		}
		else if (isset($cache[$class]['__call']))
		{
			$method = $item;
			$call = true;
		}
		else
		{
			if ($isDefinedTest)
				return false;

			if ($ignoreStrictCheck || !$env->isStrictVariables())
				return null;

			throw new RuntimeError(sprintf('Neither the property "%1$s" nor one of the methods "%1$s()", "get%1$s()"/"is%1$s()"/"has%1$s()" or "__call()" exist and have public access in class "%2$s".', $item, $class), -1, $source);
		}

		if ($isDefinedTest)
			return true;

		if ($env->hasExtension(SandboxExtension::class))
		{
			/** @var SandboxExtension $ext */
			$ext = $env->getExtension(SandboxExtension::class);
			$ext->checkMethodAllowed($object, $method);
		}

		try
		{
			$ret = $object->$method(...$arguments);
		}
		catch (BadMethodCallException $e)
		{
			if ($call && ($ignoreStrictCheck || !$env->isStrictVariables()))
				return null;

			throw $e;
		}

		return $ret;
	}

	/**
	 * Returns TRUE if in filter.
	 *
	 * @param $value
	 * @param $compare
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function inFilter ($value, $compare) : bool
	{
		if (is_array($compare))
		{
			return in_array($value, $compare, is_object($value) || is_resource($value));
		}
		else if (is_string($compare) && (is_string($value) || is_int($value) || is_float($value)))
		{
			return $value === '' || strpos($compare, (string)$value);
		}
		else if ($compare instanceof Traversable)
		{
			if (is_object($value) || is_resource($value))
			{
				foreach ($compare as $item)
					if ($item === $value)
						return true;
			}
			else
			{
				foreach ($compare as $item)
					if ($item == $value)
						return true;
			}

			return false;
		}

		return false;
	}

	/**
	 * Returns TRUE if a constant exists.
	 *
	 * @param string $constant
	 * @param mixed  $object
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function isConstantDefined (string $constant, $object) : bool
	{
		if ($object !== null)
			$constant = get_class($object) . '::' . $constant;

		return defined($constant);
	}

	/**
	 * Checks if a variable is empty.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function isEmpty ($value) : bool
	{
		if ($value instanceof Countable)
			return count($value) === 0;

		if (is_object($value) && method_exists($value, '__toString'))
			return (string)$value === '';

		return $value === '' || !$value || $value === null || $value === [];
	}

	/**
	 * Returns TRUE if the value is traversable.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function isIterable ($value) : bool
	{
		return $value instanceof Traversable || is_array($value);
	}

}
