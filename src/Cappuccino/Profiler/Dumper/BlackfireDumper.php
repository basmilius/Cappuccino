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
 * Class BlackfireDumper
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Profiler\Dumper
 * @since 1.0.0
 */
final class BlackfireDumper
{

	/**
	 * Dump.
	 *
	 * @param Profile $profile
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function dump(Profile $profile)
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
		{
			$str .= "{$name}//{$values['ct']} {$values['wt']} {$values['mu']} {$values['pmu']}\n";
		}

		return $str;
	}

	/**
	 * Dump children.
	 *
	 * @param         $parent
	 * @param Profile $profile
	 * @param         $data
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function dumpChildren($parent, Profile $profile, &$data)
	{
		foreach ($profile as $p)
		{
			if ($p->isTemplate())
			{
				$name = $p->getTemplate();
			}
			else
			{
				$name = sprintf('%s::%s(%s)', $p->getTemplate(), $p->getType(), $p->getName());
			}
			$this->dumpProfile(sprintf('%s==>%s', $parent, $name), $p, $data);
			$this->dumpChildren($name, $p, $data);
		}
	}

	/**
	 * Dump profile.
	 *
	 * @param         $edge
	 * @param Profile $profile
	 * @param         $data
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function dumpProfile($edge, Profile $profile, &$data)
	{
		if (isset($data[$edge]))
		{
			$data[$edge]['ct'] += 1;
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
