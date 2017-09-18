<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Util;

use Bas\Cappuccino\Environment;
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
 * @version 2.3.0
 */
final class DeprecationCollector
{

	/**
	 * @var Environment
	 */
	private $twig;

	/**
	 * DeprecationCollector constructor.
	 *
	 * @param Environment $twig
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (Environment $twig)
	{
		$this->twig = $twig;
	}

	/**
	 * Returns deprecations for templates container in a directory.
	 *
	 * @param string $dir
	 * @param string $ext
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function collectDir (string $dir, string $ext = '.twig') : array
	{
		$iterator = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::LEAVES_ONLY), '{' . preg_quote($ext) . '$}');

		return $this->collect(new TemplateDirIterator($iterator));
	}

	/**
	 * Returns deprecations for passed templates.
	 *
	 * @param Traversable $iterator
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
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
				$this->twig->parse($this->twig->tokenize(new Source($contents, $name)));
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
