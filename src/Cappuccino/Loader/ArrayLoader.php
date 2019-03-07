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

namespace Cappuccino\Loader;

use Cappuccino\Error\LoaderError;
use Cappuccino\Source;

/**
 * Class ArrayLoader
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Loader
 * @since 1.0.0
 */
final class ArrayLoader implements LoaderInterface, SourceContextLoaderInterface
{

	/**
	 * @var string[]
	 */
	private $templates = [];

	/**
	 * ArrayLoader constructor.
	 *
	 * @param string[] $templates
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(array $templates = [])
	{
		$this->templates = $templates;
	}

	/**
	 * Sets a template.
	 *
	 * @param string $name
	 * @param string $template
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setTemplate(string $name, string $template): void
	{
		$this->templates[$name] = $template;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext(string $name): Source
	{
		$name = (string)$name;

		if (!isset($this->templates[$name]))
			throw new LoaderError(sprintf('Template "%s" is not defined.', $name));

		return new Source($this->templates[$name], $name);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function exists(string $name): bool
	{
		return isset($this->templates[$name]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCacheKey(string $name): string
	{
		if (!isset($this->templates[$name]))
			throw new LoaderError(sprintf('Template "%s" is not defined.', $name));

		return $name . ':' . $this->templates[$name];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isFresh(string $name, int $time): bool
	{
		if (!isset($this->templates[$name]))
			throw new LoaderError(sprintf('Template "%s" is not defined.', $name));

		return true;
	}

}
