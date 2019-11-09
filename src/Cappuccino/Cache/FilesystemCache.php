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

namespace Cappuccino\Cache;

use RuntimeException;
use function apc_compile_file;
use function basename;
use function chmod;
use function clearstatcache;
use function dirname;
use function file_put_contents;
use function filemtime;
use function filter_var;
use function function_exists;
use function ini_get;
use function is_dir;
use function is_file;
use function is_writable;
use function mkdir;
use function opcache_invalidate;
use function rename;
use function rtrim;
use function sprintf;
use function substr;
use function tempnam;
use function umask;
use const FILTER_VALIDATE_BOOLEAN;

/**
 * Class FilesystemCache
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Cache
 * @since 1.0.0
 */
class FilesystemCache implements CacheInterface
{

	public const FORCE_BYTECODE_INVALIDATION = 1;

	/**
	 * @var string
	 */
	private $directory;

	/**
	 * @var int
	 */
	private $options;

	/**
	 * FilesystemCache constructor.
	 *
	 * @param string $directory
	 * @param int    $options
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(string $directory, int $options = 0)
	{
		$this->directory = rtrim($directory, '\/') . '/';
		$this->options = $options;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function generateKey(string $name, string $className): string
	{
		$hash = substr($className, 3);

		return $this->directory . $hash[0] . $hash[1] . '/' . $hash . '.php';
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function load(string $key): void
	{
		if (is_file($key))
			/** @noinspection PhpIncludeInspection */ @include $key;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function write(string $key, string $content): void
	{
		$dir = dirname($key);

		if (!is_dir($dir))
		{
			if (@mkdir($dir, 0777, true) === false)
			{
				clearstatcache(true, $dir);

				if (!is_dir($dir))
					throw new RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));
			}
		}
		else if (!is_writable($dir))
		{
			throw new RuntimeException(sprintf('Unable to write in the cache directory (%s).', $dir));
		}

		$tmpFile = tempnam($dir, basename($key));

		if (@file_put_contents($tmpFile, $content) !== false && @rename($tmpFile, $key))
		{
			@chmod($key, 0666 & ~umask());

			if (self::FORCE_BYTECODE_INVALIDATION == ($this->options & self::FORCE_BYTECODE_INVALIDATION))
			{
				if (function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOLEAN))
					opcache_invalidate($key, true);
				else if (function_exists('apc_compile_file'))
					apc_compile_file($key);
			}

			return;
		}

		throw new RuntimeException(sprintf('Failed to write cache file "%s".', $key));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTimestamp(string $key): int
	{
		return is_file($key) ? filemtime($key) ?: 0 : 0;
	}

}
