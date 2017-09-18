<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\NodeVisitor\SandboxNodeVisitor;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedFilterError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedFunctionError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedMethodError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedPropertyError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedTagError;
use Bas\Cappuccino\Sandbox\SecurityPolicyInterface;
use Bas\Cappuccino\SimpleFilter;
use Bas\Cappuccino\SimpleFunction;
use Bas\Cappuccino\TokenParser\SandboxTokenParser;

/**
 * Class SandboxExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @version 2.3.0
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
	 * @since 2.3.0
	 */
	public function __construct (SecurityPolicyInterface $policy, bool $sandboxed = false)
	{
		$this->policy = $policy;
		$this->sandboxedGlobally = $sandboxed;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getTokenParsers () : array
	{
		return [new SandboxTokenParser()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getNodeVisitors () : array
	{
		return [new SandboxNodeVisitor()];
	}

	/**
	 * Enables the sandbox.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function enableSandbox () : void
	{
		$this->sandboxed = true;
	}

	/**
	 * Disables the sandbox.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function disableSandbox () : void
	{
		$this->sandboxed = false;
	}

	/**
	 * Gets if we're sandboxed.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function isSandboxed () : bool
	{
		return $this->sandboxedGlobally || $this->sandboxed;
	}

	/**
	 * Gets if we're sandboxed globally.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function isSandboxedGlobally () : bool
	{
		return $this->sandboxedGlobally;
	}

	/**
	 * Gets the security policy.
	 *
	 * @return SecurityPolicyInterface
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getSecurityPolicy () : SecurityPolicyInterface
	{
		return $this->policy;
	}

	/**
	 * Sets the security policy.
	 *
	 * @param SecurityPolicyInterface $policy
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function setSecurityPolicy (SecurityPolicyInterface $policy) : void
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
	 * @since 2.3.0
	 */
	public function checkSecurity (array $tags, array $filters, array $functions) : void
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
	 * @since 2.3.0
	 */
	public function checkMethodAllowed ($obj, string $method) : void
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
	 * @since 2.3.0
	 */
	public function checkPropertyAllowed ($obj, string $property) : void
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
	 * @since 2.3.0
	 */
	public function ensureToStringAllowed ($obj)
	{
		if ($this->isSandboxed() && is_object($obj))
			$this->policy->checkMethodAllowed($obj, '__toString');

		return $obj;
	}
}
