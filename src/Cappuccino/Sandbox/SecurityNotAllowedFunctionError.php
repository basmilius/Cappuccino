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

namespace Cappuccino\Sandbox;

/**
 * Class SecurityNotAllowedFunctionError
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Sandbox
 * @since 1.0.0
 */
final class SecurityNotAllowedFunctionError extends SecurityError
{

	/**
	 * @var string
	 */
	private $functionName;

	/**
	 * SecurityNotAllowedFunctionError constructor.
	 *
	 * @param string $message
	 * @param string $functionName
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $message, string $functionName)
	{
		parent::__construct($message);

		$this->functionName = $functionName;
	}

	/**
	 * Gets the function name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getFunctionName(): string
	{
		return $this->functionName;
	}

}
