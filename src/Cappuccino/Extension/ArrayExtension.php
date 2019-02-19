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

use Cappuccino\CappuccinoFilter;

/**
 * Class ArrayExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.1
 */
final class ArrayExtension extends AbstractExtension
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function getFilters(): array
	{
		return [
			new CappuccinoFilter('shuffle', [$this, 'onFilterShuffle'])
		];
	}

	/**
	 * Shuffles an {@see array} or {@see \Traversable}.
	 *
	 * @param $array
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function onFilterShuffle($array): array
	{
		if ($array instanceof \Traversable)
			$array = iterator_to_array($array);

		shuffle($array);

		return $array;
	}

}
