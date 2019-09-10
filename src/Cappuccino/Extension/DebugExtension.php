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

namespace Cappuccino\Extension;

use Cappuccino\CappuccinoFunction;

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
		$isDumpOutputHtmlSafe = extension_loaded('xdebug') && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump')) && (false === ini_get('html_errors') || ini_get('html_errors')) || PHP_SAPI === 'cli';

		return [
			new CappuccinoFunction('dump', 'Columba\Util\pre', ['is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [], 'needs_context' => true, 'needs_cappuccino' => true, 'is_variadic' => true]),
		];
	}

}
