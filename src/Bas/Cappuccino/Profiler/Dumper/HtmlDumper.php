<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Profiler\Dumper;

use Bas\Cappuccino\Profiler\Profile;

/**
 * Class HtmlDumper
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Profiler\Dumper
 * @version 2.3.0
 */
final class HtmlDumper extends TextDumper
{

	private static $colors = [
		'block' => '#dfd',
		'macro' => '#ddf',
		'template' => '#ffd',
		'big' => '#d44',
	];

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function dump (Profile $profile)
	{
		return '<pre>' . parent::dump($profile) . '</pre>';
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function formatTemplate (Profile $profile, string $prefix)
	{
		return sprintf('%s└ <span style="background-color: %s">%s</span>', $prefix, self::$colors['template'], $profile->getTemplate());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function formatNonTemplate (Profile $profile, string $prefix)
	{
		return sprintf('%s└ %s::%s(<span style="background-color: %s">%s</span>)', $prefix, $profile->getTemplate(), $profile->getType(), isset(self::$colors[$profile->getType()]) ? self::$colors[$profile->getType()] : 'auto', $profile->getName());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	protected function formatTime (Profile $profile, float $percent)
	{
		return sprintf('<span style="color: %s">%.2fms/%.0f%%</span>', $percent > 20 ? self::$colors['big'] : 'auto', $profile->getDuration() * 1000, $percent);
	}

}
