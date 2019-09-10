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

namespace Cappuccino\Extension;

use Cappuccino\NodeVisitor\SandboxNodeVisitor;
use Cappuccino\Sandbox\SecurityNotAllowedMethodError;
use Cappuccino\Sandbox\SecurityNotAllowedPropertyError;
use Cappuccino\Sandbox\SecurityPolicyInterface;
use Cappuccino\Source;
use Cappuccino\TokenParser\SandboxTokenParser;

/**
 * Class SandboxExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
final class SandboxExtension extends AbstractExtension
{

	/**
	 * @var bool
	 */
	private $sandboxedGlobally;

	/**
	 * @var
	 */
	private $sandboxed;

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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(SecurityPolicyInterface $policy, bool $sandboxed = false)
	{
		$this->policy = $policy;
		$this->sandboxedGlobally = $sandboxed;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers(): array
	{
		return [new SandboxTokenParser()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array
	{
		return [new SandboxNodeVisitor()];
	}

	/**
	 * Enables the sandbox.
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function enableSandbox(): void
	{
		$this->sandboxed = true;
	}

	/**
	 * Disables the sandbox.
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function disableSandbox(): void
	{
		$this->sandboxed = false;
	}

	/**
	 * Returns TRUE if sandbox is enabled.
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function isSandboxed(): bool
	{
		return $this->sandboxedGlobally || $this->sandboxed;
	}

	/**
	 * Returns TRUE if sandbox is enabled for the global scope.
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function isSandboxedGlobally(): bool
	{
		return $this->sandboxedGlobally;
	}

	/**
	 * Sets the {@see SecurityPolicyInterface}.
	 *
	 * @param SecurityPolicyInterface $policy
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setSecurityPolicy(SecurityPolicyInterface $policy): void
	{
		$this->policy = $policy;
	}

	/**
	 * Gets the {@see SecurityPolicyInterface}.
	 *
	 * @return SecurityPolicyInterface
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getSecurityPolicy(): SecurityPolicyInterface
	{
		return $this->policy;
	}

	/**
	 * Checks security of the given tags, filters and functions.
	 *
	 * @param array $tags
	 * @param array $filters
	 * @param array $functions
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function checkSecurity(array $tags, array $filters, array $functions): void
	{
		if ($this->isSandboxed())
			$this->policy->checkSecurity($tags, $filters, $functions);
	}

	/**
	 * Checks if a method is allowed.
	 *
	 * @param mixed       $obj
	 * @param string      $method
	 * @param int         $lineNumber
	 * @param Source|null $source
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function checkMethodAllowed($obj, string $method, int $lineNumber = -1, ?Source $source = null): void
	{
		if (!$this->isSandboxed())
			return;

		try
		{
			$this->policy->checkMethodAllowed($obj, $method);
		}
		catch (SecurityNotAllowedMethodError $e)
		{
			$e->setSourceContext($source);
			$e->setTemplateLine($lineNumber);

			throw $e;
		}
	}

	/**
	 * Checks if a property is allowed.
	 *
	 * @param mixed       $obj
	 * @param string      $property
	 * @param int         $lineNumber
	 * @param Source|null $source
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function checkPropertyAllowed($obj, string $property, int $lineNumber = -1, ?Source $source = null): void
	{
		if (!$this->isSandboxed())
			return;

		try
		{
			$this->policy->checkPropertyAllowed($obj, $property);
		}
		catch (SecurityNotAllowedPropertyError $e)
		{
			$e->setSourceContext($source);
			$e->setTemplateLine($lineNumber);

			throw $e;
		}
	}

	/**
	 * Ensures that __toString is always allowed.
	 *
	 * @param mixed       $obj
	 * @param int         $lineNumber
	 * @param Source|null $source
	 *
	 * @return mixed
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function ensureToStringAllowed($obj, int $lineNumber = -1, ?Source $source = null)
	{
		if ($this->isSandboxed() && is_object($obj) && method_exists($obj, '__toString'))
		{
			try
			{
				$this->policy->checkMethodAllowed($obj, '__toString');
			}
			catch (SecurityNotAllowedMethodError $e)
			{
				$e->setSourceContext($source);
				$e->setTemplateLine($lineNumber);

				throw $e;
			}
		}

		return $obj;
	}

}
