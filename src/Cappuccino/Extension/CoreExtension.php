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

namespace Cappuccino\Extension;

use function array_column;
use function array_slice;
use ArrayAccess;
use Cappuccino\Cappuccino;
use Cappuccino\Error\LoaderError;
use Cappuccino\Error\RuntimeError;
use Cappuccino\ExpressionParser;
use Cappuccino\Markup;
use Cappuccino\Node\Expression\Binary\AddBinary;
use Cappuccino\Node\Expression\Binary\AndBinary;
use Cappuccino\Node\Expression\Binary\BitwiseAndBinary;
use Cappuccino\Node\Expression\Binary\BitwiseOrBinary;
use Cappuccino\Node\Expression\Binary\BitwiseXorBinary;
use Cappuccino\Node\Expression\Binary\ConcatBinary;
use Cappuccino\Node\Expression\Binary\DivBinary;
use Cappuccino\Node\Expression\Binary\EndsWithBinary;
use Cappuccino\Node\Expression\Binary\EqualBinary;
use Cappuccino\Node\Expression\Binary\FloorDivBinary;
use Cappuccino\Node\Expression\Binary\GreaterBinary;
use Cappuccino\Node\Expression\Binary\GreaterEqualBinary;
use Cappuccino\Node\Expression\Binary\InBinary;
use Cappuccino\Node\Expression\Binary\LessBinary;
use Cappuccino\Node\Expression\Binary\LessEqualBinary;
use Cappuccino\Node\Expression\Binary\MatchesBinary;
use Cappuccino\Node\Expression\Binary\ModBinary;
use Cappuccino\Node\Expression\Binary\MulBinary;
use Cappuccino\Node\Expression\Binary\NotEqualBinary;
use Cappuccino\Node\Expression\Binary\NotInBinary;
use Cappuccino\Node\Expression\Binary\OrBinary;
use Cappuccino\Node\Expression\Binary\PowerBinary;
use Cappuccino\Node\Expression\Binary\RangeBinary;
use Cappuccino\Node\Expression\Binary\StartsWithBinary;
use Cappuccino\Node\Expression\Binary\SubBinary;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\Filter\DefaultFilter;
use Cappuccino\Node\Expression\NullCoalesceExpression;
use Cappuccino\Node\Expression\Test\ConstantTest;
use Cappuccino\Node\Expression\Test\DefinedTest;
use Cappuccino\Node\Expression\Test\DivisiblebyTest;
use Cappuccino\Node\Expression\Test\EvenTest;
use Cappuccino\Node\Expression\Test\NullTest;
use Cappuccino\Node\Expression\Test\OddTest;
use Cappuccino\Node\Expression\Test\SameasTest;
use Cappuccino\Node\Expression\Unary\NegUnary;
use Cappuccino\Node\Expression\Unary\NotUnary;
use Cappuccino\Node\Expression\Unary\PosUnary;
use Cappuccino\Node\Node;
use Cappuccino\CappuccinoFilter;
use Cappuccino\CappuccinoFunction;
use Cappuccino\CappuccinoTest;
use Cappuccino\TokenParser\BlockTokenParser;
use Cappuccino\TokenParser\DeprecatedTokenParser;
use Cappuccino\TokenParser\DoTokenParser;
use Cappuccino\TokenParser\EmbedTokenParser;
use Cappuccino\TokenParser\ExtendsTokenParser;
use Cappuccino\TokenParser\FilterTokenParser;
use Cappuccino\TokenParser\FlushTokenParser;
use Cappuccino\TokenParser\ForTokenParser;
use Cappuccino\TokenParser\FromTokenParser;
use Cappuccino\TokenParser\IfTokenParser;
use Cappuccino\TokenParser\ImportTokenParser;
use Cappuccino\TokenParser\IncludeTokenParser;
use Cappuccino\TokenParser\MacroTokenParser;
use Cappuccino\TokenParser\SetTokenParser;
use Cappuccino\TokenParser\UseTokenParser;
use Cappuccino\TokenParser\WithTokenParser;
use Cappuccino\Util\StaticMethods;
use function count;
use Countable;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use function get_class;
use function gettype;
use IntlDateFormatter;
use function is_array;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use Iterator;
use function iterator_to_array;
use IteratorAggregate;
use LimitIterator;
use OutOfBoundsException;
use SimpleXMLElement;
use Throwable;
use Traversable;

