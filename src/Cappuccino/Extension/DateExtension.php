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
use Cappuccino\SimpleFilter;
use DateTime;
use DateTimeInterface;

/**
 * Class DateExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.1
 */
final class DateExtension extends AbstractExtension
{

	private static $units = [
		'y' => 'year',
		'm' => 'month',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second'
	];

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function getFilters (): array
	{
		return [
			new SimpleFilter('time_diff', [$this, 'onSimpleFilterTimeDiff'], ['needs_cappuccino' => true])
		];
	}

	/**
	 * Returns the time difference as something like: 5 minutes ago.
	 *
	 * @param Cappuccino                        $cappuccino
	 * @param DateTime|DateTimeInterface|string $date
	 * @param DateTime|DateTimeInterface|string $now
	 *
	 * @return string
	 * @throws \Cappuccino\Error\RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function onSimpleFilterTimeDiff (Cappuccino $cappuccino, $date, $now): string
	{
		/** @var CoreExtension $core */
		$core = $cappuccino->getExtension(CoreExtension::class);

		$date = $core->onSimpleFunctionDateConverter($cappuccino, $date);
		$now = $core->onSimpleFunctionDateConverter($cappuccino, $now);

		$diff = $date->diff($now);

		foreach (self::$units as $attribute => $unit)
		{
			$count = $diff->{$attribute};

			if ($count !== 0)
				return $this->getPluralizedInterval($count, $diff->invert === 1, $unit);
		}

		return '';
	}

	/**
	 * Gets the pluralized version of the interval.
	 *
	 * @param int    $count
	 * @param bool   $invert
	 * @param string $unit
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	private final function getPluralizedInterval (int $count, bool $invert, string $unit): string
	{
		if ($count !== 1)
			$unit .= 's';

		return $invert ? "in $count $unit" : "$count $unit ago";
	}

}
