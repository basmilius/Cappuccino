<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Sandbox;

/**
 * Interface SecurityPolicyInterface
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Sandbox
 * @version 2.3.0
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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 * @throws SecurityNotAllowedFilterError
	 * @throws SecurityNotAllowedFunctionError
	 * @throws SecurityNotAllowedTagError
	 */
	public function checkSecurity (array $tags, array $filters, array $functions) : void;

	/**
	 * Checks if a method is allowed.
	 *
	 * @param mixed $obj
	 * @param mixed $method
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 * @throws SecurityNotAllowedMethodError
	 */
	public function checkMethodAllowed ($obj, $method) : void;

	/**
	 * Checks if a property is allowed.
	 *
	 * @param mixed $obj
	 * @param mixed $method
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 * @throws SecurityNotAllowedPropertyError
	 */
	public function checkPropertyAllowed ($obj, $method) : void;

}
