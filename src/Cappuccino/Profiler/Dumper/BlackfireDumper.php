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

/**
 * Class BlackfireDumper
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler\Dumper
 * @since 1.0.0
 */
final class BlackfireDumper
{

	/**
	 * Dumps a {@see Profile}.
	 *
	 * @param Profile $profile
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function dump(Profile $profile): string
	{
		$data = [];
		$this->dumpProfile('main()', $profile, $data);
		$this->dumpChildren('main()', $profile, $data);

		$start = sprintf('%f', microtime(true));
		$str = <<<EOF
file-format: BlackfireProbe
cost-dimensions: wt mu pmu
request-start: {$start}


EOF;

		foreach ($data as $name => $values)
			$str .= "{$name}//{$values['ct']} {$values['wt']} {$values['mu']} {$values['pmu']}\n";

		return $str;
	}

	/**
	 * @param string  $parent
	 * @param Profile $profile
	 * @param array   $data
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function dumpChildren(string $parent, Profile $profile, array &$data)
	{
		foreach ($profile as $p)
		{
			if ($p->isTemplate())
				$name = $p->getTemplate();
			else
				$name = sprintf('%s::%s(%s)', $p->getTemplate(), $p->getType(), $p->getName());

			$this->dumpProfile(sprintf('%s==>%s', $parent, $name), $p, $data);
			$this->dumpChildren($name, $p, $data);
		}
	}

	/**
	 * @param string  $edge
	 * @param Profile $profile
	 * @param array   $data
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	private function dumpProfile(string $edge, Profile $profile, array &$data)
	{
		if (isset($data[$edge]))
		{
			++$data[$edge]['ct'];
			$data[$edge]['wt'] += floor($profile->getDuration() * 1000000);
			$data[$edge]['mu'] += $profile->getMemoryUsage();
			$data[$edge]['pmu'] += $profile->getPeakMemoryUsage();
		}
		else
		{
			$data[$edge] = [
				'ct' => 1,
				'wt' => floor($profile->getDuration() * 1000000),
				'mu' => $profile->getMemoryUsage(),
				'pmu' => $profile->getPeakMemoryUsage(),
			];
		}
	}

}
