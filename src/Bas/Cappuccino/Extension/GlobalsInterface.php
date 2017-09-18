<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

/**
 * Interface GlobalsInterface
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Extension
 * @version 2.3.0
 */
interface GlobalsInterface
{

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return array
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getGlobals () : array;

}
