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

/**
 * Class Source
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
final class Source
{

	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var string|null
	 */
	private $name;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * Source constructor.
	 *
	 * @param string      $code
	 * @param string|null $name
	 * @param string      $path
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(string $code, ?string $name, string $path = '')
	{
		$this->code = $code;
		$this->name = $name;
		$this->path = $path;
	}

	/**
	 * Gets the code.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * Gets the name.
	 *
	 * @return string|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * Gets the path.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPath(): string
	{
		return $this->path;
	}

}
