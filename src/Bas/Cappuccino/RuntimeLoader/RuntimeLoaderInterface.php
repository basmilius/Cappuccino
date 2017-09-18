<?php
declare(strict_types=1);

namespace Bas\Cappuccino\RuntimeLoader;

/**
 * Interface RuntimeLoaderInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\RuntimeLoader
 * @version 1.0.0
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
