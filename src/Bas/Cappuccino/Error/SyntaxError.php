<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Error;

/**
 * Class SyntaxError
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Error
 * @version 2.3.0
 */
final class SyntaxError extends Error
{

	/**
	 * Tweaks the error message to include suggestions.
	 *
	 * @param string $name
	 * @param array  $items
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function addSuggestions (string $name, array $items)
	{
		$alternatives = [];

		foreach ($items as $item)
		{
			$lev = levenshtein($name, $item);

			if ($lev <= strlen($name) / 3 || false !== strpos($item, $name))
				$alternatives[$item] = $lev;
		}

		if (!$alternatives)
			return;

		asort($alternatives);

		$this->appendMessage(sprintf(' Did you mean "%s"?', implode('", "', array_keys($alternatives))));
	}
}
