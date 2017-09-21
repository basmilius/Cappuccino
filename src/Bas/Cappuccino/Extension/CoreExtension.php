<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use ArrayAccess;
use Bas\Cappuccino\Cappuccino;
use Bas\Cappuccino\Error\LoaderError;
use Bas\Cappuccino\Error\RuntimeError;
use Bas\Cappuccino\ExpressionParser;
use Bas\Cappuccino\Markup;
use Bas\Cappuccino\Node\Expression\Binary\AddBinary;
use Bas\Cappuccino\Node\Expression\Binary\AndBinary;
use Bas\Cappuccino\Node\Expression\Binary\BitwiseAndBinary;
use Bas\Cappuccino\Node\Expression\Binary\BitwiseOrBinary;
use Bas\Cappuccino\Node\Expression\Binary\BitwiseXorBinary;
use Bas\Cappuccino\Node\Expression\Binary\ConcatBinary;
use Bas\Cappuccino\Node\Expression\Binary\DivBinary;
use Bas\Cappuccino\Node\Expression\Binary\EndsWithBinary;
use Bas\Cappuccino\Node\Expression\Binary\EqualBinary;
use Bas\Cappuccino\Node\Expression\Binary\FloorDivBinary;
use Bas\Cappuccino\Node\Expression\Binary\GreaterBinary;
use Bas\Cappuccino\Node\Expression\Binary\GreaterEqualBinary;
use Bas\Cappuccino\Node\Expression\Binary\InBinary;
use Bas\Cappuccino\Node\Expression\Binary\LessBinary;
use Bas\Cappuccino\Node\Expression\Binary\LessEqualBinary;
use Bas\Cappuccino\Node\Expression\Binary\MatchesBinary;
use Bas\Cappuccino\Node\Expression\Binary\ModBinary;
use Bas\Cappuccino\Node\Expression\Binary\MulBinary;
use Bas\Cappuccino\Node\Expression\Binary\NotEqualBinary;
use Bas\Cappuccino\Node\Expression\Binary\NotInBinary;
use Bas\Cappuccino\Node\Expression\Binary\OrBinary;
use Bas\Cappuccino\Node\Expression\Binary\PowerBinary;
use Bas\Cappuccino\Node\Expression\Binary\RangeBinary;
use Bas\Cappuccino\Node\Expression\Binary\StartsWithBinary;
use Bas\Cappuccino\Node\Expression\Binary\SubBinary;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Expression\Filter\DefaultFilter;
use Bas\Cappuccino\Node\Expression\NullCoalesceExpression;
use Bas\Cappuccino\Node\Expression\Test\ConstantTest;
use Bas\Cappuccino\Node\Expression\Test\DefinedTest;
use Bas\Cappuccino\Node\Expression\Test\DivisiblebyTest;
use Bas\Cappuccino\Node\Expression\Test\EvenTest;
use Bas\Cappuccino\Node\Expression\Test\NullTest;
use Bas\Cappuccino\Node\Expression\Test\OddTest;
use Bas\Cappuccino\Node\Expression\Test\SameasTest;
use Bas\Cappuccino\Node\Expression\Unary\NegUnary;
use Bas\Cappuccino\Node\Expression\Unary\NotUnary;
use Bas\Cappuccino\Node\Expression\Unary\PosUnary;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\SimpleFilter;
use Bas\Cappuccino\SimpleFunction;
use Bas\Cappuccino\SimpleTest;
use Bas\Cappuccino\TokenParser\BlockTokenParser;
use Bas\Cappuccino\TokenParser\DoTokenParser;
use Bas\Cappuccino\TokenParser\EmbedTokenParser;
use Bas\Cappuccino\TokenParser\ExtendsTokenParser;
use Bas\Cappuccino\TokenParser\FilterTokenParser;
use Bas\Cappuccino\TokenParser\FlushTokenParser;
use Bas\Cappuccino\TokenParser\ForTokenParser;
use Bas\Cappuccino\TokenParser\FromTokenParser;
use Bas\Cappuccino\TokenParser\IfTokenParser;
use Bas\Cappuccino\TokenParser\ImportTokenParser;
use Bas\Cappuccino\TokenParser\IncludeTokenParser;
use Bas\Cappuccino\TokenParser\MacroTokenParser;
use Bas\Cappuccino\TokenParser\SetTokenParser;
use Bas\Cappuccino\TokenParser\SpacelessTokenParser;
use Bas\Cappuccino\TokenParser\UseTokenParser;
use Bas\Cappuccino\TokenParser\WithTokenParser;
use Bas\Cappuccino\Util\StaticMethods;
use Countable;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Iterator;
use IteratorAggregate;
use LimitIterator;
use OutOfBoundsException;
use Throwable;
use Traversable;

