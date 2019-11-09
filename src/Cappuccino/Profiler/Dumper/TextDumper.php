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

namespace Cappuccino\Profiler\Dumper;

use Cappuccino\Profiler\Profile;
use function sprintf;

/**
 * Class TextDumper
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler\Dumper
 * @since 1.0.0
 */
final class TextDumper extends BaseDumper
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatTemplate(Profile $profile, $prefix): string
	{
		return sprintf('%s└ %s', $prefix, $profile->getTemplate());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatNonTemplate(Profile $profile, $prefix): string
	{
		return sprintf('%s└ %s::%s(%s)', $prefix, $profile->getTemplate(), $profile->getType(), $profile->getName());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatTime(Profile $profile, $percent): string
	{
		return sprintf('%.2fms/%.0f%%', $profile->getDuration() * 1000, $percent);
	}

}
