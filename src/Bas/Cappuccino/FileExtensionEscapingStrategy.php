<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

/**
 * Class FileExtensionEscapingStrategy
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 2.3.0
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
	 * @since 2.3.0
	 */
	public static function guess (string $name)
	{
		if (in_array(substr($name, -1), ['/', '\\']))
			return 'html';

		if (substr($name, -5) === '.twig')
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
