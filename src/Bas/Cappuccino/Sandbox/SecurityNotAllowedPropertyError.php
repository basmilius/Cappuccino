<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Sandbox;

use Exception;

/**
 * Class SecurityNotAllowedPropertyError
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Sandbox
 * @since 1.0.0
 */
class SecurityNotAllowedPropertyError extends SecurityError
{

	/**
	 * @var string
	 */
	private $className;

	/**
	 * @var string
	 */
	private $propertyName;

	/**
	 * SecurityNotAllowedPropertyError constructor.
	 *
	 * @param string         $message
	 * @param string         $className
	 * @param string         $propertyName
	 * @param int            $lineno
	 * @param string|null    $filename
	 * @param Exception|null $previous
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $message, string $className, string $propertyName, int $lineno = -1, ?string $filename = null, Exception $previous = null)
	{
		parent::__construct($message, $lineno, $filename, $previous);

		$this->className = $className;
		$this->propertyName = $propertyName;
	}

	/**
	 * Gets the class name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getClassName ()
	{
		return $this->className;
	}

	/**
	 * Gets the property name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPropertyName ()
	{
		return $this->propertyName;
	}

}
