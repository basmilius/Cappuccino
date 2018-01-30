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
 * Interface RuntimeLoaderInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\RuntimeLoader
 * @since 1.0.0
 */
interface RuntimeLoaderInterface
{

	/**
	 * Creates the runtime implementation of a Cappuccino element (filter/function/test).
	 *
	 * @param string $class
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function load (string $class);

}
