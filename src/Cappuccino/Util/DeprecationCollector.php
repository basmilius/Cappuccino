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

namespace Cappuccino\Util;

use Cappuccino\Cappuccino;
use Cappuccino\Error\Error;
use Cappuccino\Error\SyntaxError;
use Cappuccino\Source;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Traversable;
use function preg_quote;
use function restore_error_handler;
use function set_error_handler;

/**
 * Class DeprecationCollector
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Util
 * @since 1.0.0
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
	public function __construct(Cappuccino $cappuccino)
	{
		$this->cappuccino = $cappuccino;
	}

	/**
	 * Returns deprecations for templates contained in the given directory.
	 *
	 * @param string $dir
	 * @param string $ext
	 *
	 * @return array
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function collectDir(string $dir, string $ext = '.cappy'): array
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
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function collect(Traversable $iterator): array
	{
		$deprecations = [];
		set_error_handler(function ($type, $msg) use (&$deprecations)
		{
			if ($type === E_USER_DEPRECATED)
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
