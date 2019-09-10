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

namespace Cappuccino;

/**
 * Class FileExtensionEscapingStrategy
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
class FileExtensionEscapingStrategy
{

	/**
	 * Guesses the best autoescaping strategy based on the file name.
	 *
	 * @param string $name
	 *
	 * @return bool|string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function guess(string $name)
	{
		if (in_array(substr($name, -1), ['/', '\\']))
		{
			return 'html'; // return html for directories
		}

		if ('.cappy' === substr($name, -6))
		{
			$name = substr($name, 0, -6);
		}

		$extension = pathinfo($name, PATHINFO_EXTENSION);

		switch ($extension)
		{
			case 'js':
				return 'js';

			case 'css':
				return 'css';

			case 'txt':
				return false;

			default:
				return 'html';
		}
	}

}
