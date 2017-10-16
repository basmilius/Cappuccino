<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\Cappuccino;

/**
 * Interface InitRuntimeInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @since 1.0.0
 */
interface InitRuntimeInterface
{

	/**
	 * Initializes the runtime Cappuccino. This is where you can load some file that contains filter functions for instance.
	 *
	 * @param Cappuccino $cappuccino
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function initRuntime (Cappuccino $cappuccino);

}
