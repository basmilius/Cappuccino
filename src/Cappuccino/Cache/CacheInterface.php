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

namespace Cappuccino\Cache;

/**
 * Interface CacheInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Cache
 * @since 1.0.0
 */
interface CacheInterface
{

	/**
	 * Generates a cache key for the given template class name.
	 *
	 * @param string $name
	 * @param string $className
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function generateKey (string $name, string $className): string;

	/**
	 * Writes the compiled template to cache.
	 *
	 * @param string $key
	 * @param string $content
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function write (string $key, string $content): void;

	/**
	 * Loads a template from the cache.
	 *
	 * @param string $key
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function load (string $key): void;

	/**
	 * Returns the modification timestamp of a key.
	 *
	 * @param string $key
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTimestamp (string $key): int;

}
