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

namespace Cappuccino\Extension;

use Cappuccino\CappuccinoFilter;
use Cappuccino\CappuccinoFunction;
use Cappuccino\CappuccinoTest;
use Cappuccino\ExpressionParser;
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
use Cappuccino\Node\Expression\Binary\SpaceshipBinary;
use Cappuccino\Node\Expression\Binary\StartsWithBinary;
use Cappuccino\Node\Expression\Binary\SubBinary;
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
use Cappuccino\NodeVisitor\MacroAutoImportNodeVisitor;
use Cappuccino\TokenParser\ApplyTokenParser;
use Cappuccino\TokenParser\BlockTokenParser;
use Cappuccino\TokenParser\DeprecatedTokenParser;
use Cappuccino\TokenParser\DoTokenParser;
use Cappuccino\TokenParser\EmbedTokenParser;
use Cappuccino\TokenParser\ExtendsTokenParser;
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
use DateTimeZone;
use function date_default_timezone_get;

/**
 * Class CoreExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
final class CoreExtension extends AbstractExtension
{

	/**
	 * @var array
	 */
	private $dateFormats = ['F j, Y H:i', '%d days'];

	/**
	 * @var array
	 */
	private $numberFormat = [0, '.', ','];

	/**
	 * @var DateTimeZone|null
	 */
	private $timezone = null;

	/**
	 * Sets the default date format.
	 *
	 * @param string|null $format
	 * @param string|null $dateIntervalFormat
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setDateFormat(string $format = null, string $dateIntervalFormat = null): void
	{
		if ($format !== null)
			$this->dateFormats[0] = $format;

		if ($dateIntervalFormat !== null)
			$this->dateFormats[1] = $dateIntervalFormat;
	}

	/**
	 * Gets the default date format.
	 *
	 * @return array
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getDateFormat(): array
	{
		return $this->dateFormats;
	}

	/**
	 * Sets the default timezone to be used in date filters.
	 *
	 * @param DateTimeZone|string $timezone
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setTimezone($timezone)
	{
		$this->timezone = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
	}

	/**
	 * Gets the default timezone.
	 *
	 * @return DateTimeZone
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getTimezone(): DateTimeZone
	{
		if ($this->timezone === null)
			$this->timezone = new DateTimeZone(date_default_timezone_get());

		return $this->timezone;
	}

	/**
	 * Sets number format arguments.
	 *
	 * @param int    $decimal
	 * @param string $decimalPoint
	 * @param string $thousandSep
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setNumberFormat(int $decimal, string $decimalPoint, string $thousandSep): void
	{
		$this->numberFormat = [$decimal, $decimalPoint, $thousandSep];
	}

	/**
	 * Gets number format arguments.
	 *
	 * @return array
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getNumberFormat(): array
	{
		return $this->numberFormat;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers(): array
	{
		return [
			new ApplyTokenParser(),
			new ForTokenParser(),
			new IfTokenParser(),
			new ExtendsTokenParser(),
			new IncludeTokenParser(),
			new BlockTokenParser(),
			new UseTokenParser(),
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
	public function getFilters(): array
	{
		return [
			// formatting filters
			new CappuccinoFilter('date', [StaticMethods::class, 'dateFormatFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('date_modify', [StaticMethods::class, 'dateModifyFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('format', 'sprintf'),
			new CappuccinoFilter('replace', [StaticMethods::class, 'replaceFilter']),
			new CappuccinoFilter('number_format', [StaticMethods::class, 'numberFormatFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('abs', 'abs'),
			new CappuccinoFilter('round', [StaticMethods::class, 'round']),

			// encoding
			new CappuccinoFilter('url_encode', [StaticMethods::class, 'urlEncodeFilter']),
			new CappuccinoFilter('json_encode', 'json_encode'),
			new CappuccinoFilter('convert_encoding', [StaticMethods::class, 'convertEncoding']),

			// string filters
			new CappuccinoFilter('title', [StaticMethods::class, 'titleStringFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('capitalize', [StaticMethods::class, 'capitalizeStringFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('upper', [StaticMethods::class, 'upperFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('lower', [StaticMethods::class, 'lowerFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('striptags', 'strip_tags'),
			new CappuccinoFilter('trim', [StaticMethods::class, 'trimFilter']),
			new CappuccinoFilter('nl2br', 'nl2br', ['pre_escape' => 'html', 'is_safe' => ['html']]),
			new CappuccinoFilter('spaceless', [StaticMethods::class, 'spaceless'], ['is_safe' => ['html']]),

			// array helpers
			new CappuccinoFilter('join', [StaticMethods::class, 'joinFilter']),
			new CappuccinoFilter('split', [StaticMethods::class, 'splitFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('sort', [StaticMethods::class, 'sortFilter']),
			new CappuccinoFilter('merge', [StaticMethods::class, 'arrayMerge']),
			new CappuccinoFilter('batch', [StaticMethods::class, 'arrayBatch']),
			new CappuccinoFilter('column', [StaticMethods::class, 'arrayColumn']),
			new CappuccinoFilter('filter', [StaticMethods::class, 'arrayFilter']),
			new CappuccinoFilter('map', [StaticMethods::class, 'arrayMap']),
			new CappuccinoFilter('reduce', [StaticMethods::class, 'arrayReduce']),

			// string/array filters
			new CappuccinoFilter('reverse', [StaticMethods::class, 'reverseFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('length', [StaticMethods::class, 'lengthFilter'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('slice', [StaticMethods::class, 'slice'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('first', [StaticMethods::class, 'first'], ['needs_cappuccino' => true]),
			new CappuccinoFilter('last', [StaticMethods::class, 'last'], ['needs_cappuccino' => true]),

			// iteration and runtime
			new CappuccinoFilter('default', [StaticMethods::class, 'defaultFilter'], ['node_class' => DefaultFilter::class]),
			new CappuccinoFilter('keys', [StaticMethods::class, 'getArrayKeysFilter']),
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions(): array
	{
		return [
			new CappuccinoFunction('max', 'max'),
			new CappuccinoFunction('min', 'min'),
			new CappuccinoFunction('range', 'range'),
			new CappuccinoFunction('constant', [StaticMethods::class, 'constant']),
			new CappuccinoFunction('cycle', [StaticMethods::class, 'cycle']),
			new CappuccinoFunction('random', [StaticMethods::class, 'random'], ['needs_cappuccino' => true]),
			new CappuccinoFunction('date', [StaticMethods::class, 'dateConverter'], ['needs_cappuccino' => true]),
			new CappuccinoFunction('include', [StaticMethods::class, 'include'], ['needs_cappuccino' => true, 'needs_context' => true, 'is_safe' => ['all']]),
			new CappuccinoFunction('source', [StaticMethods::class, 'source'], ['needs_cappuccino' => true, 'is_safe' => ['all']]),
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests(): array
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
			new CappuccinoTest('empty', [StaticMethods::class, 'testEmpty']),
			new CappuccinoTest('iterable', [StaticMethods::class, 'testIterable']),
		];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array
	{
		return [new MacroAutoImportNodeVisitor()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getOperators(): array
	{
		return [
			[
				'not' => ['precedence' => 50, 'class' => NotUnary::class],
				'!' => ['precedence' => 50, 'class' => NotUnary::class],
				'-' => ['precedence' => 500, 'class' => NegUnary::class],
				'+' => ['precedence' => 500, 'class' => PosUnary::class],
			],
			[
				'or' => ['precedence' => 10, 'class' => OrBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'||' => ['precedence' => 10, 'class' => OrBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'and' => ['precedence' => 15, 'class' => AndBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'&&' => ['precedence' => 15, 'class' => AndBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'b-or' => ['precedence' => 16, 'class' => BitwiseOrBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'b-xor' => ['precedence' => 17, 'class' => BitwiseXorBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'b-and' => ['precedence' => 18, 'class' => BitwiseAndBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'==' => ['precedence' => 20, 'class' => EqualBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'!=' => ['precedence' => 20, 'class' => NotEqualBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
				'<=>' => ['precedence' => 20, 'class' => SpaceshipBinary::class, 'associativity' => ExpressionParser::OPERATOR_LEFT],
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
				'??' => ['precedence' => 300, 'class' => NullCoalesceExpression::class, 'associativity' => ExpressionParser::OPERATOR_RIGHT],
			],
		];
	}

}
