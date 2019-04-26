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
use Cappuccino\Error\Error;
use Cappuccino\Error\LoaderError;
use Cappuccino\Error\RuntimeError;
use Cappuccino\Error\SyntaxError;
use Cappuccino\CappuccinoFunction;
use Cappuccino\Template;

/**
 * Class StringLoaderExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
 * @since 1.0.0
 */
final class StringLoaderExtension extends AbstractExtension
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions(): array
	{
		return [
			new CappuccinoFunction('template_from_string', [$this, 'onFunctionTemplateFromString'], ['needs_cappuccino' => true]),
		];
	}

	/**
	 * template_from_string Simple Function.
	 *
	 * @param Cappuccino  $cappuccino
	 * @param string      $template
	 * @param string|null $name
	 *
	 * @return Template
	 * @throws Error
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFunctionTemplateFromString(Cappuccino $cappuccino, string $template, ?string $name = null): Template
	{
		return $cappuccino->createTemplate((string)$template, $name);
	}

}
