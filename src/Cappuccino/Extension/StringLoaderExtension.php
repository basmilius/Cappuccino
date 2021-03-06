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

use Cappuccino\Cappuccino;
use Cappuccino\CappuccinoFunction;
use Cappuccino\Error\Error;
use Cappuccino\TemplateWrapper;

/**
 * Class StringLoaderExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
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
	 * Creates a new template from string.
	 *
	 * @param Cappuccino  $cappuccino
	 * @param string      $template
	 * @param string|null $name
	 *
	 * @return TemplateWrapper
	 * @throws Error
	 * @since 1.0.0
	 * @internal
	 * @author Bas Milius <bas@ideemedia.nl>
	 */
	public final function onFunctionTemplateFromString(Cappuccino $cappuccino, string $template, ?string $name = null): TemplateWrapper
	{
		return $cappuccino->createTemplate($template, $name);
	}

}
