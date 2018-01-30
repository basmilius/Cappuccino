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

namespace Bas\Cappuccino\Sandbox;

use Exception;

/**
 * Class SecurityNotAllowedTagError
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Sandbox
 * @since 1.0.0
 */
class SecurityNotAllowedTagError extends SecurityError
{

	/**
	 * @var string
	 */
	private $tagName;

	/**
	 * SecurityNotAllowedTagError constructor.
	 *
	 * @param string         $message
	 * @param string         $tagName
	 * @param int            $lineno
	 * @param string|null    $filename
	 * @param Exception|null $previous
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $message, string $tagName, int $lineno = -1, ?string $filename = null, Exception $previous = null)
	{
		parent::__construct($message, $lineno, $filename, $previous);

		$this->tagName = $tagName;
	}

	/**
	 * Gets the tag name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTagName (): string
	{
		return $this->tagName;
	}

}
