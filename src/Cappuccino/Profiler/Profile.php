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

namespace Cappuccino\Profiler;

use ArrayIterator;
use IteratorAggregate;
use Serializable;

/**
 * Class Profile
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Profiler
 * @since 1.0.0
 */
class Profile implements IteratorAggregate, Serializable
{

	public const ROOT = 'ROOT';
	public const BLOCK = 'block';
	public const TEMPLATE = 'template';
	public const MACRO = 'macro';

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
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $template = 'main', string $type = self::ROOT, string $name = 'main')
	{
		if (__CLASS__ !== get_class($this))
			@trigger_error('Overriding ' . __CLASS__ . ' is deprecated since version 2.4.0 and the class will be final in 3.0.', E_USER_DEPRECATED);

		$this->template = $template;
		$this->type = $type;
		$this->name = 0 === strpos($name, '__internal_') ? 'INTERNAL' : $name;
		$this->enter();
	}

	/**
	 * Gets the template.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTemplate (): string
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
	public function getType (): string
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
	public function getName (): string
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
	public function isRoot (): bool
	{
		return self::ROOT === $this->type;
	}

	/**
	 * Returns TRUE if this is a template.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isTemplate (): bool
	{
		return self::TEMPLATE === $this->type;
	}

	/**
	 * Returns TRUE if this is a block.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isBlock (): bool
	{
		return self::BLOCK === $this->type;
	}

	/**
	 * Returns TRUE if this is a macro.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isMacro (): bool
	{
		return self::MACRO === $this->type;
	}

	/**
	 * Gets the child profiles.
	 *
	 * @return Profile[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getProfiles (): array
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
	public function addProfile (Profile $profile): void
	{
		$this->profiles[] = $profile;
	}

	/**
	 * Gets the duration in microseconds.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getDuration (): int
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
	public function getMemoryUsage (): int
	{
		return isset($this->ends['mu']) && isset($this->starts['mu']) ? $this->ends['mu'] - $this->starts['mu'] : 0;
	}

	/**
	 * Gets the peak memory usage in bytes.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPeakMemoryUsage (): int
	{
		return isset($this->ends['pmu']) && isset($this->starts['pmu']) ? $this->ends['pmu'] - $this->starts['pmu'] : 0;
	}

	/**
	 * Starts the profiling.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enter ()
	{
		$this->starts = [
			'wt' => microtime(true),
			'mu' => memory_get_usage(),
			'pmu' => memory_get_peak_usage(),
		];
	}

	/**
	 * Stops the profiling.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function leave (): void
	{
		$this->ends = [
			'wt' => microtime(true),
			'mu' => memory_get_usage(),
			'pmu' => memory_get_peak_usage(),
		];
	}

	/**
	 * Resets the profiling.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function reset (): void
	{
		$this->starts = $this->ends = $this->profiles = [];
		$this->enter();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getIterator (): ArrayIterator
	{
		return new ArrayIterator($this->profiles);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function serialize (): string
	{
		return serialize([$this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function unserialize ($data): void
	{
		[$this->template, $this->name, $this->type, $this->starts, $this->ends, $this->profiles] = unserialize($data);
	}

}
