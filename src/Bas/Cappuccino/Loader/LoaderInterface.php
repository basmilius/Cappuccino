<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Bas\Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\Loader;

use Bas\Cappuccino\Error\LoaderError;
use Bas\Cappuccino\Source;

/**
 * Interface LoaderInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Loader
 * @since 1.0.0
 */
interface LoaderInterface
{

	/**
	 * Gets the source context for a given template logical name.
	 *
	 * @param string $name
	 *
	 * @return Source
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext (string $name): Source;

	/**
	 * Gets the cache key to use for the cache for a given template name.
	 *
	 * @param string $name
	 *
	 * @return string
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCacheKey (string $name): string;

	/**
	 * Returns TRUE if the template is still fresh.
	 *
	 * @param string $name
	 * @param int    $time
	 *
	 * @return bool
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isFresh (string $name, int $time): bool;

	/**
	 * Checks if we have the source code of a template, given its name.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function exists (string $name): bool;

}
