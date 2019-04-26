<?php
declare(strict_types=1);

namespace Cappuccino\Profiler\Dumper;

use Cappuccino\Profiler\Profile;

/**
 * Class BaseDumper
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Profiler\Dumper
 * @since 1.2.0
 */
abstract class BaseDumper
{

	private $root;

	/**
	 * Dumps the profile.
	 *
	 * @param Profile $profile
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
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
	 * @since 1.2.0
	 */
	protected abstract function formatTemplate(Profile $profile, $prefix);

	/**
	 * Formats a non template.
	 *
	 * @param Profile $profile
	 * @param string  $prefix
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	protected abstract function formatNonTemplate(Profile $profile, $prefix);

	/**
	 * Formats time.
	 *
	 * @param Profile $profile
	 * @param float   $percent
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	protected abstract function formatTime(Profile $profile, $percent);

	/**
	 * Dumps a profile.
	 *
	 * @param Profile $profile
	 * @param string  $prefix
	 * @param bool    $sibling
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
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
