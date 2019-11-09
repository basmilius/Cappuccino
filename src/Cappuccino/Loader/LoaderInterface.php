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

namespace Cappuccino\Loader;

use Cappuccino\Error\LoaderError;
use Cappuccino\Source;

/**
 * Interface LoaderInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Loader
 * @since 1.0.0
 */
interface LoaderInterface
{

	/**
	 * Gets the source context for a given template name.
	 *
	 * @param string $name
	 *
	 * @return Source
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext(string $name): Source;

	/**
	 * Generates a cache key for a given template name.
	 *
	 * @param string $name
	 *
	 * @return string
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCacheKey(string $name): string;

	/**
	 * Returns TRUE if the template of the given template name is still fresh.
	 *
	 * @param string $name
	 * @param int    $time
	 *
	 * @return bool
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isFresh(string $name, int $time): bool;

	/**
	 * Returns TRUE if we have source code for a given template name.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since
	 */
	public function exists(string $name): bool;
}
