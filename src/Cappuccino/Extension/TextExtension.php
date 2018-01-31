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

/**
 * Class TextExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
final class TextExtension extends AbstractExtension
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters (): array
	{
		return [
			new SimpleFilter('truncate', [$this, 'onSimpleFilterTruncate'], ['needs_cappuccino' => true]),
			new SimpleFilter('wordwrap', [$this, 'onSimpleFilterWordwrap'], ['needs_cappuccino' => true])
		];
	}

	/**
	 * Truncates the {@see $value} string.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $value
	 * @param int        $length
	 * @param bool       $preserve
	 * @param string     $ending
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function onSimpleFilterTruncate (Cappuccino $cappuccino, string $value, int $length = 30, bool $preserve = false, string $ending = '...'): string
	{
		if (mb_strlen($value, $cappuccino->getCharset()) > $length)
		{
			if ($preserve)
			{
				if (!($breakpoint = mb_strpos($value, ' ', $length, $cappuccino->getCharset())))
					return $value;

				$length = $breakpoint;
			}

			return rtrim(mb_substr($value, 0, $length, $cappuccino->getCharset())) . $ending;
		}

		return $value;
	}

	/**
	 * Performs word wrapping on the {@see $value} string.
	 *
	 * @param Cappuccino $cappuccino
	 * @param string     $value
	 * @param int        $length
	 * @param string     $separator
	 * @param bool       $preserve
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function onSimpleFilterWordwrap (Cappuccino $cappuccino, string $value, int $length = 80, string $separator = PHP_EOL, bool $preserve = false): string
	{
		$sentences = [];

		$previous = mb_regex_encoding();
		mb_regex_encoding($cappuccino->getCharset());

		$pieces = mb_split($separator, $value);
		mb_regex_encoding($previous);

		foreach ($pieces as $piece)
		{
			while (!$preserve && mb_strlen($piece, $cappuccino->getCharset()) > $length)
			{
				$sentences[] = mb_substr($piece, 0, $length, $cappuccino->getCharset());
				$piece = mb_substr($piece, $length, 2048, $cappuccino->getCharset());
			}

			$sentences[] = $piece;
		}

		return implode($separator, $sentences);
	}

}
