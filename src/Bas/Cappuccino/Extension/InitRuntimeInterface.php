<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\Cappuccino;

/**
 * Interface InitRuntimeInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @version 1.0.0
 */
interface InitRuntimeInterface
{

	/**
	 * Initializes the runtime environment. This is where you can load some file that contains filter functions for instance.
	 *
	 * @param Cappuccino $environment
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function initRuntime (Cappuccino $environment);

}
