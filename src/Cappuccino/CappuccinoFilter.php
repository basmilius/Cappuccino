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

namespace Cappuccino;

use Cappuccino\Node\Expression\FilterExpression;
use Cappuccino\Node\Node;
use function array_merge;
use function is_bool;

/**
 * Class CappuccinoFilter
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
final class CappuccinoFilter
{

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var callable|null
	 */
	private $callable;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var array
	 */
	private $arguments = [];

	/**
	 * CappuccinoFilter constructor.
	 *
	 * @param string        $name
	 * @param callable|null $callable
	 * @param array         $options
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(string $name, ?callable $callable = null, array $options = [])
	{
		$this->name = $name;
		$this->callable = $callable;
		$this->options = array_merge([
			'needs_cappuccino' => false,
			'needs_context' => false,
			'is_variadic' => false,
			'is_safe' => null,
			'is_safe_callback' => null,
			'pre_escape' => null,
			'preserves_safety' => null,
			'node_class' => FilterExpression::class,
			'deprecated' => false,
			'alternative' => null,
		], $options);
	}

	/**
	 * Gets the name of our filter.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Callable to be executed when this filter is used.
	 *
	 * @return callable|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCallable(): ?callable
	{
		return $this->callable;
	}

	/**
	 * Gets the Node class.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeClass(): string
	{
		return $this->options['node_class'];
	}

	/**
	 * Sets the filter arguments.
	 *
	 * @param array $arguments
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setArguments(array $arguments): void
	{
		$this->arguments = $arguments;
	}

	/**
	 * Gets the filter instance.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getArguments(): array
	{
		return $this->arguments;
	}

	/**
	 * Does this filter need a {@see Cappuccino} instance.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function needsCappuccino(): bool
	{
		return $this->options['needs_cappuccino'];
	}

	/**
	 * Does this filter need the Node context.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function needsContext(): bool
	{
		return $this->options['needs_context'];
	}

	/**
	 * Gets the filter safe status.
	 *
	 * @param Node $filterArgs
	 *
	 * @return array|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSafe(Node $filterArgs): ?array
	{
		if ($this->options['is_safe'] !== null)
			return $this->options['is_safe'];

		if ($this->options['is_safe_callback'] !== null)
			return $this->options['is_safe_callback']($filterArgs);

		return null;
	}

	/**
	 * Gets preserves safety.
	 *
	 * @return array|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPreservesSafety(): ?array
	{
		return $this->options['preserves_safety'];
	}

	/**
	 * Gets PRE escape.
	 *
	 * @return string|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPreEscape(): ?string
	{
		return $this->options['pre_escape'];
	}

	/**
	 * Returns TRUE if this filter is variadic.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isVariadic(): bool
	{
		return $this->options['is_variadic'];
	}

	/**
	 * Returns TRUE if this filter is deprecated.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isDeprecated(): bool
	{
		return $this->options['deprecated'];
	}

	/**
	 * Gets the deprecated since version.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getDeprecatedVersion(): string
	{
		return is_bool($this->options['deprecated']) ? '' : $this->options['deprecated'];
	}

	/**
	 * Gets an alternative.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getAlternative(): ?string
	{
		return $this->options['alternative'];
	}

}
