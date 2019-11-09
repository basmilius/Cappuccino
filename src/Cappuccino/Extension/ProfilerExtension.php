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

namespace Cappuccino\Extension;

use Cappuccino\Profiler\NodeVisitor\ProfilerNodeVisitor;
use Cappuccino\Profiler\Profile;
use function array_shift;
use function array_unshift;
use function get_class;

/**
 * Class ProfilerExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
class ProfilerExtension extends AbstractExtension
{

	/**
	 * @var Profile[]
	 */
	private $actives = [];

	/**
	 * ProfilerExtension constructor.
	 *
	 * @param Profile $profile
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(Profile $profile)
	{
		$this->actives[] = $profile;
	}

	/**
	 * Starts profiling.
	 *
	 * @param Profile $profile
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function enter(Profile $profile): void
	{
		$this->actives[0]->addProfile($profile);
		array_unshift($this->actives, $profile);
	}

	/**
	 * Stops profiling.
	 *
	 * @param Profile $profile
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function leave(Profile $profile): void
	{
		$profile->leave();

		array_shift($this->actives);

		if (count($this->actives) === 1)
			$this->actives[0]->leave();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array
	{
		return [new ProfilerNodeVisitor(get_class($this))];
	}

}
