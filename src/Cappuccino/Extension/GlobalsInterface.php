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

namespace Cappuccino\Extension;

/**
 * Interface GlobalsInterface
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @since 1.0.0
 * @deprecated Implement this only if you really need it in your extensions.
 * @package Cappuccino\Extension
 */
interface GlobalsInterface
{

	/**
	 * Returns a list of globals to merge with the context.
	 *
	 * @return array
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getGlobals(): array;

}