/**
 * Class CoreExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
final class CoreExtension extends AbstractExtension
{

	private $dateFormats = ['F j, Y H:i', '%d days'];
	private $numberFormat = [0, '.', ','];
	private $timezone = null;
	private $escapers = [];

	/**
	 * Gets all defined escapers.
	 *
	 * @return callable[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getEscapers(): array
	{
		return $this->escapers;
	}

	/**
	 * Defines a new escaper to be used via the escape filter.
	 *
	 * @param string   $strategy
	 * @param callable $callable
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function setEscaper(string $strategy, callable $callable): void
	{
		$this->escapers[$strategy] = $callable;
	}

	/**
	 * Gets the default format to be used by the date filter.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getDateFormat(): array
	{
		return $this->dateFormats;
	}

	/**
	 * Sets the default format to be used by the date filter.
	 *
	 * @param string|null $format
	 * @param string|null $dateIntervalFormat
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function setDateFormat(?string $format = null, ?string $dateIntervalFormat = null): void
	{
		if ($format !== null)
			$this->dateFormats[0] = $format;

		if ($dateIntervalFormat !== null)
			$this->dateFormats[1] = $dateIntervalFormat;
	}

	/**
	 * Gets the default timezone to be used by the date filter.
	 *
	 * @return DateTimeZone
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getTimezone(): DateTimeZone
	{
		if ($this->timezone === null)
			$this->timezone = new DateTimeZone(date_default_timezone_get());

		return $this->timezone;
	}

	/**
	 * Sets the default timezone to be used by the date filter.
	 *
	 * @param DateTimeZone|string $timezone
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function setTimezone($timezone): void
	{
		$this->timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
	}

	/**
	 * Gets the default format used by the number_format filter.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getNumberFormat(): array
	{
		return $this->numberFormat;
	}

	/**
	 * Sets the default format to be used by the number_format filter.
	 *
	 * @param int    $decimal
	 * @param string $decimalPoint
	 * @param string $thousandSep
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function setNumberFormat(int $decimal, string $decimalPoint, string $thousandSep): void
	{
		$this->numberFormat = [$decimal, $decimalPoint, $thousandSep];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getTokenParsers(): array
	{
		return [
			new ForTokenParser(),
			new IfTokenParser(),
			new ExtendsTokenParser(),
			new IncludeTokenParser(),
			new BlockTokenParser(),
			new UseTokenParser(),
			new FilterTokenParser(),
			new MacroTokenParser(),
			new ImportTokenParser(),
			new FromTokenParser(),
			new SetTokenParser(),
			new FlushTokenParser(),
			new DoTokenParser(),
			new EmbedTokenParser(),
			new WithTokenParser(),
			new DeprecatedTokenParser()
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getFilters(): array
	{
		return [
			// formatting filters
			new CappuccinoFilter('date', [$this, 'onFilterDateFormat']),
			new CappuccinoFilter('date_modify', [$this, 'onFilterDateModify']),
			new CappuccinoFilter('format', 'sprintf'),
			new CappuccinoFilter('replace', [$this, 'onFilterReplace']),
			new CappuccinoFilter('number_format', [$this, 'onFilterNumberFormat']),
			new CappuccinoFilter('abs', 'abs'),
			new CappuccinoFilter('round', [$this, 'onFilterRound']),

			// encoding
			new CappuccinoFilter('url_encode', [$this, 'onFilterUrlEncode']),
			new CappuccinoFilter('json_encode', 'json_encode'),
			new CappuccinoFilter('convert_encoding', 'mb_convert_encoding'),

			// string filters
			new CappuccinoFilter('title', [$this, 'onFilterStringTitle'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('capitalize', [$this, 'onFilterStringCapitalize'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('upper', [$this, 'onFilterStringUpper'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('lower', [$this, 'onFilterStringLower'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('striptags', 'strip_tags'),
			new CappuccinoFilter('trim', [$this, 'onFilterStringTrim']),
			new CappuccinoFilter('nl2br', 'nl2br', ['pre_escape' => 'html', 'is_safe' => ['html']]),
			new CappuccinoFilter('spaceless', [$this, 'onFilterSpaceless'], ['is_safe' => ['html']]),

			// array helpers
			new CappuccinoFilter('join', [$this, 'onFilterArrayJoin']),
			new CappuccinoFilter('split', [$this, 'onFilterArraySplit'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('sort', [$this, 'onFilterArraySort']),
			new CappuccinoFilter('merge', [StaticMethods::class, 'arrayMerge']),
			new CappuccinoFilter('batch', [$this, 'onFilterArrayBatch']),
			new CappuccinoFilter('column', [$this, 'onFilterArrayColumn']),

			// string/array filters
			new CappuccinoFilter('reverse', [$this, 'onFilterReverse'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('length', [$this, 'onFilterLength'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('slice', [$this, 'onFilterArraySlice'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('first', [$this, 'onFilterArrayFirst'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('last', [$this, 'onFilterArrayLast'], ['needs_cappuccino' => true]),

			// iteration and runtime
			new CappuccinoFilter('default', [$this, 'onFilterDefault'], ['node_class' => DefaultFilter::class]),
			new CappuccinoFilter('keys', [$this, 'onFilterArrayKeys']),

			// escaping
			new CappuccinoFilter('escape', [$this, 'onFilterEscape'], ['needs_cappuccino' => true, 'is_safe_callback' => [$this, 'onFilterEscapeIsSave']]),
			new CappuccinoFilter('e', [$this, 'onFilterEscape'], ['needs_cappuccino' => true, 'is_safe_callback' => [$this, 'onFilterEscapeIsSave']])
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getFunctions(): array
	{
		return [
			new CappuccinoFunction('max', 'max'),
			new CappuccinoFunction('min', 'min'),
			new CappuccinoFunction('range', 'range'),
			new CappuccinoFunction('constant', [StaticMethods::class, 'isConstant']),
			new CappuccinoFunction('cycle', [$this, 'onFunctionCycle']),
			new CappuccinoFunction('random', [$this, 'onFunctionRandom'], ['needs_cappuccino' => true]),
			new CappuccinoFunction('date', [$this, 'onFunctionDateConverter']),
			new CappuccinoFunction('include', [$this, 'onFunctionInclude'], ['needs_cappuccino' => true, 'needs_context' => true, 'is_safe' => ['all']]),
			new CappuccinoFunction('source', [$this, 'onFunctionSource'], ['needs_cappuccino' => true, 'is_safe' => ['all']])
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getTests(): array
	{
		return [
			new CappuccinoTest('even', null, ['node_class' => EvenTest::class]),
			new CappuccinoTest('odd', null, ['node_class' => OddTest::class]),
			new CappuccinoTest('defined', null, ['node_class' => DefinedTest::class]),
			new CappuccinoTest('same as', null, ['node_class' => SameasTest::class]),
			new CappuccinoTest('none', null, ['node_class' => NullTest::class]),
			new CappuccinoTest('null', null, ['node_class' => NullTest::class]),
			new CappuccinoTest('divisible by', null, ['node_class' => DivisiblebyTest::class]),
			new CappuccinoTest('constant', null, ['node_class' => ConstantTest::class]),
			new CappuccinoTest('empty', [StaticMethods::class, 'isEmpty']),
			new CappuccinoTest('iterable', [StaticMethods::class, 'isIterable']),
			new CappuccinoTest('numeric', 'is_numeric')
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getOperators(): array
	{
		return [
			[
				'!' => ['precedence' => 50, 'class' => NotUnary::class], // Added 21-09-2017
				'not' => ['precedence' => 50, 'class' => NotUnary::class],
				'-' => ['precedence' => 500, 'class' => NegUnary::class],
				'+' => ['precedence' => 500, 'class' => PosUnary::class]
			],
			[
				'||' => ['precedence' => 10, 'class' => OrBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT], // Added 21-09-2017
				'&&' => ['precedence' => 15, 'class' => AndBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT], // Added 21-09-2017
				'or' => ['precedence' => 10, 'class' => OrBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'and' => ['precedence' => 15, 'class' => AndBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'b-or' => ['precedence' => 16, 'class' => BitwiseOrBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'b-xor' => ['precedence' => 17, 'class' => BitwiseXorBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'b-and' => ['precedence' => 18, 'class' => BitwiseAndBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'==' => ['precedence' => 20, 'class' => EqualBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'!=' => ['precedence' => 20, 'class' => NotEqualBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'<' => ['precedence' => 20, 'class' => LessBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'>' => ['precedence' => 20, 'class' => GreaterBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'>=' => ['precedence' => 20, 'class' => GreaterEqualBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'<=' => ['precedence' => 20, 'class' => LessEqualBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'not in' => ['precedence' => 20, 'class' => NotInBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'in' => ['precedence' => 20, 'class' => InBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'matches' => ['precedence' => 20, 'class' => MatchesBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'starts with' => ['precedence' => 20, 'class' => StartsWithBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'ends with' => ['precedence' => 20, 'class' => EndsWithBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'..' => ['precedence' => 25, 'class' => RangeBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'+' => ['precedence' => 30, 'class' => AddBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'-' => ['precedence' => 30, 'class' => SubBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'~' => ['precedence' => 40, 'class' => ConcatBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'*' => ['precedence' => 60, 'class' => MulBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'/' => ['precedence' => 60, 'class' => DivBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'//' => ['precedence' => 60, 'class' => FloorDivBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'%' => ['precedence' => 60, 'class' => ModBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'is' => ['precedence' => 100, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'is not' => ['precedence' => 100, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'**' => ['precedence' => 200, 'class' => PowerBinary::class, 'associativity' => ExpressionParser::OPERATOR_RIGHT],
				'??' => ['precedence' => 300, 'class' => NullCoalesceExpression::class, 'associativity' => ExpressionParser::OPERATOR_RIGHT]
			],
		];
	}

	/**
	 * Converts a date to the given format.
	 *
	 * @param DateTimeInterface|DateInterval|string $date
	 * @param string|null                           $format
	 * @param DateTimeZone|string|null|false        $timezone
	 *
	 * @return string
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterDateFormat($date, ?string $format = null, $timezone = null): string
	{
		if ($format === null)
		{
			$formats = $this->getDateFormat();
			$format = $date instanceof DateInterval ? $formats[1] : $formats[0];
		}

		$formatter = new IntlDateFormatter('nl_NL', IntlDateFormatter::FULL, IntlDateFormatter::FULL);
		$formatter->setPattern($format);

		if ($date instanceof DateInterval)
			return $formatter->format($date);

		$dt = $this->onFunctionDateConverter($date, $timezone);

		return $formatter->format($dt);
	}

	/**
	 * Returns a new date object modified.
	 *
	 * @param $date
	 * @param $modifier
	 *
	 * @return DateTime
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterDateModify($date, $modifier)
	{
		return $this->onFunctionDateConverter($date, false)->modify($modifier);
	}

	/**
	 * Default filter.
	 *
	 * @param mixed  $value
	 * @param string $default
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterDefault($value, $default = '')
	{
		if (StaticMethods::isEmpty($value))
			return $default;

		return $value;
	}

	/**
	 * Cycles over a value.
	 *
	 * @param array|ArrayAccess $values
	 * @param int               $position
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function onFunctionCycle($values, int $position): string
	{
		if (!is_array($values) && !($values instanceof ArrayAccess))
			return (string)$values;

		return $values[$position % count($values)];
	}

	/**
	 * Converts an input to a DateTime instance.
	 *
	 * @param DateTime|DateTimeInterface|string|null $date
	 * @param DateTimeZone|string|null|false         $timezone
	 *
	 * @return DateTime|DateTimeImmutable
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function onFunctionDateConverter($date = null, $timezone = null)
	{
		if ($timezone)
		{
			if ($timezone === null)
				$timezone = $this->getTimezone();
			else if (!$timezone instanceof DateTimeZone)
				$timezone = new DateTimeZone($timezone);
		}

		if ($date instanceof DateTimeImmutable)
			return $timezone ? $date->setTimezone($timezone) : $date;

		if ($date instanceof DateTimeInterface)
		{
			$date = clone $date;

			if ($timezone)
				$date->setTimezone($timezone);

			return $date;
		}

		try
		{
			if ($date === null || $date === 'now')
				return new DateTime($date, $timezone ? $timezone : $this->getTimezone());

			$asString = (string)$date;

			if (ctype_digit($asString) || (!empty($asString) && '-' === $asString[0] && ctype_digit(substr($asString, 1))))
				$date = new DateTime('@' . $date);
			else
				$date = new DateTime($date, $this->getTimezone());

			if ($timezone)
				$date->setTimezone($timezone);
		}
		catch (Exception $err)
		{
			throw new RuntimeError($err->getMessage());
		}

		return $date;
	}

	/**
	 * Renders a Template.
	 *
	 * @param Cappuccino      $cappuccino
	 * @param array           $context
	 * @param string|string[] $template
	 * @param array           $variables
	 * @param bool            $withContext
	 * @param bool            $ignoreMissing
	 * @param bool            $sandboxed
	 *
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws Throwable
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFunctionInclude(Cappuccino $cappuccino, array $context, $template, array $variables = [], bool $withContext = true, bool $ignoreMissing = false, bool $sandboxed = false): string
	{
		/** @var SandboxExtension|null $sandbox */
		$sandbox = null;
		$alreadySandboxed = false;
		$isSandboxed = false;

		if ($withContext)
			$variables = array_merge($context, $variables);

		if ($isSandboxed = $sandboxed && $cappuccino->hasExtension(SandboxExtension::class))
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
			if ($isSandboxed && !$alreadySandboxed)
				$sandbox->disableSandbox();
		}
	}

	/**
	 * Returns a random value depending on the supplied parameter type.
	 *
	 * @param Cappuccino                         $cappuccino
	 * @param Traversable|array|int|float|string $values
	 * @param int|null                           $max
	 *
	 * @return array|false|int|mixed|string|null|string[]
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFunctionRandom(Cappuccino $cappuccino, $values = null, $max = null)
	{
		if ($values === null)
			return $max === null ? mt_rand() : mt_rand(0, $max);

		if (is_int($values) || is_float($values))
		{
			if ($max === null)
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

		if (!StaticMethods::isIterable($values))
			return $values;

		$values = StaticMethods::toArray($values);

		if (count($values) === 0)
			throw new RuntimeError('The random function cannot pick from an empty array.');

		return $values[array_rand($values, 1)];
	}

	/**
	 * Returns a template context without rendering it.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $name
	 * @param bool       $ignoreMissing
	 *
	 * @return string|null
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFunctionSource(Cappuccino $cappuccino, string $name, bool $ignoreMissing = false): ?string
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
		}

		return null;
	}

	/**
	 * Batches items.
	 *
	 * @param Traversable|array $items
	 * @param int               $size
	 * @param mixed             $fill
	 * @param bool              $preserveKeys
	 *
	 * @return array
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArrayBatch($items, int $size, $fill = null, bool $preserveKeys = true): array
	{
		if (!StaticMethods::isIterable($items))
			throw new RuntimeError(sprintf('The "batch" filter expects an array or "Traversable", got "%s".', is_object($items) ? get_class($items) : gettype($items)));

		$size = ceil($size);
		$result = array_chunk(StaticMethods::toArray($items, $preserveKeys), $size, $preserveKeys);

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
	 * Returns an array column.
	 *
	 * @param Traversable|array $array
	 * @param string            $column
	 *
	 * @return array
	 * @throws RuntimeError
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.2.0
	 */
	public final function onFilterArrayColumn($array, string $column): array
	{
		if ($array instanceof Traversable)
			$array = iterator_to_array($array);
		else if (!is_array($array))
			throw new RuntimeError(sprintf('The column filter only works with arrays or "Traversable", got "%s" as first argument.', gettype($array)));

		return array_column($array, $column);
	}

	/**
	 * Returns the first element of the item.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $item
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArrayFirst(Cappuccino $cappuccino, $item)
	{
		$elements = $this->onFilterArraySlice($cappuccino, $item, 0, 1, false);

		return is_string($elements) ? $elements : current($elements);
	}

	/**
	 * Joins the values to a string. The separator between elements is an empty string per default, you
	 * can define it with the optional parameter.
	 *
	 * @param array|Traversable $value
	 * @param string            $glue
	 * @param string|null       $and
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArrayJoin($value, $glue = '', ?string $and = null): string
	{
		if (!StaticMethods::isIterable($value))
			$value = (array)$value;

		$value = StaticMethods::toArray($value, false);

		if (count($value) === 0)
			return '';

		if ($and === null || $and === $glue)
			return implode($glue, $value);

		if (count($value) === 1)
			return $value[0];

		return implode($glue, array_slice($value, 0, -1)) . $and . $value[count($value) - 1];
	}

	/**
	 * Returns the keys for the given array.
	 *
	 * @param array|Traversable $array
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArrayKeys($array): array
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
	 * Returns the last element of the item.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $item
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArrayLast(Cappuccino $cappuccino, $item)
	{
		$elements = $this->onFilterArraySlice($cappuccino, $item, -1, 1, false);

		return is_string($elements) ? $elements : current($elements);
	}

	/**
	 * Slices a variable.
	 *
	 * @param Cappuccino  $cappuccino
	 * @param             $item
	 * @param int         $start
	 * @param int|null    $length
	 * @param bool        $preserveKeys
	 *
	 * @return array|LimitIterator|string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArraySlice(Cappuccino $cappuccino, $item, int $start, ?int $length = null, bool $preserveKeys = false)
	{
		if ($item instanceof Traversable)
		{
			while ($item instanceof IteratorAggregate)
				$item = $item->getIterator();

			if ($start >= 0 && $length >= 0 && $item instanceof Iterator)
			{
				try
				{
					return iterator_to_array(new LimitIterator($item, $start, $length === null ? -1 : $length), $preserveKeys);
				}
				catch (OutOfBoundsException $exception)
				{
					return [];
				}
			}

			$item = iterator_to_array($item, $preserveKeys);
		}

		if (is_array($item))
			return array_slice($item, $start, $length, $preserveKeys);

		return (string)mb_substr((string)$item, $start, $length, $cappuccino->getCharset());
	}

	/**
	 * Sorts an array.
	 *
	 * @param Traversable|array $array
	 *
	 * @return array
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArraySort($array): array
	{
		if ($array instanceof Traversable)
			$array = iterator_to_array($array);

		if (!is_array($array))
			throw new RuntimeError(sprintf('The sort filter only works with arrays or "Traversable", got "%s".', gettype($array)));

		asort($array);

		return $array;
	}

	/**
	 * Splits the string into an array.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $str
	 * @param string     $delimiter
	 * @param int|null   $limit
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterArraySplit(Cappuccino $cappuccino, string $str, string $delimiter, ?int $limit = null): array
	{
		if (!empty($delimiter))
		{
			return null === $limit ? explode($delimiter, $str) : explode($delimiter, $str, $limit);
		}

		if ($limit <= 1)
		{
			return preg_split('/(?<!^)(?!$)/u', $str);
		}

		$length = mb_strlen($str, $cappuccino->getCharset());

		if ($length < $limit)
			return [$str];

		$r = [];

		for ($i = 0; $i < $length; $i += $limit)
			$r[] = mb_substr($str, $i, $limit, $cappuccino->getCharset());

		return $r;
	}

	/**
	 * Escapes a string.
	 *
	 * @param Cappuccino    $cappuccino
	 * @param Markup|string $string
	 * @param string        $strategy
	 * @param string|null   $charset
	 * @param bool          $autoescape
	 *
	 * @return Markup|string
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterEscape(Cappuccino $cappuccino, $string, string $strategy = 'html', ?string $charset = null, bool $autoescape = false)
	{
		if ($autoescape && $string instanceof Markup)
			return $string;

		if (!is_string($string))
			if (is_object($string) && method_exists($string, '__toString'))
				$string = (string)$string;
			else if (in_array($strategy, ['html', 'js', 'css', 'html_attr', 'url']))
				return $string;

		if ($string === '')
			return '';

		if ($charset === null)
			$charset = $cappuccino->getCharset();

		switch ($strategy)
		{
			case 'html':
				static $htmlspecialcharsCharsets = [
					'ISO-8859-1' => true, 'ISO8859-1' => true,
					'ISO-8859-15' => true, 'ISO8859-15' => true,
					'utf-8' => true, 'UTF-8' => true,
					'CP866' => true, 'IBM866' => true, '866' => true,
					'CP1251' => true, 'WINDOWS-1251' => true, 'WIN-1251' => true,
					'1251' => true,
					'CP1252' => true, 'WINDOWS-1252' => true, '1252' => true,
					'KOI8-R' => true, 'KOI8-RU' => true, 'KOI8R' => true,
					'BIG5' => true, '950' => true,
					'GB2312' => true, '936' => true,
					'BIG5-HKSCS' => true,
					'SHIFT_JIS' => true, 'SJIS' => true, '932' => true,
					'EUC-JP' => true, 'EUCJP' => true,
					'ISO8859-5' => true, 'ISO-8859-5' => true, 'MACROMAN' => true,
				];

				if (isset($htmlspecialcharsCharsets[$charset]))
					return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);

				if (isset($htmlspecialcharsCharsets[strtoupper($charset)]))
				{
					$htmlspecialcharsCharsets[$charset] = true;

					return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
				}

				$string = iconv($charset, 'UTF-8', $string);
				$string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

				return iconv('UTF-8', $charset, $string);

			case 'js':
				if ($charset !== 'UTF-8')
					$string = iconv($charset, 'UTF-8', $string);

				if (!preg_match('//u', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9,\._]#Su', function ($matches)
				{
					$char = $matches[0];

					if (!isset($char[1]))
						return '\\x' . strtoupper(substr('00' . bin2hex($char), -2));

					$char = mb_convert_encoding($char, 'UTF-16BE', 'UTF-8');
					$char = strtoupper(bin2hex($char));

					if (4 >= strlen($char))
						return sprintf('\u%04s', $char);

					return sprintf('\u%04s\u%04s', substr($char, 0, -4), substr($char, -4));
				}, $string);

				if ($charset !== 'UTF-8')
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'css':
				if ($charset !== 'UTF-8')
					$string = iconv($charset, 'UTF-8', $string);

				if (!preg_match('//u', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9]#Su', function ($matches)
				{
					$char = $matches[0];

					return sprintf('\\%X ', strlen($char) === 1 ? ord($char) : mb_ord($char, 'UTF-8'));
				}, $string);

				if ($charset !== 'UTF-8')
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'html_attr':
				if ($charset !== 'UTF-8')
					$string = iconv($charset, 'UTF-8', $string);

				if (!preg_match('//u', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', function ($matches)
				{
					$chr = $matches[0];
					$ord = ord($chr);

					if (($ord <= 0x1f && "\t" != $chr && "\n" != $chr && "\r" != $chr) || ($ord >= 0x7f && $ord <= 0x9f))
						return '&#xFFFD;';

					if (strlen($chr) === 1)
					{
						static $entityMap = [
							34 => '&quot;',
							38 => '&amp;',
							60 => '&lt;',
							62 => '&gt;',
						];

						if (isset($entityMap[$ord]))
							return $entityMap[$ord];

						return sprintf('&#x%02X;', $ord);
					}

					return sprintf('&#x%04X;', mb_ord($chr, 'UTF-8'));
				}, $string);

				if ($charset !== 'UTF-8')
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'url':
				return rawurlencode($string);

			default:
				static $escapers;

				if ($escapers === null)
					$escapers = $this->getEscapers();

				if (isset($escapers[$strategy]))
					return $escapers[$strategy]($cappuccino, $string, $charset);

				$validStrategies = implode(', ', array_merge(['html', 'js', 'url', 'css', 'html_attr'], array_keys($escapers)));

				throw new RuntimeError(sprintf('Invalid escaping strategy "%s" (valid ones: %s).', $strategy, $validStrategies));
		}
	}

	/**
	 * Checks if save.
	 *
	 * @param Node $filterArgs
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterEscapeIsSave(Node $filterArgs): array
	{
		foreach ($filterArgs as $arg)
		{
			if ($arg instanceof ConstantExpression)
				return [$arg->getAttribute('value')];

			return [];
		}

		return ['html'];
	}

	/**
	 * Returns the length of a variable.
	 *
	 * @param Cappuccino $cappuccino
	 * @param mixed      $thing
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterLength(Cappuccino $cappuccino, $thing): int
	{
		if ($thing === null)
			return 0;

		if (is_scalar($thing))
			return mb_strlen($thing, $cappuccino->getCharset());

		if ($thing instanceof Countable || is_array($thing) || $thing instanceof SimpleXMLElement)
			return count($thing);

		if ($thing instanceof Traversable)
			return iterator_count($thing);

		if (is_object($thing) && method_exists($thing, '__toString'))
			return mb_strlen((string)$thing, $cappuccino->getCharset());

		return 1;
	}

	/**
	 * Number format filter.
	 *
	 * @param mixed       $number
	 * @param int|null    $decimal
	 * @param string|null $decimalPoint
	 * @param string|null $thousandSep
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterNumberFormat($number, ?int $decimal = null, ?string $decimalPoint = null, ?string $thousandSep = null): string
	{
		$defaults = $this->getNumberFormat();

		if ($decimal === null)
			$decimal = $defaults[0];

		if ($decimalPoint === null)
			$decimalPoint = $defaults[1];

		if ($thousandSep === null)
			$thousandSep = $defaults[2];

		return number_format((float)$number, $decimal, $decimalPoint, $thousandSep);
	}

	/**
	 * Replaces strings within a string.
	 *
	 * @param string            $str
	 * @param array|Traversable $from
	 *
	 * @return string
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterReplace(string $str, $from): string
	{
		if (!StaticMethods::isIterable($from))
			throw new RuntimeError(sprintf('The "replace" filter expects an array or "Traversable" as replace values, got "%s".', is_object($from) ? get_class($from) : gettype($from)));

		return strtr($str, StaticMethods::toArray($from));
	}

	/**
	 * Reverses a variable.
	 *
	 * @param Cappuccino               $cappuccino
	 * @param array|string|Traversable $item
	 * @param bool                     $preserveKeys
	 *
	 * @return array|string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterReverse(Cappuccino $cappuccino, $item, bool $preserveKeys = false)
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
	 * Rounds a number.
	 *
	 * @param int|float $value
	 * @param int       $precision
	 * @param string    $method
	 *
	 * @return float|int
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterRound($value, int $precision = 0, string $method = 'common')
	{
		if ($method === 'common')
			return round($value, $precision);

		if ($method !== 'ceil' && $method !== 'floor')
			throw new RuntimeError('The round filter only supports the "common", "ceil", and "floor" methods.');

		return $method($value * pow(10, $precision)) / pow(10, $precision);
	}

	/**
	 * Returns a capitalized string.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $str
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterStringCapitalize(Cappuccino $cappuccino, string $str): string
	{
		$charset = $cappuccino->getCharset();

		return mb_strtoupper(mb_substr($str, 0, 1, $charset), $charset) . mb_strtolower(mb_substr($str, 1, null, $charset), $charset);
	}

	/**
	 * Converts a string to lowercase.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $str
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterStringLower(Cappuccino $cappuccino, string $str): string
	{
		return mb_strtolower($str, $cappuccino->getCharset());
	}

	/**
	 * Returns a titlecased string.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $str
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterStringTitle(Cappuccino $cappuccino, string $str): string
	{
		if (($charset = $cappuccino->getCharset()) !== null)
			return mb_convert_case($str, MB_CASE_TITLE, $charset);

		return ucwords(strtolower($str));
	}

	/**
	 * Returns a trimmed string.
	 *
	 * @param string      $str
	 * @param string|null $characterMask
	 * @param string      $side
	 *
	 * @return string
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterStringTrim(string $str, ?string $characterMask = null, string $side = 'both'): string
	{
		if ($characterMask === null)
			$characterMask = " \t\n\r\0\x0B";

		switch ($side)
		{
			case 'both':
				return trim($str, $characterMask);
			case 'left':
				return ltrim($str, $characterMask);
			case 'right':
				return rtrim($str, $characterMask);
			default:
				throw new RuntimeError('Trimming side must be "left", "right" or "both".');
		}
	}

	/**
	 * Invoked on the spaceless filter.
	 *
	 * @param string $content
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.1
	 */
	public final function onFilterSpaceless(string $content): string
	{
		return trim(preg_replace('/>\s+</', '><', $content));
	}

	/**
	 * Converts a string to uppercase.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $str
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterStringUpper(Cappuccino $cappuccino, string $str): string
	{
		return mb_strtoupper($str, $cappuccino->getCharset());
	}

	/**
	 * URL encodes (RFC 3986) a string as a path segment or an array as a query string.
	 *
	 * @param string|array $url
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterUrlEncode($url): string
	{
		if (is_array($url))
			return http_build_query($url, '', '&', PHP_QUERY_RFC3986);

		return rawurlencode($url);
	}

}
