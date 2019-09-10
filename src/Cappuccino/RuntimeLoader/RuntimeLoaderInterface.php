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

/**
 * Interface RuntimeLoaderInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\RuntimeLoader
 * @since 1.0.0
 */
interface RuntimeLoaderInterface
{

	/**
	 * Creates the runtime implementation of a Cappuccino element (filter/function/test).
	 *
	 * @param string $class
	 *
	 * @return object|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function load(string $class);

}
