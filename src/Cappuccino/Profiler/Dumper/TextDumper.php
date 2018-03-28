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

namespace Cappuccino\Profiler\Dumper;

use Cappuccino\Profiler\Profile;

/**
 * Class TextDumper
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Profiler\Dumper
 * @since 1.0.0
 */
class TextDumper
{

	private $root;

	/**
	 * Dumps the profile.
	 *
	 * @param Profile $profile
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function dump(Profile $profile)
	{
		return $this->dumpProfile($profile);
	}

	/**
	 * Formats a template.
	 *
	 * @param Profile $profile
	 * @param string  $prefix
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatTemplate(Profile $profile, string $prefix)
	{
		return sprintf('%s└ %s', $prefix, $profile->getTemplate());
	}

	/**
	 * Formats a non template.
	 *
	 * @param Profile $profile
	 * @param string  $prefix
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatNonTemplate(Profile $profile, string $prefix)
	{
		return sprintf('%s└ %s::%s(%s)', $prefix, $profile->getTemplate(), $profile->getType(), $profile->getName());
	}

	/**
	 * Formats time.
	 *
	 * @param Profile $profile
	 * @param float   $percent
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function formatTime(Profile $profile, float $percent)
	{
		return sprintf('%.2fms/%.0f%%', $profile->getDuration() * 1000, $percent);
	}

	/**
	 * Dumps a profile.
	 *
	 * @param Profile $profile
	 * @param string  $prefix
	 * @param bool    $sibling
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function dumpProfile(Profile $profile, string $prefix = '', bool $sibling = false)
	{
		if ($profile->isRoot())
		{
			$this->root = $profile->getDuration();
			$start = $profile->getName();
		}
		else
		{
			if ($profile->isTemplate())
				$start = $this->formatTemplate($profile, $prefix);
			else
				$start = $this->formatNonTemplate($profile, $prefix);

			$prefix .= $sibling ? '│ ' : '  ';
		}

		$percent = $this->root ? $profile->getDuration() / $this->root * 100 : 0;

		if ($profile->getDuration() * 1000 < 1)
			$str = $start . "\n";
		else
			$str = sprintf("%s %s\n", $start, $this->formatTime($profile, $percent));

		$nCount = count($profile->getProfiles());

		foreach ($profile as $i => $p)
			$str .= $this->dumpProfile($p, $prefix, $i + 1 !== $nCount);

		return $str;
	}

}
