<?php
declare(strict_types=1);

namespace Bas\Cappuccino\RuntimeLoader;

/**
 * Interface RuntimeLoaderInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\RuntimeLoader
 * @version 2.3.0
 */
interface RuntimeLoaderInterface
{

	/**
	 * Creates the runtime implementation of a Twig element (filter/function/test).
	 *
	 * @param string $class
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function load (string $class);

}
