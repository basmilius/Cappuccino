<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Cache;

/**
 * Class NullCache
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Cache
 * @since 1.0.0
 */
final class NullCache implements CacheInterface
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function generateKey (string $name, string $className): string
	{
		return '';
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function write (string $key, string $content): void
	{
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function load (string $key): void
	{
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTimestamp (string $key): int
	{
		return 0;
	}

}
