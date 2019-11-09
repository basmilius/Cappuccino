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
use function count;
use function sprintf;

/**
 * Class BaseDumper
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler\Dumper
 * @since 2.0.0
 */
abstract class BaseDumper
{

	/**
	 * @var float
	 */
	private $root;

	/**
	 * Dump the given {@see Profile}.
	 *
	 * @param Profile $profile
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	public function dump(Profile $profile): string
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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	protected abstract function formatTemplate(Profile $profile, string $prefix): string;

	/**
	 * Formats anything else.
	 *
	 * @param Profile $profile
	 * @param string  $prefix
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	protected abstract function formatNonTemplate(Profile $profile, string $prefix): string;

	/**
	 * Formats time.
	 *
	 * @param Profile $profile
	 * @param float   $percent
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	protected abstract function formatTime(Profile $profile, float $percent): string;

	/**
	 * Dumps the profile.
	 *
	 * @param Profile $profile
	 * @param string  $prefix
	 * @param bool    $sibling
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	private function dumpProfile(Profile $profile, string $prefix = '', bool $sibling = false): string
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
