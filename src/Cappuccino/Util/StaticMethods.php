<?php
/** @noinspection PhpUnused */

/**
 * Copyright (c) 2019 - Bas Milius <bas@mili.us>
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\Util;

use ArrayAccess;
use ArrayObject;
use BadMethodCallException;
use CallbackFilterIterator;
use Cappuccino\Cappuccino;
use Cappuccino\Error\Error;
use Cappuccino\Error\LoaderError;
use Cappuccino\Error\RuntimeError;
use Cappuccino\Extension\CoreExtension;
use Cappuccino\Extension\SandboxExtension;
use Cappuccino\Markup;
use Cappuccino\Source;
use Cappuccino\Template;
use Countable;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use LimitIterator;
use OutOfBoundsException;
use SimpleXMLElement;
use Traversable;
use const ARRAY_FILTER_USE_BOTH;
use const MB_CASE_TITLE;
use const PHP_QUERY_RFC3986;
use function array_chunk;
use function array_column;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_rand;
use function array_reduce;
use function array_reverse;
use function array_slice;
use function array_values;
use function asort;
use function ceil;
use function constant;
use function count;
use function ctype_digit;
use function current;
use function defined;
use function explode;
use function get_class;
use function get_class_methods;
use function gettype;
use function http_build_query;
use function iconv;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_iterable;
use function is_nan;
use function is_numeric;
use function is_object;
use function is_resource;
use function is_scalar;
use function is_string;
use function iterator_count;
use function iterator_to_array;
use function ltrim;
use function mb_convert_case;
use function mb_strlen;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function method_exists;
use function mt_rand;
use function number_format;
use function pow;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function rawurlencode;
use function round;
use function rtrim;
use function sort;
use function sprintf;
use function strlen;
use function strpos;
use function strtolower;
use function strtr;
use function substr;
use function trim;
use function uasort;
use function ucwords;

/**
 * Class StaticMethods
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Util
 * @since 1.0.0
 * @internal
 */
final class StaticMethods
{

