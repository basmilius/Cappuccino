<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\Sandbox;

use Exception;

/**
 * Class SecurityNotAllowedFilterError
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Sandbox
 * @since 1.0.0
 */
class SecurityNotAllowedFilterError extends SecurityError
{

	/**
	 * @var string
	 */
	private $filterName;

	/**
	 * SecurityNotAllowedFilterError constructor.
	 *
	 * @param string         $message
	 * @param string         $functionName
	 * @param int            $lineno
	 * @param string|null    $filename
	 * @param Exception|null $previous
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(string $message, string $functionName, int $lineno = -1, ?string $filename = null, Exception $previous = null)
	{
		parent::__construct($message, $lineno, $filename, $previous);

		$this->filterName = $functionName;
	}

	/**
	 * Gets the filter name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilterName(): string
	{
		return $this->filterName;
	}

}
