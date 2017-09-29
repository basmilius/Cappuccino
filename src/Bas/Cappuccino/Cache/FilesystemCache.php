<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Cache;

use RuntimeException;

/**
 * Class FilesystemCache
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Cache
 * @version 1.0.0
 */
class FilesystemCache implements CacheInterface
{

	public const FORCE_BYTECODE_INVALIDATION = 1;

	private $directory;
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
	public function __construct (string $directory, int $options = 0)
	{
		$this->directory = rtrim($directory, '\/') . '/';
		$this->options = $options;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function generateKey (string $name, string $className): string
	{
		$hash = hash('sha256', $className);

		return $this->directory . $hash[0] . $hash[1] . '/' . $hash . '.php';
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function load (string $key): void
	{
		if (file_exists($key))
		{
			/** @noinspection PhpIncludeInspection */
			@include_once $key;
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function write (string $key, string $content): void
	{
		$dir = dirname($key);
		if (!is_dir($dir))
		{
			if (false === @mkdir($dir, 0777, true))
			{
				clearstatcache(true, $dir);
				if (!is_dir($dir))
				{
					throw new RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));
				}
			}
		}
		else if (!is_writable($dir))
		{
			throw new RuntimeException(sprintf('Unable to write in the cache directory (%s).', $dir));
		}

		$tmpFile = tempnam($dir, basename($key));
		if (false !== @file_put_contents($tmpFile, $content) && @rename($tmpFile, $key))
		{
			@chmod($key, 0666 & ~umask());

			if (self::FORCE_BYTECODE_INVALIDATION == ($this->options & self::FORCE_BYTECODE_INVALIDATION))
			{
				// Compile cached file into bytecode cache
				if (function_exists('opcache_invalidate'))
				{
					opcache_invalidate($key, true);
				}
				else if (function_exists('apc_compile_file'))
				{
					apc_compile_file($key);
				}
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
	public function getTimestamp (string $key): int
	{
		if (!file_exists($key))
			return 0;

		return (int)@filemtime($key);
	}

}
