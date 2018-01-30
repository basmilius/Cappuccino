<?php
/**
 * This file is part of the Bas\Cappuccino package.
 *
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\RuntimeLoader;

/**
 * Class FactoryRuntimeLoader
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\RuntimeLoader
 * @since 1.0.0
 */
class FactoryRuntimeLoader implements RuntimeLoaderInterface
{

	/**
	 * @var array
	 */
	private $map;

	/**
	 * FactoryRuntimeLoader constructor.
	 *
	 * @param array $map
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (array $map = [])
	{
		$this->map = $map;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function load (string $class)
	{
		if (isset($this->map[$class]))
		{
			$runtimeFactory = $this->map[$class];

			return $runtimeFactory();
		}

		return null;
	}

}
