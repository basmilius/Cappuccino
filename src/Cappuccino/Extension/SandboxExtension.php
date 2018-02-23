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

namespace Cappuccino\Extension;

use Cappuccino\NodeVisitor\SandboxNodeVisitor;
use Cappuccino\Sandbox\SecurityNotAllowedFilterError;
use Cappuccino\Sandbox\SecurityNotAllowedFunctionError;
use Cappuccino\Sandbox\SecurityNotAllowedMethodError;
use Cappuccino\Sandbox\SecurityNotAllowedPropertyError;
use Cappuccino\Sandbox\SecurityNotAllowedTagError;
use Cappuccino\Sandbox\SecurityPolicyInterface;
use Cappuccino\SimpleFilter;
use Cappuccino\SimpleFunction;
use Cappuccino\TokenParser\SandboxTokenParser;

/**
 * Class SandboxExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
final class SandboxExtension extends AbstractExtension
{

	/**
	 * @var bool
	 */
	private $sandboxed;

	/**
	 * @var bool
	 */
	private $sandboxedGlobally;

	/**
	 * @var SecurityPolicyInterface
	 */
	private $policy;

	/**
	 * SandboxExtension constructor.
	 *
	 * @param SecurityPolicyInterface $policy
	 * @param bool                    $sandboxed
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (SecurityPolicyInterface $policy, bool $sandboxed = false)
	{
		$this->policy = $policy;
		$this->sandboxedGlobally = $sandboxed;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getTokenParsers (): array
	{
		return [new SandboxTokenParser()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getNodeVisitors (): array
	{
		return [new SandboxNodeVisitor()];
	}

	/**
	 * Enables the sandbox.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function enableSandbox (): void
	{
		$this->sandboxed = true;
	}

	/**
	 * Disables the sandbox.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function disableSandbox (): void
	{
		$this->sandboxed = false;
	}

	/**
	 * Gets if we're sandboxed.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function isSandboxed (): bool
	{
		return $this->sandboxedGlobally || $this->sandboxed;
	}

	/**
	 * Gets if we're sandboxed globally.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function isSandboxedGlobally (): bool
	{
		return $this->sandboxedGlobally;
	}

	/**
	 * Gets the security policy.
	 *
	 * @return SecurityPolicyInterface
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getSecurityPolicy (): SecurityPolicyInterface
	{
		return $this->policy;
	}

	/**
	 * Sets the security policy.
	 *
	 * @param SecurityPolicyInterface $policy
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function setSecurityPolicy (SecurityPolicyInterface $policy): void
	{
		$this->policy = $policy;
	}

	/**
	 * @param array            $tags
	 * @param SimpleFilter[]   $filters
	 * @param SimpleFunction[] $functions
	 *
	 * @throws SecurityNotAllowedFilterError
	 * @throws SecurityNotAllowedFunctionError
	 * @throws SecurityNotAllowedTagError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function checkSecurity (array $tags, array $filters, array $functions): void
	{
		if ($this->isSandboxed())
			$this->policy->checkSecurity($tags, $filters, $functions);
	}

	/**
	 * Checks if a * is allowed.
	 *
	 * @param mixed  $obj
	 * @param string $method
	 *
	 * @throws SecurityNotAllowedMethodError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function checkMethodAllowed ($obj, string $method): void
	{
		if ($this->isSandboxed())
			$this->policy->checkMethodAllowed($obj, $method);
	}

	/**
	 * Checks if a * is allowed.
	 *
	 * @param mixed  $obj
	 * @param string $property
	 *
	 * @throws SecurityNotAllowedPropertyError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function checkPropertyAllowed ($obj, string $property): void
	{
		if ($this->isSandboxed())
			$this->policy->checkPropertyAllowed($obj, $property);
	}

	/**
	 * Ensures that __toString is always allowed.
	 *
	 * @param mixed $obj
	 *
	 * @return mixed
	 * @throws SecurityNotAllowedMethodError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function ensureToStringAllowed ($obj)
	{
		if ($this->isSandboxed() && is_object($obj))
			$this->policy->checkMethodAllowed($obj, '__toString');

		return $obj;
	}
}