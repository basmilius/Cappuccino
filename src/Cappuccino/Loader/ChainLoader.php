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

namespace Cappuccino\Loader;

use Cappuccino\Error\LoaderError;
use Cappuccino\Source;
use function get_class;
use function implode;
use function sprintf;

/**
 * Class ChainLoader
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Loader
 * @since 1.0.0
 */
final class ChainLoader implements LoaderInterface
{

	/**
	 * @var bool[]
	 */
	private $hasSourceCache = [];

	/**
	 * @var LoaderInterface[]
	 */
	private $loaders = [];

	/**
	 * ChainLoader constructor.
	 *
	 * @param array $loaders
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(array $loaders = [])
	{
		foreach ($loaders as $loader)
			$this->addLoader($loader);
	}

	/**
	 * Adds a new loader.
	 *
	 * @param LoaderInterface $loader
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addLoader(LoaderInterface $loader): void
	{
		$this->loaders[] = $loader;
		$this->hasSourceCache = [];
	}

	/**
	 * Gets all loaders attached to this chain.
	 *
	 * @return LoaderInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getLoaders(): array
	{
		return $this->loaders;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext(string $name): Source
	{
		$exceptions = [];

		foreach ($this->loaders as $loader)
		{
			if (!$loader->exists($name))
				continue;

			try
			{
				return $loader->getSourceContext($name);
			}
			catch (LoaderError $e)
			{
				$exceptions[] = $e->getMessage();
			}
		}

		throw new LoaderError(sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' (' . implode(', ', $exceptions) . ')' : ''));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function exists(string $name): bool
	{
		if (isset($this->hasSourceCache[$name]))
			return $this->hasSourceCache[$name];

		foreach ($this->loaders as $loader)
			if ($loader->exists($name))
				return $this->hasSourceCache[$name] = true;

		return $this->hasSourceCache[$name] = false;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCacheKey(string $name): string
	{
		$exceptions = [];

		foreach ($this->loaders as $loader)
		{
			if (!$loader->exists($name))
				continue;

			try
			{
				return $loader->getCacheKey($name);
			}
			catch (LoaderError $e)
			{
				$exceptions[] = get_class($loader) . ': ' . $e->getMessage();
			}
		}

		throw new LoaderError(sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' (' . implode(', ', $exceptions) . ')' : ''));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isFresh(string $name, int $time): bool
	{
		$exceptions = [];

		foreach ($this->loaders as $loader)
		{
			if (!$loader->exists($name))
				continue;

			try
			{
				return $loader->isFresh($name, $time);
			}
			catch (LoaderError $e)
			{
				$exceptions[] = get_class($loader) . ': ' . $e->getMessage();
			}
		}

		throw new LoaderError(sprintf('Template "%s" is not defined%s.', $name, $exceptions ? ' (' . implode(', ', $exceptions) . ')' : ''));
	}

}
