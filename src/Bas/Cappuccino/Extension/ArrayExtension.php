<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\SimpleFilter;

/**
 * Class ArrayExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @since 1.0.1
 */
final class ArrayExtension extends AbstractExtension
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.1
	 */
	public final function getFilters (): array
	{
		return [
			new SimpleFilter('shuffle', [$this, 'onSimpleFunctionShuffle'])
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
	public final function onSimpleFunctionShuffle ($array): array
	{
		if ($array instanceof \Traversable)
			$array = iterator_to_array($array);

		shuffle($array);

		return $array;
	}

}
