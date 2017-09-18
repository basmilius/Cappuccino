<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Cache;

/**
 * Interface CacheInterface
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Cache
 * @version 2.3.0
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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function generateKey (string $name, string $className) : string;

	/**
	 * Writes the compiled template to cache.
	 *
	 * @param string $key
	 * @param string $content
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function write (string $key, string $content) : void;

	/**
	 * Loads a template from the cache.
	 *
	 * @param string $key
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function load (string $key) : void;

	/**
	 * Returns the modification timestamp of a key.
	 *
	 * @param string $key
	 *
	 * @return int
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getTimestamp (string $key) : int;

}
