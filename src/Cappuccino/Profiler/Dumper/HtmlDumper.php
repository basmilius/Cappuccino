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
 * Class HtmlDumper
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler\Dumper
 * @since 1.0.0
 */
final class HtmlDumper extends BaseDumper
{

	public const COLORS = [
		'block' => '#dfd',
		'macro' => '#ddf',
		'template' => '#ffd',
		'big' => '#d44',
	];

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function dump(Profile $profile): string
	{
		return '<pre>' . parent::dump($profile) . '</pre>';
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatTemplate(Profile $profile, $prefix): string
	{
		return sprintf('%s└ <span style="background-color: %s">%s</span>', $prefix, self::COLORS['template'], $profile->getTemplate());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatNonTemplate(Profile $profile, $prefix): string
	{
		return sprintf('%s└ %s::%s(<span style="background-color: %s">%s</span>)', $prefix, $profile->getTemplate(), $profile->getType(), isset(self::COLORS[$profile->getType()]) ? self::COLORS[$profile->getType()] : 'auto', $profile->getName());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatTime(Profile $profile, $percent): string
	{
		return sprintf('<span style="color: %s">%.2fms/%.0f%%</span>', $percent > 20 ? self::COLORS['big'] : 'auto', $profile->getDuration() * 1000, $percent);
	}

}
