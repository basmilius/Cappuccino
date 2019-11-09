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

namespace Cappuccino\Profiler;

use ArrayIterator;
use IteratorAggregate;
use Serializable;
use function memory_get_peak_usage;
use function memory_get_usage;
use function microtime;
use function serialize;
use function strpos;
use function unserialize;

/**
 * Class Profile
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler
 * @since 1.0.0
 */
final class Profile implements IteratorAggregate, Serializable
{

	const ROOT = 'ROOT';
	const BLOCK = 'block';
	const TEMPLATE = 'template';
	const MACRO = 'macro';

	private $template;
	private $name;
	private $type;
	private $starts = [];
	private $ends = [];
	private $profiles = [];

	/**
	 * Profile constructor.
	 *
	 * @param string $template
	 * @param string $type
	 * @param string $name
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $template = 'main', string $type = self::ROOT, string $name = 'main')
	{
		$this->template = $template;
		$this->type = $type;
		$this->name = strpos($name, '__internal_') === 0 ? 'INTERNAL' : $name;
		$this->enter();
	}

	/**
	 * Gets the template.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTemplate(): string
	{
		return $this->template;
	}

	/**
	 * Gets the type.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Gets the name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Returns TRUE if this is the root.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isRoot(): bool
	{
		return $this->type === self::ROOT;
	}

	/**
	 * Returns TRUE if this is a template.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isTemplate(): bool
	{
		return $this->type === self::TEMPLATE;
	}

	/**
	 * Returns TRUE if this is a block.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isBlock(): bool
	{
		return $this->type === self::BLOCK;
	}

	/**
	 * Returns TRUE if this is a macro.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isMacro(): bool
	{
		return $this->type === self::MACRO;
	}

	/**
	 * Gets the child profiles.
	 *
	 * @return Profile[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getProfiles(): array
	{
		return $this->profiles;
	}

	/**
	 * Adds a profile.
	 *
	 * @param Profile $profile
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addProfile(self $profile): void
	{
		$this->profiles[] = $profile;
	}

	/**
	 * Gets the duration in microseconds.
	 *
	 * @return float
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getDuration(): float
	{
		if ($this->isRoot() && $this->profiles)
		{
			$duration = 0;

			foreach ($this->profiles as $profile)
				$duration += $profile->getDuration();

			return $duration;
		}

		return isset($this->ends['wt']) && isset($this->starts['wt']) ? $this->ends['wt'] - $this->starts['wt'] : 0;
	}

	/**
	 * Gets the memory usage in bytes.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getMemoryUsage(): int
	{
		return isset($this->ends['mu']) && isset($this->starts['mu']) ? $this->ends['mu'] - $this->starts['mu'] : 0;
	}

	/**
	 * Gets the peak memory usage in bytes.
	 *
	 * @return int
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getPeakMemoryUsage(): int
	{
		return isset($this->ends['pmu']) && isset($this->starts['pmu']) ? $this->ends['pmu'] - $this->starts['pmu'] : 0;
	}

	/**
	 * Starts profiling.
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function enter(): void
	{
		$this->starts = [
			'wt' => microtime(true),
			'mu' => memory_get_usage(),
			'pmu' => memory_get_peak_usage(),
		];
	}

	/**
	 * Stops profiling.
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function leave(): void
	{
		$this->ends = [
			'wt' => microtime(true),
			'mu' => memory_get_usage(),
			'pmu' => memory_get_peak_usage(),
		];
	}

	/**
	 * Resets profiling.
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function reset(): void
	{
		$this->starts = $this->ends = $this->profiles = [];
		$this->enter();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->profiles);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function serialize()
	{
		return serialize($this->__serialize());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function unserialize($data)
	{
		$this->__unserialize(unserialize($data));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __serialize()
	{
		return [$this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __unserialize(array $data)
	{
		[$this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles] = $data;
	}

}
