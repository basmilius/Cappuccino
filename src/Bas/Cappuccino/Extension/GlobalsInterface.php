<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

/**
 * Interface GlobalsInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
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
	public function getGlobals (): array;

}
