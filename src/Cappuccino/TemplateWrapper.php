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

namespace Cappuccino;

use Cappuccino\Error\Error;
use Cappuccino\Util\EasyPeasyLemonSqueezy;

/**
 * Class TemplateWrapper
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
final class TemplateWrapper
{

	/**
	 * @var Cappuccino
	 */
	private $cappuccino;

	/**
	 * @var Template
	 */
	private $template;

	/**
	 * TemplateWrapper constructor.
	 *
	 * @param Cappuccino $cappuccino
	 * @param Template   $template
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(Cappuccino $cappuccino, Template $template)
	{
		$this->cappuccino = $cappuccino;
		$this->template = $template;
	}

	/**
	 * Renders the template.
	 *
	 * @param array $context
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function render(array $context = []): string
	{
		/** @noinspection PhpMethodParametersCountMismatchInspection */
		return $this->template->render($context, func_get_args()[1] ?? []);
	}

	/**
	 * Displays the template.
	 *
	 * @param array $context
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function display(array $context = [])
	{
		$this->template->display($context, func_get_args()[1] ?? []);
	}

	/**
	 * Returns TRUE if a block is defined.
	 *
	 * @param string $name
	 * @param array  $context
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasBlock(string $name, array $context = []): bool
	{
		return $this->template->hasBlock($name, $context);
	}

	/**
	 * Returns defined block names in the template.
	 *
	 * @param array $context
	 *
	 * @return string[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBlockNames(array $context = []): array
	{
		return $this->template->getBlockNames($context);
	}

	/**
	 * Renders a template block.
	 *
	 * @param string $name
	 * @param array  $context
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function renderBlock(string $name, array $context = []): string
	{
		$context = $this->cappuccino->mergeGlobals($context);
		$level = ob_get_level();

		if ($this->cappuccino->isDebug())
			ob_start();
		else
			ob_start([EasyPeasyLemonSqueezy::class, 'returnEmptyString']);

		try
		{
			$this->template->displayBlock($name, $context);
		}
		catch (Error $e)
		{
			while (ob_get_level() > $level)
				ob_end_clean();

			throw $e;
		}

		return ob_get_clean();
	}

	/**
	 * Displays a template block.
	 *
	 * @param string $name
	 * @param array  $context
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function displayBlock(string $name, array $context = [])
	{
		$this->template->displayBlock($name, $this->cappuccino->mergeGlobals($context));
	}

	/**
	 * Gets the source context.
	 *
	 * @return Source
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext(): Source
	{
		return $this->template->getSourceContext();
	}

	/**
	 * Gets the template name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTemplateName(): string
	{
		return $this->template->getTemplateName();
	}

	/**
	 * Gets the template.
	 *
	 * @return Template
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function unwrap(): Template
	{
		return $this->template;
	}

}