/**
 * Class CoreExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @version 1.0.0
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
	public function getEscapers () : array
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
	public function setEscaper (string $strategy, callable $callable) : void
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
	public function getDateFormat () : array
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
	public function setDateFormat (?string $format = null, ?string $dateIntervalFormat = null) : void
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
	public function getTimezone () : DateTimeZone
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
	public function setTimezone ($timezone) : void
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
	public function getNumberFormat () : array
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
	public function setNumberFormat (int $decimal, string $decimalPoint, string $thousandSep) : void
	{
		$this->numberFormat = [$decimal, $decimalPoint, $thousandSep];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers () : array
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
			new SpacelessTokenParser(),
			new FlushTokenParser(),
			new DoTokenParser(),
			new EmbedTokenParser(),
			new WithTokenParser()
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters () : array
	{
		return [
			// formatting filters
			new SimpleFilter('date', [$this, 'onSimpleFilterDateFormat']),
			new SimpleFilter('date_modify', [$this, 'onSimpleFilterDateModify']),
			new SimpleFilter('format', 'sprintf'),
			new SimpleFilter('replace', [$this, 'onSimpleFilterReplace']),
			new SimpleFilter('number_format', [$this, 'onSimpleFilterNumberFormat']),
			new SimpleFilter('abs', 'abs'),
			new SimpleFilter('round', [$this, 'onSimpleFilterRound']),

			// encoding
			new SimpleFilter('url_encode', [$this, 'onSimpleFilterUrlEncode']),
			new SimpleFilter('json_encode', 'json_encode'),
			new SimpleFilter('convert_encoding', [StaticMethods::class, 'convertEncoding']),

			// string filters
			new SimpleFilter('title', [$this, 'onSimpleFilterStringTitle'], ['needs_cappuccino' => true]),
			new SimpleFilter('capitalize', [$this, 'onSimpleFilterStringCapitalize'], ['needs_cappuccino' => true]),
			new SimpleFilter('upper', [$this, 'onSimpleFilterStringUpper'], ['needs_cappuccino' => true]),
			new SimpleFilter('lower', [$this, 'onSimpleFilterStringLower'], ['needs_cappuccino' => true]),
			new SimpleFilter('striptags', 'strip_tags'),
			new SimpleFilter('trim', [$this, 'onSimpleFilterStringTrim']),
			new SimpleFilter('nl2br', 'nl2br', ['pre_escape' => 'html', 'is_safe' => ['html']]),

			// array helpers
			new SimpleFilter('join', [$this, 'onSimpleFIlterArrayJoin']),
			new SimpleFilter('split', [$this, 'onSimpleFilterArraySplit'], ['needs_cappuccino' => true]),
			new SimpleFilter('sort', [$this, 'onSimpleFilterArraySort']),
			new SimpleFilter('merge', [$this, 'onSimpleFilterArrayMerge']),
			new SimpleFilter('batch', [$this, 'onSimpleFilterArrayBatch']),

			// string/array filters
			new SimpleFilter('reverse', [$this, 'onSimpleFilterReverse'], ['needs_cappuccino' => true]),
			new SimpleFilter('length', [$this, 'onSimpleFilterLength'], ['needs_cappuccino' => true]),
			new SimpleFilter('slice', [$this, 'onSimpleFilterArraySlice'], ['needs_cappuccino' => true]),
			new SimpleFilter('first', [$this, 'onSimpleFilterArrayFirst'], ['needs_cappuccino' => true]),
			new SimpleFilter('last', [$this, 'onSimpleFilterArrayLast'], ['needs_cappuccino' => true]),

			// iteration and runtime
			new SimpleFilter('default', [$this, 'onSimpleFilterDefault'], ['node_class' => DefaultFilter::class]),
			new SimpleFilter('keys', [$this, 'onSimpleFilterArrayKeys']),

			// escaping
			new SimpleFilter('escape', [$this, 'onSimpleFilterEscape'], ['needs_cappuccino' => true, 'is_safe_callback' => [$this, 'onSimpleFilterEscapeIsSave']]),
			new SimpleFilter('e', [$this, 'onSimpleFilterEscape'], ['needs_cappuccino' => true, 'is_safe_callback' => [$this, 'onSimpleFilterEscapeIsSave']])
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions () : array
	{
		return [
			new SimpleFunction('max', 'max'),
			new SimpleFunction('min', 'min'),
			new SimpleFunction('range', 'range'),
			new SimpleFunction('constant', [$this, 'onSimpleFunctionConstant']),
			new SimpleFunction('cycle', [$this, 'onSimpleFunctionCycle']),
			new SimpleFunction('random', [$this, 'onSimpleFunctionRandom'], ['needs_cappuccino' => true]),
			new SimpleFunction('date', [$this, 'onSimpleFunctionDateConverter']),
			new SimpleFunction('include', [$this, 'onSimpleFunctionInclude'], ['needs_cappuccino' => true, 'needs_context' => true, 'is_safe' => ['all']]),
			new SimpleFunction('source', [$this, 'onSimpleFunctionSource'], ['needs_cappuccino' => true, 'is_safe' => ['all']])
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests () : array
	{
		return [
			new SimpleTest('even', null, ['node_class' => EvenTest::class]),
			new SimpleTest('odd', null, ['node_class' => OddTest::class]),
			new SimpleTest('defined', null, ['node_class' => DefinedTest::class]),
			new SimpleTest('same as', null, ['node_class' => SameasTest::class]),
			new SimpleTest('none', null, ['node_class' => NullTest::class]),
			new SimpleTest('null', null, ['node_class' => NullTest::class]),
			new SimpleTest('divisible by', null, ['node_class' => DivisiblebyTest::class]),
			new SimpleTest('constant', null, ['node_class' => ConstantTest::class]),
			new SimpleTest('empty', [StaticMethods::class, 'isEmpty']),
			new SimpleTest('iterable', [StaticMethods::class, 'isIterable'])
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getOperators () : array
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
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFilterDateFormat ($date, ?string $format = null, $timezone = null) : string
	{
		if ($format === null)
		{
			$formats = $this->getDateFormat();
			$format = $date instanceof DateInterval ? $formats[1] : $formats[0];
		}

		if ($date instanceof DateInterval)
			return $date->format($format);

		return $this->onSimpleFunctionDateConverter($date, $timezone)->format($format);
	}

	/**
	 * Returns a new date object modified.
	 *
	 * @param $date
	 * @param $modifier
	 *
	 * @return DateTime
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFilterDateModify ($date, $modifier)
	{
		return $this->onSimpleFunctionDateConverter($date, false)->modify($modifier);
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
	public final function onSimpleFilterDefault ($value, $default = '')
	{
		if (StaticMethods::isEmpty($value))
			return $default;

		return $value;
	}

	/**
	 * Provides the ability to get constants from instances as well as class/global constants.
	 *
	 * @param string $constant
	 * @param mixed  $object
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFunctionConstant (string $constant, $object = null) : string
	{
		if ($object !== null)
			$constant = get_class($object) . '::' . $constant;

		return constant($constant);
	}

	/**
	 * Cycles over a value.
	 *
	 * @param ArrayAccess|array $values
	 * @param int               $position
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFunctionCycle ($values, int $position) : string
	{
//		if (!is_array($values) && !($values instanceof ArrayAccess))
//			return $values;

		return $values[$position % count($values)];
	}

	/**
	 * Converts an input to a DateTime instance.
	 *
	 * @param DateTime|DateTimeInterface|string|null $date
	 * @param DateTimeZone|string|null|false         $timezone
	 *
	 * @return DateTime|DateTimeImmutable
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFunctionDateConverter ($date = null, $timezone = null)
	{
		if ($timezone)
			if ($timezone === null)
				$timezone = $this->getTimezone();
			else if (!$timezone instanceof DateTimeZone)
				$timezone = new DateTimeZone($timezone);

		if ($date instanceof DateTimeImmutable)
			return $timezone ? $date->setTimezone($timezone) : $date;

		if ($date instanceof DateTimeInterface)
		{
			$date = clone $date;

			if ($timezone)
				$date->setTimezone($timezone);

			return $date;
		}

		if ($date === null || $date === 'now')
			return new DateTime($date, $timezone ? $timezone : $this->getTimezone());

		$asString = (string)$date;

		if (ctype_digit($asString) || (!empty($asString) && '-' === $asString[0] && ctype_digit(substr($asString, 1))))
			$date = new DateTime('@' . $date);
		else
			$date = new DateTime($date, $this->getTimezone());

		if ($timezone)
			$date->setTimezone($timezone);

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
	public final function onSimpleFunctionInclude (Cappuccino $cappuccino, array $context, $template, array $variables = [], bool $withContext = true, bool $ignoreMissing = false, bool $sandboxed = false) : string
	{
		$alreadySandboxed = false;
		$isSandboxed = false;
		$sandbox = null;

		if ($withContext)
			$variables = array_merge($context, $variables);

		if ($isSandboxed = $sandboxed && $cappuccino->hasExtension(SandboxExtension::class))
		{
			/** @var SandboxExtension $sandbox */
			$sandbox = $cappuccino->getExtension(SandboxExtension::class);

			if (!$alreadySandboxed = $sandbox->isSandboxed())
				$sandbox->enableSandbox();
		}

		$result = null;

		try
		{
			$result = $cappuccino->resolveTemplate($template)->render($variables);
		}
		catch (LoaderError $e)
		{
			if (!$ignoreMissing)
			{
				if ($isSandboxed && !$alreadySandboxed)
					$sandbox->disableSandbox();

				throw $e;
			}
		}
		catch (Throwable $e)
		{
			if ($isSandboxed && !$alreadySandboxed)
				$sandbox->disableSandbox();

			throw $e;
		}

		if ($isSandboxed && !$alreadySandboxed)
			$sandbox->disableSandbox();

		return $result;
	}

	/**
	 * Returns a random value depending on the supplied parameter type.
	 *
	 * @param Cappuccino $cappuccino
	 * @param null       $values
	 *
	 * @return array|false|int|mixed|null|string|string[]
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFunctionRandom (Cappuccino $cappuccino, $values = null)
	{
		if ($values === null)
			return mt_rand();

		if (is_int($values) || is_float($values))
			return $values < 0 ? mt_rand($values, 0) : mt_rand(0, $values);

		if ($values instanceof Traversable)
		{
			$values = iterator_to_array($values);
		}
		else if (is_string($values))
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

		if (!is_array($values))
			return $values;

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
	public final function onSimpleFunctionSource (Cappuccino $cappuccino, string $name, bool $ignoreMissing = false) : ?string
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
	 * @param null              $fill
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFilterArrayBatch ($items, int $size, $fill = null) : array
	{
		if ($items instanceof Traversable)
			$items = iterator_to_array($items, false);

		$size = ceil($size);
		$result = array_chunk($items, $size, true);

		if ($fill !== null && !empty($result))
		{
			$last = count($result) - 1;

			if ($fillCount = $size - count($result[$last]))
				$result[$last] = array_merge($result[$last], array_fill(0, $fillCount, $fill));
		}

		return $result;
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
	public final function onSimpleFilterArrayFirst (Cappuccino $cappuccino, $item)
	{
		$elements = $this->onSimpleFilterArraySlice($cappuccino, $item, 0, 1, false);

		return is_string($elements) ? $elements : current($elements);
	}

	/**
	 * Joins the values to a string. The separator between elements is an empty string per default, you
	 * can define it with the optional parameter.
	 *
	 * @param array|Traversable $array
	 * @param string            $glue
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFIlterArrayJoin ($array, $glue = '') : string
	{
		if ($array instanceof Traversable)
			$array = iterator_to_array($array);

		return implode($glue, $array);
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
	public final function onSimpleFilterArrayKeys ($array) : array
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
	public final function onSimpleFilterArrayLast (Cappuccino $cappuccino, $item)
	{
		$elements = $this->onSimpleFilterArraySlice($cappuccino, $item, -1, 1, false);

		return is_string($elements) ? $elements : current($elements);
	}

	/**
	 * Merges an array with another one.
	 *
	 * @param array|Traversable $array1
	 * @param array|Traversable $array2
	 *
	 * @return array
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFilterArrayMerge ($array1, $array2) : array
	{
		if ($array1 instanceof Traversable)
			$array1 = iterator_to_array($array1);

		if ($array2 instanceof Traversable)
			$array2 = iterator_to_array($array2);

		if (!is_array($array1))
			throw new RuntimeError(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as first argument.', gettype($array1)));

		if (!is_array($array2))
			throw new RuntimeError(sprintf('The merge filter only works with arrays or "Traversable", got "%s" as first argument.', gettype($array2)));

		return array_merge($array1, $array2);
	}

	/**
	 * Slices a variable.
	 *
	 * @param Cappuccino  $cappuccino
	 * @param             $item
	 * @param int         $start
	 * @param int         $length
	 * @param bool        $preserveKeys
	 *
	 * @return array|LimitIterator|string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onSimpleFilterArraySlice (Cappuccino $cappuccino, $item, int $start, int $length, bool $preserveKeys)
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
	public final function onSimpleFilterArraySort ($array) : array
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
	public final function onSimpleFilterArraySplit (Cappuccino $cappuccino, string $str, string $delimiter, ?int $limit = null) : array
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
	public final function onSimpleFilterEscape (Cappuccino $cappuccino, $string, string $strategy = 'html', ?string $charset = null, bool $autoescape = false)
	{
		if ($autoescape && $string instanceof Markup)
			return $string;

		if (!is_string($string))
			if (is_object($string) && method_exists($string, '__toString'))
				$string = (string)$string;
			else if (in_array($strategy, ['html', 'js', 'css', 'html_attr', 'url']))
				return $string;

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
				if ('UTF-8' !== $charset)
					$string = iconv($charset, 'UTF-8', $string);

				if (0 == strlen($string) ? false : 1 !== preg_match('/^./su', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9,\._]#Su', function ($matches)
				{
					$char = $matches[0];

					if (!isset($char[1]))
						return '\\x' . strtoupper(substr('00' . bin2hex($char), -2));

					$char = StaticMethods::convertEncoding($char, 'UTF-16BE', 'UTF-8');
					$char = strtoupper(bin2hex($char));

					if (4 >= strlen($char))
						return sprintf('\u%04s', $char);

					return sprintf('\u%04s\u%04s', substr($char, 0, -4), substr($char, -4));
				}, $string);

				if ('UTF-8' !== $charset)
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'css':
				if ('UTF-8' !== $charset)
					$string = iconv($charset, 'UTF-8', $string);

				if (0 == strlen($string) ? false : 1 !== preg_match('/^./su', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9]#Su', function ($matches)
				{
					$char = $matches[0];

					if (!isset($char[1]))
					{
						$hex = ltrim(strtoupper(bin2hex($char)), '0');
						if (0 === strlen($hex))
							$hex = '0';

						return '\\' . $hex . ' ';
					}

					$char = StaticMethods::convertEncoding($char, 'UTF-16BE', 'UTF-8');

					return '\\' . ltrim(strtoupper(bin2hex($char)), '0') . ' ';
				}, $string);

				if ('UTF-8' !== $charset)
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'html_attr':
				if ('UTF-8' !== $charset)
					$string = iconv($charset, 'UTF-8', $string);

				if (0 == strlen($string) ? false : 1 !== preg_match('/^./su', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9,\.\-_]#Su', function ($matches)
				{
					static $entityMap = [
						34 => 'quot',
						38 => 'amp',
						60 => 'lt',
						62 => 'gt',
					];

					$chr = $matches[0];
					$ord = ord($chr);

					if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r") || ($ord >= 0x7f && $ord <= 0x9f))
						return '&#xFFFD;';

					if (strlen($chr) == 1)
					{
						$hex = strtoupper(substr('00' . bin2hex($chr), -2));
					}
					else
					{
						$chr = StaticMethods::convertEncoding($chr, 'UTF-16BE', 'UTF-8');
						$hex = strtoupper(substr('0000' . bin2hex($chr), -4));
					}

					$int = hexdec($hex);

					if (array_key_exists($int, $entityMap))
						return sprintf('&%s;', $entityMap[$int]);

					return sprintf('&#x%s;', $hex);
				}, $string);

				if ('UTF-8' !== $charset)
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
	public final function onSimpleFilterEscapeIsSave (Node $filterArgs) : array
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
	public final function onSimpleFilterLength (Cappuccino $cappuccino, $thing) : int
	{
		if ($thing === null)
			return 0;

		if (is_scalar($thing))
			return mb_strlen($thing, $cappuccino->getCharset());

		if (method_exists($thing, '__toString') && !($thing instanceof Countable))
			return mb_strlen((string)$thing, $cappuccino->getCharset());

		if ($thing instanceof Countable || is_array($thing))
			return count($thing);

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
	public final function onSimpleFilterNumberFormat ($number, ?int $decimal = null, ?string $decimalPoint = null, ?string $thousandSep = null) : string
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
	public final function onSimpleFilterReplace (string $str, $from) : string
	{
		if ($from instanceof Traversable)
			$from = iterator_to_array($from);
		else if (!is_array($from))
			throw new RuntimeError(sprintf('The "replace" filter expects an array or "Traversable" as replace values, got "%s".', is_object($from) ? get_class($from) : gettype($from)));

		return strtr($str, $from);
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
	public final function onSimpleFilterReverse (Cappuccino $cappuccino, $item, bool $preserveKeys = false)
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
	public final function onSimpleFilterRound ($value, int $precision = 0, string $method = 'common')
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
	public final function onSimpleFilterStringCapitalize (Cappuccino $cappuccino, string $str) : string
	{
		$charset = $cappuccino->getCharset();

		return mb_strtoupper(mb_substr($str, 0, 1, $charset), $charset) . mb_strtolower(mb_substr($str, 0, null, $charset), $charset);
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
	public final function onSimpleFilterStringLower (Cappuccino $cappuccino, string $str) : string
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
	public final function onSimpleFilterStringTitle (Cappuccino $cappuccino, string $str) : string
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
	public final function onSimpleFilterStringTrim (string $str, ?string $characterMask = null, string $side = 'both') : string
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
	public final function onSimpleFilterStringUpper (Cappuccino $cappuccino, string $str) : string
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
	public final function onSimpleFilterUrlEncode ($url) : string
	{
		if (is_array($url))
			return http_build_query($url, '', '&', PHP_QUERY_RFC3986);

		return rawurlencode($url);
	}

}
