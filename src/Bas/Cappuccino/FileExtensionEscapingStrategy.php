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

namespace Bas\Cappuccino;

/**
 * Class FileExtensionEscapingStrategy
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
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
	public static function guess (string $name)
	{
		if (in_array(substr($name, -1), ['/', '\\']))
			return 'html';

		if (substr($name, -strlen(Cappuccino::DEFAULT_EXTENSION)) === Cappuccino::DEFAULT_EXTENSION)
			$name = substr($name, 0, -5);

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
