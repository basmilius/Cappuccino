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

/**
 * Interface SecurityPolicyInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Sandbox
 * @since 1.0.0
 */
interface SecurityPolicyInterface
{

	/**
	 * Checks the security for tags, filters and functions.
	 *
	 * @param array $tags
	 * @param array $filters
	 * @param array $functions
	 *
	 * @throws SecurityNotAllowedFilterError
	 * @throws SecurityNotAllowedFunctionError
	 * @throws SecurityNotAllowedTagError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function checkSecurity(array $tags, array $filters, array $functions): void;

	/**
	 * Checks if a method is allowed.
	 *
	 * @param mixed $obj
	 * @param mixed $method
	 *
	 * @throws SecurityNotAllowedMethodError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function checkMethodAllowed($obj, $method): void;

	/**
	 * Checks if a property is allowed.
	 *
	 * @param mixed $obj
	 * @param mixed $method
	 *
	 * @throws SecurityNotAllowedPropertyError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function checkPropertyAllowed($obj, $method): void;

}
