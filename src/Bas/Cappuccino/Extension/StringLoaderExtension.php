<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\Environment;
use Bas\Cappuccino\Error\Error;
use Bas\Cappuccino\Error\LoaderError;
use Bas\Cappuccino\Error\RuntimeError;
use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\SimpleFunction;
use Bas\Cappuccino\Template;

/**
 * Class StringLoaderExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Extension
 * @version 2.3.0
 */
final class StringLoaderExtension extends AbstractExtension
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getFunctions () : array
	{
		return [
			new SimpleFunction('template_from_string', [$this, 'onSimpleFunctionTemplateFromString'], ['needs_environment' => true]),
		];
	}

	/**
	 * template_from_string Simple Function.
	 *
	 * @param Environment $env
	 * @param string      $template
	 *
	 * @return Template
	 * @throws Error
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 * @internal
	 */
	public final function onSimpleFunctionTemplateFromString (Environment $env, string $template) : Template
	{
		return $env->createTemplate((string)$template);
	}

}
