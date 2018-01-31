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

namespace Bas\Cappuccino\Sandbox;

use Exception;

/**
 * Class SecurityNotAllowedMethodError
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Sandbox
 * @since 1.0.0
 */
class SecurityNotAllowedMethodError extends SecurityError
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
	 * @param string         $message
	 * @param string         $className
	 * @param string         $methodName
	 * @param int            $lineno
	 * @param string|null    $filename
	 * @param Exception|null $previous
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $message, string $className, string $methodName, int $lineno = -1, ?string $filename = null, Exception $previous = null)
	{
		parent::__construct($message, $lineno, $filename, $previous);

		$this->className = $className;
		$this->methodName = $methodName;
	}

	/**
	 * Gets the class name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since string
	 */
	public function getClassName (): string
	{
		return $this->className;
	}

	/**
	 * Gets the method name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since string
	 */
	public function getMethodName (): string
	{
		return $this->methodName;
	}

}
