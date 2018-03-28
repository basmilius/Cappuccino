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

use Cappuccino\Cappuccino;
use Cappuccino\Error\RuntimeError;
use Cappuccino\Error\SyntaxError;
use Cappuccino\SimpleFilter;
use Exception;
use IntlDateFormatter;
use Locale;
use NumberFormatter;

/**
 * Class IntlExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.1
 */
final class IntlExtension extends AbstractExtension
{

	/**
	 * IntlExtension constructor.
	 *
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public function __construct ()
	{
		if (!class_exists(IntlDateFormatter::class))
			throw new RuntimeError('The PHP intl extension is needed to use intl-based filters.');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function getFilters (): array
	{
		return [
			new SimpleFilter('localizedcurrency', [$this, 'onSimpleFilterLocalizedCurrency']),
			new SimpleFilter('localizeddate', [$this, 'onSimpleFilterLocalizedDate'], ['needs_cappuccino' => true]),
			new SimpleFilter('localizednumber', [$this, 'onSimpleFilterLocalizedNumber'])
		];
	}

	/**
	 * Gets localized currency.
	 *
	 * @param float|int|string $number
	 * @param null|string      $currency
	 * @param null|string      $locale
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function onSimpleFilterLocalizedCurrency ($number, ?string $currency = null, ?string $locale = null): string
	{
		return self::getNumberFormatter($locale, 'currency')->formatCurrency($number, $currency);
	}

	/**
	 * Gets a localized date.
	 *
	 * @param Cappuccino                               $cappuccino
	 * @param \DateTime|\DateTimeInterface|string|null $date
	 * @param string                                   $dateFormat
	 * @param string                                   $timeFormat
	 * @param null|string                              $locale
	 * @param null                                     $timezone
	 * @param null|string                              $format
	 * @param string                                   $calendar
	 *
	 * @return string
	 * @throws \Cappuccino\Error\RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function onSimpleFilterLocalizedDate (Cappuccino $cappuccino, $date, string $dateFormat = 'full', string $timeFormat = 'full', ?string $locale = null, $timezone = null, ?string $format = null, string $calendar = 'gregorian'): string
	{
		/** @var CoreExtension $core */
		$core = $cappuccino->getExtension(CoreExtension::class);

		$date = $core->onSimpleFunctionDateConverter($date, $timezone);

		$formatValues = [
			'none' => IntlDateFormatter::NONE,
			'short' => IntlDateFormatter::SHORT,
			'medium' => IntlDateFormatter::MEDIUM,
			'long' => IntlDateFormatter::LONG,
			'full' => IntlDateFormatter::FULL,
		];

		$formatter = IntlDateFormatter::create(
			$locale,
			$formatValues[$dateFormat],
			$formatValues[$timeFormat],
			$date->getTimezone(),
			$calendar === 'gregorian' ? IntlDateFormatter::GREGORIAN : IntlDateFormatter::TRADITIONAL,
			$format
		);

		return $formatter->format($date->getTimestamp());
	}

	/**
	 * Gets a localized number.
	 *
	 * @param float|int|string $number
	 * @param string           $style
	 * @param string           $type
	 * @param null|string      $locale
	 *
	 * @return string
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function onSimpleFilterLocalizedNumber ($number, string $style = 'decimal', string $type = 'default', ?string $locale = null): string
	{
		static $typeValues = [
			'default' => NumberFormatter::TYPE_DEFAULT,
			'int32' => NumberFormatter::TYPE_INT32,
			'int64' => NumberFormatter::TYPE_INT64,
			'double' => NumberFormatter::TYPE_DOUBLE,
			'currency' => NumberFormatter::TYPE_CURRENCY
		];

		if (!isset($typeValues[$type]))
			throw new SyntaxError(sprintf('The type "%s" does not exist. Known types are: "%s"', $type, implode('", "', array_keys($typeValues))));

		return self::getNumberFormatter($locale, $style)->format($number, $typeValues[$type]);
	}

	/**
	 * Gets the {@see NumberFormatter} for the provided {@see $style} and {@see $locale}.
	 *
	 * @param string|null $locale
	 * @param string      $style
	 *
	 * @return NumberFormatter
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	private static function getNumberFormatter (?string $locale, string $style): NumberFormatter
	{
		static $currentStyle;
		/** @var NumberFormatter $formatter */
		static $formatter;

		$locale = $locale ?? Locale::getDefault();

		if ($formatter && $formatter->getLocale() === $locale && $currentStyle === $style)
			return $formatter;

		static $styleValues = [
			'decimal' => NumberFormatter::DECIMAL,
			'currency' => NumberFormatter::CURRENCY,
			'percent' => NumberFormatter::PERCENT,
			'scientific' => NumberFormatter::SCIENTIFIC,
			'spellout' => NumberFormatter::SPELLOUT,
			'ordinal' => NumberFormatter::ORDINAL,
			'duration' => NumberFormatter::DURATION
		];

		return $formatter = NumberFormatter::create($locale, $styleValues[$style]);
	}

}
