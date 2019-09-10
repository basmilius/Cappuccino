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
 * Class SecurityNotAllowedMethodError
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Sandbox
 * @since 1.0.0
 */
final class SecurityNotAllowedMethodError extends SecurityError
{

	/**
	 * @var string
	 */
	private $className;

	/**
	 * @var string
	 */
	private $methodName;

	/**
	 * SecurityNotAllowedMethodError constructor.
	 *
	 * @param string $message
	 * @param string $className
	 * @param string $methodName
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $message, string $className, string $methodName)
	{
		parent::__construct($message);

		$this->className = $className;
		$this->methodName = $methodName;
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
	 * Gets the method name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getMethodName()
	{
		return $this->methodName;
	}

}
