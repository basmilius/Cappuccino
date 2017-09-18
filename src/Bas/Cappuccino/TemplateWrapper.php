<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Exception;
use Throwable;

/**
 * Class TemplateWrapper
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino
 * @version 2.3.0
 */
final class TemplateWrapper
{

	/**
	 * @var Environment
	 */
	private $environment;

	/**
	 * @var Template
	 */
	private $template;

	/**
	 * TemplateWrapper constructor.
	 *
	 * @param Environment $environment
	 * @param Template    $template
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (Environment $environment, Template $template)
	{
		$this->environment = $environment;
		$this->template = $template;
	}

	/**
	 * Renders the template.
	 *
	 * @param array $context
	 *
	 * @return string
	 * @throws Throwable
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function render ($context = []) : string
	{
		return $this->template->render($context);
	}

	/**
	 * Displays the template.
	 *
	 * @param array $context
	 *
	 * @throws Exception
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function display (array $context = []) : void
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
	 * @throws Error\LoaderError
	 * @throws Exception
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function hasBlock (string $name, array $context = []) : bool
	{
		return $this->template->hasBlock($name, $context);
	}

	/**
	 * Returns defined block names in the template.
	 *
	 * @param array $context
	 *
	 * @return string[]
	 * @throws Error\LoaderError
	 * @throws Exception
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getBlockNames (array $context = []) : array
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
	 * @throws Throwable
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function renderBlock (string $name, array $context = []) : string
	{
		$context = $this->environment->mergeGlobals($context);
		$level = ob_get_level();
		ob_start();

		try
		{
			$this->template->displayBlock($name, $context);
		}
		catch (Throwable $e)
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
	 * @throws Error\Error
	 * @throws Error\LoaderError
	 * @throws Error\RuntimeError
	 * @throws Exception
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function displayBlock (string $name, array $context = []) : void
	{
		$this->template->displayBlock($name, $this->environment->mergeGlobals($context));
	}

	/**
	 * Gets the source context.
	 *
	 * @return Source
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getSourceContext () : Source
	{
		return $this->template->getSourceContext();
	}

}
