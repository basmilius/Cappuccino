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

namespace Cappuccino\RuntimeLoader;

use Psr\Container\ContainerInterface;

/**
 * Class ContainerRuntimeLoader
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\RuntimeLoader
 * @since 2.0.0
 */
class ContainerRuntimeLoader implements RuntimeLoaderInterface
{

	/**
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * ContainerRuntimeLoader constructor.
	 *
	 * @param ContainerInterface $container
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function load(string $class)
	{
		return $this->container->has($class) ? $this->container->get($class) : null;
	}

}
