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

use Cappuccino\Cappuccino;
use Cappuccino\Error\LoaderError;
use Cappuccino\Source;

/**
 * Class FilesystemLoader
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Loader
 * @since 1.0.0
 */
class FilesystemLoader implements LoaderInterface, ExistsLoaderInterface, SourceContextLoaderInterface
{

	public const MAIN_NAMESPACE = 'CappuccinoMain';

	protected $paths = [];
	protected $cache = [];
	protected $errorCache = [];

	private $rootPath;

	/**
	 * FilesystemLoader constructor.
	 *
	 * @param array       $paths
	 * @param string|null $rootPath
	 *
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (array $paths = [], ?string $rootPath = null)
	{
		$this->rootPath = ($rootPath === null ? getcwd() : $rootPath) . DIRECTORY_SEPARATOR;

		if ($realPath = realpath($rootPath ?? ''))
			$this->rootPath = $realPath . DIRECTORY_SEPARATOR;

		if ($paths)
			$this->setPaths($paths);
	}

	/**
	 * Gets the paths.
	 *
	 * @param string $namespace
	 *
	 * @return string[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getPaths (string $namespace = self::MAIN_NAMESPACE): array
	{
		return isset($this->paths[$namespace]) ? $this->paths[$namespace] : [];
	}

	/**
	 * Gets a list with namespaces.
	 *
	 * @return string[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNamespaces (): array
	{
		return array_keys($this->paths);
	}

	/**
	 * Sets paths.
	 *
	 * @param array  $paths
	 * @param string $namespace
	 *
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setPaths (array $paths, string $namespace = self::MAIN_NAMESPACE): void
	{
		if (!is_array($paths))
			$paths = [$paths];

		$this->paths[$namespace] = [];

		foreach ($paths as $path)
			$this->addPath($path, $namespace);
	}

	/**
	 * Adds a path.
	 *
	 * @param string $path
	 * @param string $namespace
	 *
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addPath (string $path, string $namespace = self::MAIN_NAMESPACE): void
	{
		$this->cache = $this->errorCache = [];

		$checkPath = $this->isAbsolutePath($path) ? $path : $this->rootPath . $path;

		if (!is_dir($checkPath))
			throw new LoaderError(sprintf('The "%s" directory does not exist ("%s").', $path, $checkPath));

		$this->paths[$namespace][] = rtrim($path, '/\\');
	}

	/**
	 * Prepend a path.
	 *
	 * @param string $path
	 * @param string $namespace
	 *
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function prependPath (string $path, string $namespace = self::MAIN_NAMESPACE)
	{
		$this->cache = $this->errorCache = [];

		$checkPath = $this->isAbsolutePath($path) ? $path : $this->rootPath . $path;

		if (!is_dir($checkPath))
			throw new LoaderError(sprintf('The "%s" directory does not exist ("%s").', $path, $checkPath));

		$path = rtrim($path, '/\\');

		if (!isset($this->paths[$namespace]))
			$this->paths[$namespace][] = $path;
		else
			array_unshift($this->paths[$namespace], $path);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext (string $name): Source
	{
		$path = $this->findTemplate($name);

		return new Source(file_get_contents($path), $name, $path);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCacheKey (string $name): string
	{
		$path = $this->findTemplate($name);
		$len = strlen($this->rootPath);

		if (strncmp($this->rootPath, $path, $len) === 0)
			return substr($path, $len);

		return $path;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function exists (string $name): bool
	{
		$name = $this->normalizeName($name);

		if (isset($this->cache[$name]))
			return true;

		return $this->findTemplate($name, false);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isFresh (string $name, int $time): bool
	{
		return filemtime($this->findTemplate($name)) <= $time;
	}

	/**
	 * Tries to find a template.
	 *
	 * @param string $name
	 * @param bool   $throw
	 *
	 * @return string|bool|mixed
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function findTemplate (string $name, bool $throw = true)
	{
		$name = $this->normalizeName($name);

		if (isset($this->cache[$name]))
			return $this->cache[$name];

		if (isset($this->errorCache[$name]))
		{
			if (!$throw)
				return false;

			throw new LoaderError($this->errorCache[$name]);
		}

		$this->validateName($name);

		[$namespace, $shortname] = $this->parseName($name);

		if (!isset($this->paths[$namespace]))
		{
			$this->errorCache[$name] = sprintf('There are no registered paths for namespace "%s".', $namespace);

			if (!$throw)
				return false;

			throw new LoaderError($this->errorCache[$name]);
		}

		foreach ($this->paths[$namespace] as $path)
		{
			if (!$this->isAbsolutePath($path))
				$path = $this->rootPath . '/' . $path;

			if (is_file($path . '/' . $shortname))
			{
				if ($realpath = realpath($path . '/' . $shortname))
					return $this->cache[$name] = $realpath;

				return $this->cache[$name] = $path . '/' . $shortname;
			}

			if (is_file($path . '/' . $shortname . Cappuccino::DEFAULT_EXTENSION))
			{
				if ($realpath = realpath($path . '/' . $shortname . Cappuccino::DEFAULT_EXTENSION))
					return $this->cache[$name] = $realpath;

				return $this->cache[$name] = $path . '/' . $shortname . Cappuccino::DEFAULT_EXTENSION;
			}
		}

		$this->errorCache[$name] = sprintf('Unable to find template "%s" (looked into: %s).', $name, implode(', ', $this->paths[$namespace]));

		if (!$throw)
			return false;

		throw new LoaderError($this->errorCache[$name]);
	}

	/**
	 * Normalizes the name.
	 *
	 * @param string $name
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function normalizeName (string $name): string
	{
		return preg_replace('#/{2,}#', '/', str_replace('\\', '/', $name));
	}

	/**
	 * Parses a name.
	 *
	 * @param string $name
	 * @param string $default
	 *
	 * @return array
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function parseName (string $name, string $default = self::MAIN_NAMESPACE): array
	{
		if (isset($name[0]) && '@' === $name[0])
		{
			if (!($pos = strpos($name, '/')))
				throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));

			$namespace = substr($name, 1, $pos - 1);
			$shortname = substr($name, $pos + 1);

			return [$namespace, $shortname];
		}

		return [$default, $name];
	}

	/**
	 * Validates a filename.
	 *
	 * @param string $name
	 *
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function validateName (string $name): void
	{
		if (strpos($name, "\0"))
			throw new LoaderError('A template name cannot contain NUL bytes.');

		$name = ltrim($name, '/');
		$parts = explode('/', $name);
		$level = 0;

		foreach ($parts as $part)
		{
			if ('..' === $part)
				--$level;
			else if ('.' !== $part)
				++$level;

			if ($level < 0)
				throw new LoaderError(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
		}
	}

	/**
	 * Returns TRUE if the {@see $file} is an absolute path.
	 *
	 * @param string $file
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function isAbsolutePath (string $file): bool
	{
		return strspn($file, '/\\', 0, 1) || (strlen($file) > 3 && ctype_alpha($file[0]) && ':' === $file[1] && strspn($file, '/\\', 2, 1)) || null !== parse_url($file, PHP_URL_SCHEME);
	}

}
