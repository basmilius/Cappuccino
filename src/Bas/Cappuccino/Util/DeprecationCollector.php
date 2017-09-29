<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Util;

use Bas\Cappuccino\Cappuccino;
use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Source;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Traversable;

/**
 * Class DeprecationCollector
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Util
 * @version 1.0.0
 */
final class DeprecationCollector
{

	/**
	 * @var Cappuccino
	 */
	private $cappuccino;

	/**
	 * DeprecationCollector constructor.
	 *
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Cappuccino $cappuccino)
	{
		$this->cappuccino = $cappuccino;
	}

	/**
	 * Returns deprecations for templates container in a directory.
	 *
	 * @param string $directory
	 * @param string $extension
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function collectDir (string $directory, string $extension = Cappuccino::DEFAULT_EXTENSION): array
	{
		$iterator = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::LEAVES_ONLY), '{' . preg_quote($extension) . '$}');

		return $this->collect(new TemplateDirIterator($iterator));
	}

	/**
	 * Returns deprecations for passed templates.
	 *
	 * @param Traversable $iterator
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function collect (Traversable $iterator)
	{
		$deprecations = [];
		set_error_handler(function ($type, $msg) use (&$deprecations)
		{
			if (E_USER_DEPRECATED === $type)
				$deprecations[] = $msg;
		});

		foreach ($iterator as $name => $contents)
		{
			try
			{
				$this->cappuccino->parse($this->cappuccino->tokenize(new Source($contents, $name)));
			}
			catch (SyntaxError $e)
			{
				// ignore templates containing syntax errors
			}
		}

		restore_error_handler();

		return $deprecations;
	}

}
