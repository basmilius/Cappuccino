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

namespace Cappuccino\Extension;

use Cappuccino\Cappuccino;
use Cappuccino\CappuccinoFunction;
use Cappuccino\Template;
use Cappuccino\TemplateWrapper;

/**
 * Class DebugExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
final class DebugExtension extends AbstractExtension
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions(): array
	{
		$isDumpOutputHtmlSafe = extension_loaded('xdebug') && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump')) && (false === ini_get('html_errors') || ini_get('html_errors')) || 'cli' === PHP_SAPI;

		return [
			new CappuccinoFunction ('dump', [$this, 'onFunctionDump'], ['is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [], 'needs_context' => true, 'needs_cappuccino' => true, 'is_variadic' => true]),
		];
	}

	/**
	 * Var dump.
	 *
	 * @param Cappuccino $cappuccino
	 * @param array      $context
	 * @param array      ...$vars
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function onFunctionDump(Cappuccino $cappuccino, array $context, ...$vars): string
	{
		if (!$cappuccino->isDebug())
			return '';

		ob_start();

		if (!$vars)
		{
			$vars = [];

			foreach ($context as $key => $value)
				if (!$value instanceof Template && !$value instanceof TemplateWrapper)
					$vars[$key] = $value;

			var_dump($vars);
		}
		else
		{
			foreach ($vars as $var)
				var_dump($var);
		}

		return ob_get_clean();
	}

}

