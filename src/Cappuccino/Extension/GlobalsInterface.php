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

namespace Cappuccino\Extension;

/**
 * Interface GlobalsInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
interface GlobalsInterface
{

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getGlobals(): array;

}
