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

use Cappuccino\Markup;
use Cappuccino\Template;

/**
 * Class SecurityPolicy
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Sandbox
 * @since 1.0.0
 */
final class SecurityPolicy implements SecurityPolicyInterface
{

	/**
	 * @var array
	 */
	private $allowedTags;

	/**
	 * @var array
	 */
	private $allowedFilters;

	/**
	 * @var
	 */
	private $allowedMethods;

	/**
	 * @var array
	 */
	private $allowedProperties;

	/**
	 * @var array
	 */
	private $allowedFunctions;

	/**
	 * SecurityPolicy constructor.
	 *
	 * @param array $allowedTags
	 * @param array $allowedFilters
	 * @param array $allowedMethods
	 * @param array $allowedProperties
	 * @param array $allowedFunctions
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(array $allowedTags = [], array $allowedFilters = [], array $allowedMethods = [], array $allowedProperties = [], array $allowedFunctions = [])
	{
		$this->allowedTags = $allowedTags;
		$this->allowedFilters = $allowedFilters;
		$this->setAllowedMethods($allowedMethods);
		$this->allowedProperties = $allowedProperties;
		$this->allowedFunctions = $allowedFunctions;
	}

	/**
	 * Sets the allowed tags.
	 *
	 * @param array $tags
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setAllowedTags(array $tags): void
	{
		$this->allowedTags = $tags;
	}

	/**
	 * Sets the allowed filters.
	 *
	 * @param array $filters
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setAllowedFilters(array $filters): void
	{
		$this->allowedFilters = $filters;
	}

	/**
	 * Sets the allowed methods.
	 *
	 * @param array $methods
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setAllowedMethods(array $methods): void
	{
		$this->allowedMethods = [];
		foreach ($methods as $class => $m)
		{
			$this->allowedMethods[$class] = array_map('strtolower', is_array($m) ? $m : [$m]);
		}
	}

	/**
	 * Sets the allowed properties.
	 *
	 * @param array $properties
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setAllowedProperties(array $properties): void
	{
		$this->allowedProperties = $properties;
	}

	/**
	 * Sets the allowed functions.
	 *
	 * @param array $functions
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setAllowedFunctions(array $functions): void
	{
		$this->allowedFunctions = $functions;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function checkSecurity(array $tags, array $filters, array $functions): void
	{
		foreach ($tags as $tag)
			if (!in_array($tag, $this->allowedTags))
				throw new SecurityNotAllowedTagError(sprintf('Tag "%s" is not allowed.', $tag), $tag);

		foreach ($filters as $filter)
			if (!in_array($filter, $this->allowedFilters))
				throw new SecurityNotAllowedFilterError(sprintf('Filter "%s" is not allowed.', $filter), $filter);

		foreach ($functions as $function)
			if (!in_array($function, $this->allowedFunctions))
				throw new SecurityNotAllowedFunctionError(sprintf('Function "%s" is not allowed.', $function), $function);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function checkMethodAllowed($obj, $method): void
	{
		if ($obj instanceof Template || $obj instanceof Markup)
			return;

		$allowed = false;
		$method = strtolower($method);

		foreach ($this->allowedMethods as $class => $methods)
			if ($obj instanceof $class)
			{
				$allowed = in_array($method, $methods);

				break;
			}

		if (!$allowed)
		{
			$class = get_class($obj);

			throw new SecurityNotAllowedMethodError(sprintf('Calling "%s" method on a "%s" object is not allowed.', $method, $class), $class, $method);
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function checkPropertyAllowed($obj, $property): void
	{
		$allowed = false;

		foreach ($this->allowedProperties as $class => $properties)
		{
			if ($obj instanceof $class)
			{
				$allowed = in_array($property, is_array($properties) ? $properties : [$properties]);

				break;
			}
		}

		if (!$allowed)
		{
			$class = get_class($obj);
			throw new SecurityNotAllowedPropertyError(sprintf('Calling "%s" property on a "%s" object is not allowed.', $property, $class), $class, $property);
		}
	}

}
