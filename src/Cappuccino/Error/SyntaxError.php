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

namespace Cappuccino\Error;

use function array_keys;
use function asort;
use function implode;
use function levenshtein;
use function sprintf;
use function strlen;
use function strpos;

/**
 * Class SyntaxError
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Error
 * @since 1.0.0
 */
class SyntaxError extends Error
{

	/**
	 * Tweaks the error message to include suggestions.
	 *
	 * @param string $name
	 * @param array  $items
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addSuggestions(string $name, array $items): void
	{
		$alternatives = [];

		foreach ($items as $item)
		{
			$lev = levenshtein($name, $item);

			if ($lev <= strlen($name) / 3 || strpos($item, $name) !== false)
				$alternatives[$item] = $lev;
		}

		if (!$alternatives)
			return;

		asort($alternatives);

		$this->appendMessage(sprintf(' Did you mean "%s"?', implode('", "', array_keys($alternatives))));
	}

}
