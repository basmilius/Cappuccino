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

use Cappuccino\Node\Expression\Test\TestExpression;

/**
 * Class CappuccinoTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
final class CappuccinoTest
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
	 * CappuccinoTest constructor.
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
			'is_variadic' => false,
			'node_class' => TestExpression::class,
			'deprecated' => false,
			'alternative' => null,
		], $options);
	}

	/**
	 * Gets the name of our test.
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
	 * Gets the callable to execute this test.
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
	 * Gets the Node Class.
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
	 * Gets the test arguments.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	/**
	 * Sets the test arguments.
	 *
	 * @param array $arguments
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public function setArguments($arguments): void
	{
		$this->arguments = $arguments;
	}

	/**
	 * Returns TRUE if this test is variadic.
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
	 * Returns TRUE if this test is deprecated.
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
	 * Gets the version when this was deprecated.
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
	public function getAlternative(): ?string
	{
		return $this->options['alternative'] ?? null;
	}

}
