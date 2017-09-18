<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\Profiler\NodeVisitor\ProfilerNodeVisitor;
use Bas\Cappuccino\Profiler\Profile;

/**
 * Class ProfilerExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @version 1.0.0
 */
class ProfilerExtension extends AbstractExtension
{

	private $actives = [];

	public function __construct (Profile $profile)
	{
		$this->actives[] = $profile;
	}

	public function enter (Profile $profile)
	{
		$this->actives[0]->addProfile($profile);
		array_unshift($this->actives, $profile);
	}

	public function leave (Profile $profile)
	{
		$profile->leave();
		array_shift($this->actives);

		if (1 === count($this->actives))
		{
			$this->actives[0]->leave();
		}
	}

	public function getNodeVisitors () : array
	{
		return [new ProfilerNodeVisitor(get_class($this))];
	}
}
