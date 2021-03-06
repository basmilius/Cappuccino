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
 * Class SecurityNotAllowedPropertyError
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Sandbox
 * @since 1.0.0
 */
final class SecurityNotAllowedPropertyError extends SecurityError
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
	 * @param string $message
	 * @param string $className
	 * @param string $propertyName
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $message, string $className, string $propertyName)
	{
		parent::__construct($message);

		$this->className = $className;
		$this->propertyName = $propertyName;
	}

	/**
	 * Gets the class name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getClassName(): string
	{
		return $this->className;
	}

	/**
	 * Gets the property name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getPropertyName()
	{
		return $this->propertyName;
	}

}
