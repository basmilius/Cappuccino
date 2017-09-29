<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Error\LoaderError;
use Bas\Cappuccino\Error\RuntimeError;
use Exception;

/**
 * Class TemplateWrapper
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 1.0.0
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
	public function __construct (Cappuccino $cappuccino, Template $template)
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
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function render ($context = []): string
	{
		return $this->template->render($context);
	}

	/**
	 * Displays the template.
	 *
	 * @param array $context
	 *
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function display (array $context = []): void
	{
		$this->template->display($context);
	}

	/**
	 * Checks if a block is defined.
	 *
	 * @param string $name
	 * @param array  $context
	 *
	 * @return bool
	 * @throws LoaderError
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasBlock (string $name, array $context = []): bool
	{
		return $this->template->hasBlock($name, $context);
	}

	/**
	 * Returns defined block names in the template.
	 *
	 * @param array $context
	 *
	 * @return string[]
	 * @throws LoaderError
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBlockNames (array $context = []): array
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
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function renderBlock (string $name, array $context = []): string
	{
		$context = $this->cappuccino->mergeGlobals($context);
		$level = ob_get_level();
		ob_start();

		try
		{
			$this->template->displayBlock($name, $context);
		}
		catch (LoaderError | RuntimeError | Exception $e)
		{
			while (ob_get_level() > $level)
			{
				ob_end_clean();
			}

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
	 * @throws Exception
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function displayBlock (string $name, array $context = []): void
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
	public function getSourceContext (): Source
	{
		return $this->template->getSourceContext();
	}

}
