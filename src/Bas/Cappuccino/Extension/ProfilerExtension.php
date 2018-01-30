<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Bas\Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\Profiler\NodeVisitor\ProfilerNodeVisitor;
use Bas\Cappuccino\Profiler\Profile;

/**
 * Class ProfilerExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @since 1.0.0
 */
final class ProfilerExtension extends AbstractExtension
{

	/**
	 * @var array
	 */
	private $actives = [];

	/**
	 * ProfilerExtension constructor.
	 *
	 * @param Profile $profile
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Profile $profile)
	{
		$this->actives[] = $profile;
	}

	/**
	 * Enters profiling.
	 *
	 * @param Profile $profile
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function enter (Profile $profile)
	{
		$this->actives[0]->addProfile($profile);
		array_unshift($this->actives, $profile);
	}

	/**
	 * Leaves profiling.
	 *
	 * @param Profile $profile
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function leave (Profile $profile)
	{
		$profile->leave();
		array_shift($this->actives);

		if (1 === count($this->actives))
		{
			$this->actives[0]->leave();
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getNodeVisitors (): array
	{
		return [
			new ProfilerNodeVisitor(get_class($this))
		];
	}
}