	/**
	 * {@see array_chunk()}.
	 *
	 * @param iterable $items
	 * @param int      $size
	 * @param null     $fill
	 * @param bool     $preserveKeys
	 *
	 * @return iterable
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function arrayBatch(iterable $items, int $size, $fill = null, bool $preserveKeys = true): iterable
	{
		if (!self::testIterable($items))
			throw new RuntimeError(sprintf('The "batch" filter expects an array or "Traversable", got "%s".', is_object($items) ? get_class($items) : gettype($items)));

		$size = ceil($size);
		$result = array_chunk(self::toArray($items, $preserveKeys), $size, $preserveKeys);

		if ($fill !== null && $result)
		{
			$last = count($result) - 1;

			if ($fillCount = $size - count($result[$last]))
				for ($i = 0; $i < $fillCount; ++$i)
					$result[$last][] = $fill;
		}

		return $result;
	}

	/**
	 * {@see array_column()}.
	 *
	 * @param iterable $array
	 * @param          $name
	 * @param null     $index
	 *
	 * @return iterable
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function arrayColumn(iterable $array, $name, $index = null): iterable
	{
		if ($array instanceof Traversable)
			$array = iterator_to_array($array);
		else if (!is_array($array))
			throw new RuntimeError(sprintf('The column filter only works with arrays or "Traversable", got "%s" as first argument.', gettype($array)));

		return array_column($array, $name, $index);
	}

	/**
	 * {@see array_filter()}.
	 *
	 * @param iterable $array
	 * @param callable $arrowFunction
	 *
	 * @return iterable
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function arrayFilter(iterable $array, callable $arrowFunction): iterable
	{
		if (is_array($array))
			return array_filter($array, $arrowFunction, ARRAY_FILTER_USE_BOTH);

		/** @var Traversable $array */
		return new CallbackFilterIterator(new IteratorIterator($array), $arrowFunction);
	}

	/**
	 * {@see array_map()}, but a faster implementation of it.
	 *
	 * @param iterable $array
	 * @param callable $arrowFunction
	 *
	 * @return iterable
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function arrayMap(iterable $array, callable $arrowFunction): iterable
	{
		$r = [];

		foreach ($array as $k => $v)
			$r[$k] = $arrowFunction($v, $k);

		return $r;
	}

	/**
	 * {@see array_merge()}.
	 *
	 * @param iterable $arr1
	 * @param iterable $arr2
	 *
	 * @return iterable
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function arrayMerge(iterable $arr1, iterable $arr2): iterable
	{
		if (!static::testIterable($arr1))
			throw new RuntimeError(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as first argument.', gettype($arr1)));

		if (!static::testIterable($arr2))
			throw new RuntimeError(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as second argument.', gettype($arr2)));

		return array_merge(static::toArray($arr1), static::toArray($arr2));
	}

	/**
	 * {@see array_reduce()}.
	 *
	 * @param iterable $array
	 * @param callable $arrowFunction
	 * @param null     $initial
	 *
	 * @return iterable
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function arrayReduce(iterable $array, callable $arrowFunction, $initial = null): iterable
	{
		if (!is_array($array))
			/** @var Traversable $array */
			$array = iterator_to_array($array);

		return array_reduce($array, $arrowFunction, $initial);
	}

	/**
	 * Calls a method "macro" on an object.
	 *
	 * @param Template $template
	 * @param string   $method
	 * @param array    $args
	 * @param int      $lineNumber
	 * @param array    $context
	 * @param Source   $source
	 *
	 * @return mixed
	 * @throws RuntimeError
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function callMacro(Template $template, string $method, array $args, int $lineNumber, array $context, Source $source)
	{
		if (!method_exists($template, $method))
		{
			$parent = $template;

			while ($parent = $parent->getParent($context))
				if (method_exists($parent, $method))
					return $parent->$method(...$args);

			throw new RuntimeError(sprintf('Macro "%s" is not defined in template "%s".', substr($method, strlen('macro_')), $template->getTemplateName()), $lineNumber, $source);
		}

		return $template->$method(...$args);
	}

	/**
	 * Capitalizes a string.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $string
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function capitalizeStringFilter(Cappuccino $cappuccino, string $string): string
	{
		$charset = $cappuccino->getCharset();

		return mb_strtoupper(mb_substr($string, 0, 1, $charset), $charset) . mb_strtolower(mb_substr($string, 1, null, $charset), $charset);
	}

	/**
	 * Compares the given objects with eachother.
	 *
	 * @param mixed $a
	 * @param mixed $b
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function compare($a, $b)
	{
		if (is_int($a) && is_string($b))
		{
			$b = trim($b);

			if (!is_numeric($b))
				return (string)$a <=> $b;

			if ((int)$b == $b)
				return $a <=> (int)$b;
			else
				return (float)$a <=> (float)$b;
		}

		if (is_string($a) && is_int($b))
		{
			$a = trim($a);

			if (!is_numeric($a))
				return $a <=> (string)$b;

			if ((int)$a == $a)
				return (int)$a <=> $b;
			else
				return (float)$a <=> (float)$b;
		}

		if (is_float($a) && is_string($b))
		{
			if (is_nan($a))
				return 1;

			if (!is_numeric($b))
				return (string)$a <=> $b;

			return (float)$a <=> $b;
		}

		if (is_float($b) && is_string($a))
		{
			if (is_nan($b))
				return 1;

			if (!is_numeric($a))
				return $a <=> (string)$b;

			return (float)$a <=> $b;
		}

		return $a <=> $b;
	}

	/**
	 * Gets a constant value.
	 *
	 * @param string      $constant
	 * @param object|null $object
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function constant(string $constant, ?object $object = null)
	{
		if ($object !== null)
			$constant = get_class($object) . '::' . $constant;

		return constant($constant);
	}

	/**
	 * Checks if a constant is defined.
	 *
	 * @param string      $constant
	 * @param object|null $object
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function constantIsDefined(string $constant, ?object $object = null): bool
	{
		if ($object !== null)
			$constant = get_class($object) . '::' . $constant;

		return defined($constant);
	}

	/**
	 * Converts encoding of a string.
	 *
	 * @param string $string
	 * @param string $to
	 * @param string $from
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function convertEncoding($string, string $to, string $from): string
	{
		return iconv($from, $to, $string);
	}

	/**
	 * Cycles to array values.
	 *
	 * @param array|ArrayAccess $values
	 * @param int               $position
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function cycle($values, int $position)
	{
		if (!is_array($values) && !$values instanceof ArrayAccess)
			return $values;

		return $values[$position % count($values)];
	}

	/**
	 * Tests if the given value is empty and returns the default if it is.
	 *
	 * @param mixed  $value
	 * @param string $default
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function defaultFilter($value, $default = '')
	{
		if (self::testEmpty($value))
			return $default;

		return $value;
	}

	/**
	 * Converts a date.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $date
	 * @param mixed      $timezone
	 *
	 * @return DateTime|DateTimeImmutable|DateTimeInterface|false|null
	 * @throws Error
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function dateConverter(Cappuccino $cappuccino, $date = null, $timezone = null)
	{
		if ($timezone !== false)
		{
			if ($timezone === null)
				$timezone = $cappuccino->getExtension(CoreExtension::class)->getTimezone();
			else if (!$timezone instanceof DateTimeZone)
				$timezone = new DateTimeZone($timezone);
		}

		if ($date instanceof DateTimeImmutable)
			return $timezone !== false ? $date->setTimezone($timezone) : $date;

		if ($date instanceof DateTimeInterface)
		{
			$date = clone $date;

			if ($timezone !== false)
				$date->setTimezone($timezone);

			return $date;
		}

		if ($date === null || $date === 'now')
			return new DateTime($date, $timezone !== false ? $timezone : $cappuccino->getExtension(CoreExtension::class)->getTimezone());

		$asString = (string)$date;

		if (ctype_digit($asString) || (!empty($asString) && $asString[0] === '-' && ctype_digit(substr($asString, 1))))
			$date = new DateTime('@' . $date);
		else
			$date = new DateTime($date, $cappuccino->getExtension(CoreExtension::class)->getTimezone());

		if ($timezone !== false)
			$date->setTimezone($timezone);

		return $date;
	}

	/**
	 * Applies a date format.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $date
	 * @param mixed      $format
	 * @param mixed      $timezone
	 *
	 * @return string
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function dateFormatFilter(Cappuccino $cappuccino, $date, $format = null, $timezone = null)
	{
		if ($format === null)
		{
			$formats = $cappuccino->getExtension(CoreExtension::class)->getDateFormat();
			$format = $date instanceof DateInterval ? $formats[1] : $formats[0];
		}

		if ($date instanceof DateInterval)
			return $date->format($format);

		return self::dateConverter($cappuccino, $date, $timezone)->format($format);
	}

	/**
	 * Modifies a date.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $date
	 * @param mixed      $modifier
	 *
	 * @return DateTime|DateTimeImmutable
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function dateModifyFilter(Cappuccino $cappuccino, $date, $modifier)
	{
		$date = self::dateConverter($cappuccino, $date, false);

		return $date->modify($modifier);
	}

	/**
	 * Ensures that the given value is traversable.
	 *
	 * @param iterable|mixed $seq
	 *
	 * @return iterable
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function ensureTraversable($seq): iterable
	{
		if ($seq instanceof Traversable || is_array($seq))
			return $seq;

		return [];
	}

	/**
	 * Returns the first element of the given item.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $item
	 *
	 * @return mixed|string
	 * @throws Exception
	 * @since 2.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public static function first(Cappuccino $cappuccino, $item)
	{
		$elements = self::slice($cappuccino, $item, 0, 1, false);

		return is_string($elements) ? $elements : current($elements);
	}

	/**
	 * Returns the keys of an array.
	 *
	 * @param iterable $array
	 *
	 * @return array
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function getArrayKeysFilter(iterable $array): array
	{
		if ($array instanceof Traversable)
		{
			while ($array instanceof IteratorAggregate)
				$array = $array->getIterator();

			if ($array instanceof Iterator)
			{
				$keys = [];
				$array->rewind();

				while ($array->valid())
				{
					$keys[] = $array->key();
					$array->next();
				}

				return $keys;
			}

			$keys = [];

			foreach ($array as $key => $item)
				$keys[] = $key;

			return $keys;
		}

		if (!is_array($array))
			return [];

		return array_keys($array);
	}

	/**
	 * Gets an attribute.
	 *
	 * @param Cappuccino $cappuccino
	 * @param Source     $source
	 * @param mixed      $object
	 * @param mixed      $item
	 * @param array      $arguments
	 * @param string     $type
	 * @param bool       $isDefinedTest
	 * @param bool       $ignoreStrictCheck
	 * @param bool       $sandboxed
	 * @param int        $lineNumber
	 *
	 * @return bool|mixed|null
	 * @throws Error
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function getAttribute(Cappuccino $cappuccino, Source $source, $object, $item, array $arguments = [], $type = Template::ANY_CALL, bool $isDefinedTest = false, bool $ignoreStrictCheck = false, bool $sandboxed = false, int $lineNumber = -1)
	{
		/** @var string|bool|float $item */

		if (Template::METHOD_CALL !== $type)
		{
			$arrayItem = is_bool($item) || is_float($item) ? (int)$item : $item;

			if (((is_array($object) || $object instanceof ArrayObject) && (isset($object[$arrayItem]) || array_key_exists($arrayItem, (array)$object))) || ($object instanceof ArrayAccess && isset($object[$arrayItem])))
			{
				if ($isDefinedTest)
					return true;

				return $object[$arrayItem];
			}

			if (Template::ARRAY_CALL === $type || !is_object($object))
			{
				if ($isDefinedTest)
					return false;

				if ($ignoreStrictCheck || !$cappuccino->isStrictVariables())
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
					if ($object === null)
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

				throw new RuntimeError($message, $lineNumber, $source);
			}
		}

		if (!is_object($object))
		{
			if ($isDefinedTest)
				return false;

			if ($ignoreStrictCheck || !$cappuccino->isStrictVariables())
				return null;

			if ($object === null)
				$message = sprintf('Impossible to invoke a method ("%s") on a null variable.', $item);
			else if (is_array($object))
				$message = sprintf('Impossible to invoke a method ("%s") on an array.', $item);
			else
				$message = sprintf('Impossible to invoke a method ("%s") on a %s variable ("%s").', $item, gettype($object), $object);

			throw new RuntimeError($message, $lineNumber, $source);
		}

		if ($object instanceof Template)
			throw new RuntimeError('Accessing \Cappuccino\Template attributes is forbidden.', $lineNumber, $source);

		if (Template::METHOD_CALL !== $type)
		{
			if (isset($object->{$item}) || array_key_exists((string)$item, (array)$object))
			{
				if ($isDefinedTest)
					return true;

				if ($sandboxed)
					$cappuccino->getExtension(SandboxExtension::class)->checkPropertyAllowed($object, $item, $lineNumber, $source);

				return $object->{$item};
			}
		}

		static $cache = [];

		$class = get_class($object);

		if (!isset($cache[$class]))
		{
			$methods = get_class_methods($object);
			sort($methods);
			$lcMethods = array_map('strtolower', $methods);
			$classCache = [];

			foreach ($methods as $i => $method)
			{
				$classCache[$method] = $method;
				$classCache[$lcName = $lcMethods[$i]] = $method;

				if ($lcName[0] === 'g' && strpos($lcName, 'get') === 0)
				{
					$name = substr($method, 3);
					$lcName = substr($lcName, 3);
				}
				else if ($lcName[0] === 'i' && strpos($lcName, 'is') === 0)
				{
					$name = substr($method, 2);
					$lcName = substr($lcName, 2);
				}
				else if ($lcName[0] === 'h' && strpos($lcName, 'has') === 0)
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

			if ($ignoreStrictCheck || !$cappuccino->isStrictVariables())
				return null;

			throw new RuntimeError(sprintf('Neither the property "%1$s" nor one of the methods "%1$s()", "get%1$s()"/"is%1$s()"/"has%1$s()" or "__call()" exist and have public access in class "%2$s".', $item, $class), $lineNumber, $source);
		}

		if ($isDefinedTest)
			return true;

		if ($sandboxed)
			$cappuccino->getExtension(SandboxExtension::class)->checkMethodAllowed($object, $method, $lineNumber, $source);

		try
		{
			$ret = $object->$method(...$arguments);
		}
		catch (BadMethodCallException $e)
		{
			if ($call && ($ignoreStrictCheck || !$cappuccino->isStrictVariables()))
				return null;

			throw $e;
		}

		return $ret;
	}

	/**
	 * Includes the given template.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $context
	 * @param mixed      $template
	 * @param array      $variables
	 * @param bool       $withContext
	 * @param bool       $ignoreMissing
	 * @param bool       $sandboxed
	 *
	 * @return string
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function include(Cappuccino $cappuccino, $context, $template, array $variables = [], bool $withContext = true, bool $ignoreMissing = false, bool $sandboxed = false): string
	{
		$alreadySandboxed = false;
		$sandbox = null;

		if ($withContext)
			$variables = array_merge($context, $variables);

		if ($sandboxed && $cappuccino->hasExtension(SandboxExtension::class))
		{
			$sandbox = $cappuccino->getExtension(SandboxExtension::class);

			if (!$alreadySandboxed = $sandbox->isSandboxed())
				$sandbox->enableSandbox();
		}

		try
		{
			$loaded = null;

			try
			{
				$loaded = $cappuccino->resolveTemplate($template);
			}
			catch (LoaderError $e)
			{
				if (!$ignoreMissing)
					throw $e;
			}

			return $loaded ? $loaded->render($variables) : '';
		}
		finally
		{
			if ($sandboxed && !$alreadySandboxed)
				$sandbox->disableSandbox();
		}
	}

	/**
	 * Checks if the given value is present in {@see $compare}.
	 *
	 * @param mixed $value
	 * @param mixed $compare
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function inFilter($value, $compare): bool
	{
		if ($value instanceof Markup)
			$value = (string)$value;

		if ($compare instanceof Markup)
			$compare = (string)$compare;

		if (is_string($compare))
		{
			if (is_string($value) || is_int($value) || is_float($value))
				return $value === '' || strpos($compare, (string)$value) !== false;

			return false;
		}

		if (!is_iterable($compare))
			return false;

		if (is_object($value) || is_resource($value))
		{
			if (!is_array($compare))
			{
				foreach ($compare as $item)
					if ($item === $value)
						return true;

				return false;
			}

			return in_array($value, $compare, true);
		}

		foreach ($compare as $item)
			if (self::compare($value, $item) === 0)
				return true;

		return false;
	}

	/**
	 * Joins the entries of the given value with the given glue.
	 *
	 * @param mixed       $value
	 * @param string      $glue
	 * @param string|null $and
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function joinFilter($value, string $glue = '', ?string $and = null): string
	{
		if (!self::testIterable($value))
			$value = (array)$value;

		$value = self::toArray($value, false);

		if (count($value) === 0)
			return '';

		if ($and === null || $and === $glue)
			return implode($glue, $value);

		if (count($value) === 1)
			return $value[0];

		return implode($glue, array_slice($value, 0, -1)) . $and . $value[count($value) - 1];
	}

	/**
	 * Returns the last element of the given item.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $item
	 *
	 * @return mixed|string
	 * @throws Exception
	 * @since 2.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public static function last(Cappuccino $cappuccino, $item)
	{
		$elements = self::slice($cappuccino, $item, -1, 1, false);

		return is_string($elements) ? $elements : current($elements);
	}

	/**
	 * Returns the length of the given thing.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $thing
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function lengthFilter(Cappuccino $cappuccino, $thing): int
	{
		if ($thing === null)
			return 0;

		if (is_scalar($thing))
			return mb_strlen($thing, $cappuccino->getCharset());

		if ($thing instanceof Countable || is_array($thing) || $thing instanceof SimpleXMLElement)
			return count($thing);

		if ($thing instanceof Traversable)
			return iterator_count($thing);

		if (method_exists($thing, '__toString') && !$thing instanceof Countable)
			return mb_strlen((string)$thing, $cappuccino->getCharset());

		return 1;
	}

	/**
	 * {@see mb_strtolower()}.
	 * @param Cappuccino $cappuccino
	 * @param string     $string
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function lowerFilter(Cappuccino $cappuccino, string $string): string
	{
		return mb_strtolower($string, $cappuccino->getCharset());
	}

	/**
	 * {@see number_format()}.
	 *
	 * @param Cappuccino  $cappuccino
	 * @param mixed       $number
	 * @param int|null    $decimal
	 * @param string|null $decimalPoint
	 * @param string|null $thousandSep
	 *
	 * @return string
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function numberFormatFilter(Cappuccino $cappuccino, $number, ?int $decimal = null, ?string $decimalPoint = null, ?string $thousandSep = null): string
	{
		$defaults = $cappuccino->getExtension(CoreExtension::class)->getNumberFormat();

		if ($decimal === null)
			$decimal = $defaults[0];

		if ($decimalPoint === null)
			$decimalPoint = $defaults[1];

		if ($thousandSep === null)
			$thousandSep = $defaults[2];

		return number_format((float)$number, $decimal, $decimalPoint, $thousandSep);
	}

	/**
	 * Retyrns a random value.
	 *
	 * @param Cappuccino $cappuccino
	 * @param null       $values
	 * @param int|null   $max
	 *
	 * @return mixed
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function random(Cappuccino $cappuccino, $values = null, ?int $max = null)
	{
		if ($values === null)
			return $max === null ? mt_rand() : mt_rand(0, $max);

		if (is_int($values) || is_float($values))
		{
			if (null === $max)
			{
				if ($values < 0)
				{
					$max = 0;
					$min = $values;
				}
				else
				{
					$max = $values;
					$min = 0;
				}
			}
			else
			{
				$min = $values;
			}

			return mt_rand($min, $max);
		}

		if (is_string($values))
		{
			if ($values === '')
				return '';

			$charset = $cappuccino->getCharset();

			if ($charset !== 'UTF-8')
				$values = iconv($charset, 'UTF-8', $values);

			$values = preg_split('/(?<!^)(?!$)/u', $values);

			if ($charset !== 'UTF-8')
				foreach ($values as $i => $value)
					$values[$i] = iconv('UTF-8', $charset, $value);
		}

		if (!self::testIterable($values))
			return $values;

		$values = self::toArray($values);

		if (count($values) === 0)
			throw new RuntimeError('The random function cannot pick from an empty array.');

		return $values[array_rand($values, 1)];
	}

	/**
	 * Replaces all entries in {@see $from} in the given string.
	 *
	 * @param string         $str
	 * @param iterable|mixed $from
	 *
	 * @return string
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function replaceFilter(string $str, $from): string
	{
		if (!self::testIterable($from))
			throw new RuntimeError(sprintf('The "replace" filter expects an array or "Traversable" as replace values, got "%s".', is_object($from) ? get_class($from) : gettype($from)));

		return strtr($str, self::toArray($from));
	}

	/**
	 * Reverses the given item.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $item
	 * @param bool       $preserveKeys
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function reverseFilter(Cappuccino $cappuccino, $item, bool $preserveKeys = false)
	{
		if ($item instanceof Traversable)
			return array_reverse(iterator_to_array($item), $preserveKeys);

		if (is_array($item))
			return array_reverse($item, $preserveKeys);

		$string = (string)$item;
		$charset = $cappuccino->getCharset();

		if ($charset !== 'UTF-8')
			$item = iconv($charset, 'UTF-8', $string);

		preg_match_all('/./us', $item, $matches);

		$string = implode('', array_reverse($matches[0]));

		if ($charset !== 'UTF-8')
			$string = iconv('UTF-8', $charset, $string);

		return $string;
	}

	/**
	 * Rounds the given value.
	 *
	 * @param float|int|mixed $value
	 * @param int             $precision
	 * @param string          $method
	 *
	 * @return float|int
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function round($value, int $precision = 0, string $method = 'common')
	{
		if ($method === 'common')
			return round($value, $precision);

		if ($method !== 'ceil' && $method !== 'floor')
			throw new RuntimeError('The round filter only supports the "common", "ceil", and "floor" methods.');

		return $method($value * pow(10, $precision)) / pow(10, $precision);
	}

	/**
	 * Slices the given item.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $item
	 * @param int        $start
	 * @param int|null   $length
	 * @param bool       $preserveKeys
	 *
	 * @return array|LimitIterator|string
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function slice(Cappuccino $cappuccino, $item, int $start, ?int $length = null, bool $preserveKeys = false)
	{
		if ($item instanceof Traversable)
		{
			while ($item instanceof IteratorAggregate)
				$item = $item->getIterator();

			if ($start >= 0 && $length >= 0 && $item instanceof Iterator)
			{
				try
				{
					return iterator_to_array(new LimitIterator($item, $start, null === $length ? -1 : $length), $preserveKeys);
				}
				catch (OutOfBoundsException $e)
				{
					return [];
				}
			}

			$item = iterator_to_array($item, $preserveKeys);
		}

		if (is_array($item))
			return array_slice($item, $start, $length, $preserveKeys);

		$item = (string)$item;

		return (string)mb_substr($item, $start, $length, $cappuccino->getCharset());
	}

	/**
	 * {@see asort()} and {@see uasort()}.
	 *
	 * @param iterable      $array
	 * @param callable|null $arrowFunction
	 *
	 * @return iterable
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function sortFilter(iterable $array, ?callable $arrowFunction = null): iterable
	{
		if ($array instanceof Traversable)
			$array = iterator_to_array($array);
		else if (!is_array($array))
			throw new RuntimeError(sprintf('The sort filter only works with arrays or "Traversable", got "%s".', gettype($array)));

		if ($arrowFunction !== null)
			uasort($array, $arrowFunction);
		else
			asort($array);

		return $array;
	}

	/**
	 * Gets the source code of something...?
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $name
	 * @param bool       $ignoreMissing
	 *
	 * @return string|null
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function source(Cappuccino $cappuccino, $name, bool $ignoreMissing = false): ?string
	{
		$loader = $cappuccino->getLoader();

		try
		{
			return $loader->getSourceContext($name)->getCode();
		}
		catch (LoaderError $e)
		{
			if (!$ignoreMissing)
				throw $e;

			return null;
		}
	}

	/**
	 * Applies the spaceless filter to the given content.
	 *
	 * @param string $content
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function spaceless(string $content): string
	{
		return trim(preg_replace('/>\s+</', '><', $content));
	}

	/**
	 * Splits the given value.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $value
	 * @param string     $delimiter
	 * @param int|null   $limit
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function splitFilter(Cappuccino $cappuccino, $value, string $delimiter, ?int $limit = null): array
	{
		if (strlen($delimiter) > 0)
			return $limit === null ? explode($delimiter, $value) : explode($delimiter, $value, $limit);

		if ($limit <= 1)
			return preg_split('/(?<!^)(?!$)/u', $value);

		$length = mb_strlen($value, $cappuccino->getCharset());

		if ($length < $limit)
			return [$value];

		$r = [];

		for ($i = 0; $i < $length; $i += $limit)
			$r[] = mb_substr($value, $i, $limit, $cappuccino->getCharset());

		return $r;
	}

	/**
	 * Returns TRUE if the given value is empty.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function testEmpty($value): bool
	{
		if ($value instanceof Countable)
			return count($value) === 0;

		if ($value instanceof Traversable)
			return !iterator_count($value);

		if (is_object($value) && method_exists($value, '__toString'))
			return (string)$value === '';

		return $value === '' || $value === false || $value === null || $value === [];
	}

	/**
	 * Returns TRUE if the given value is iterable.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function testIterable($value): bool
	{
		return $value instanceof Traversable || is_array($value);
	}

	/**
	 * Applies the title case to the given string.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $string
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function titleStringFilter(Cappuccino $cappuccino, string $string): string
	{
		if (($charset = $cappuccino->getCharset()) !== null)
			return mb_convert_case($string, MB_CASE_TITLE, $charset);

		return ucwords(strtolower($string));
	}

	/**
	 * Applies a trim on the given string.
	 *
	 * @param string      $string
	 * @param string|null $characterMask
	 * @param string      $side
	 *
	 * @return string
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function trimFilter(string $string, ?string $characterMask = " \t\n\r\0\x0B", string $side = 'both'): string
	{
		switch ($side)
		{
			case 'both':
				return trim($string, $characterMask);

			case 'left':
				return ltrim($string, $characterMask);

			case 'right':
				return rtrim($string, $characterMask);

			default:
				throw new RuntimeError('Trimming side must be "left", "right" or "both".');
		}
	}

	/**
	 * Converts the given sequence to an array.
	 *
	 * @param iterable $seq
	 * @param bool     $preserveKeys
	 *
	 * @return array|iterable
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function toArray(iterable $seq, bool $preserveKeys = true)
	{
		if ($seq instanceof Traversable)
			return iterator_to_array($seq, $preserveKeys);

		if (!is_array($seq))
			return $seq;

		return $preserveKeys ? $seq : array_values($seq);
	}

	/**
	 * Converts the given string to uppercase.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $string
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function upperFilter(Cappuccino $cappuccino, string $string): string
	{
		return mb_strtoupper($string, $cappuccino->getCharset());
	}

	/**
	 * Urlencodes the given value.
	 *
	 * @param string|array $url
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public static function urlEncodeFilter($url): string
	{
		if (is_array($url))
			return http_build_query($url, '', '&', PHP_QUERY_RFC3986);

		return rawurlencode($url);
	}

}
