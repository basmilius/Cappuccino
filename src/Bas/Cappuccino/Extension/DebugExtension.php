<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\Environment;
use Bas\Cappuccino\SimpleFunction;
use Bas\Cappuccino\Template;

/**
 * Class DebugExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Extension
 * @version 2.3.0
 */
final class DebugExtension extends AbstractExtension
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getFunctions () : array
	{
		$isDumpOutputHtmlSafe = extension_loaded('xdebug') && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump')) && (false === ini_get('html_errors') || ini_get('html_errors')) || 'cli' === PHP_SAPI;

		return [
			new SimpleFunction ('dump', [$this, 'onSimpleFunctionDump'], ['is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [], 'needs_context' => true, 'needs_environment' => true]),
		];
	}

	/**
	 * Var dump.
	 *
	 * @param Environment $environment
	 * @param array       $context
	 * @param array       ...$vars
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public final function onSimpleFunctionDump (Environment $environment, array $context, ...$vars) : string
	{
		if (!$environment->isDebug())
			return '';

		ob_start();

		if (!$vars)
		{
			$vars = [];

			foreach ($context as $key => $value)
				if (!$value instanceof Template)
					$vars[$key] = $value;

			var_dump($vars);
		}
		else
		{
			var_dump(...$vars);
		}

		return ob_get_clean();
	}

}

