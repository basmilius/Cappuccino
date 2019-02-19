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

namespace Cappuccino;

use Cappuccino\Node\Expression\FunctionExpression;
use Cappuccino\Node\Node;

/**
 * Class CappuccinoFunction
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
final class CappuccinoFunction
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
	 * CappuccinoFunction constructor.
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
			'node_class' => FunctionExpression::class,
			'deprecated' => false,
			'alternative' => null,
		], $options);
	}

	/**
	 * Gets the name of this function.
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
	 * Gets the callable to execute for this function.
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
	 * Gets the arguments.
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
	 * Sets the arguments.
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
	 * Does this function need our {@see Cappuccino} instance?
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
	 * Does this function need the Node context.
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
	 * Gets function safe status.
	 *
	 * @param Node $functionArgs
	 *
	 * @return array|mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSafe(Node $functionArgs)
	{
		if ($this->options['is_safe'] !== null)
			return $this->options['is_safe'];

		if ($this->options['is_safe_callback'] !== null)
			return $this->options['is_safe_callback']($functionArgs);

		return [];
	}

	/**
	 * Returns TRUE if this function is variadic.
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
	 * Returns TRUE if this function is deprecated.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isDeprecated(): bool
	{
		return (bool)$this->options['deprecated'];
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
		return $this->options['deprecated'];
	}

	/**
	 * Gets an alternative.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getAlternative(): string
	{
		return $this->options['alternative'];
	}

}
