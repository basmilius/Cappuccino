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
use Cappuccino\Error\LoaderError;
use Cappuccino\Error\RuntimeError;
use Cappuccino\Util\EasyPeasyLemonSqueezy;
use Exception;
use LogicException;
use function array_keys;
use function array_merge;
use function array_unique;
use function get_class;
use function is_array;
use function ob_end_clean;
use function ob_get_clean;
use function ob_get_level;
use function ob_start;
use function sprintf;
use function strrpos;
use function substr;

abstract class Template
{

	public const ANY_CALL = 'any';
	public const ARRAY_CALL = 'array';
	public const METHOD_CALL = 'method';

	protected $parent;
	protected $parents = [];
	protected $cappuccino;
	protected $blocks = [];
	protected $traits = [];
	protected $extensions = [];
	protected $sandbox;

	/**
	 * Template constructor.
	 *
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(Cappuccino $cappuccino)
	{
		$this->cappuccino = $cappuccino;
		$this->extensions = $cappuccino->getExtensions();
	}

	/**
	 * Gets the template name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public abstract function getTemplateName(): string;

	/**
	 * Gets debug info for the template.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public abstract function getDebugInfo(): array;

	/**
	 * Gets the source context for the template.
	 *
	 * @return Source|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public abstract function getSourceContext(): ?Source;

	/**
	 * Gets the parent template.
	 *
	 * @param array $context
	 *
	 * @return Template|TemplateWrapper|bool
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getParent(array $context)
	{
		if ($this->parent !== null)
			return $this->parent;

		try
		{
			$parent = $this->doGetParent($context);

			if ($parent === false)
				return false;

			if ($parent instanceof self || $parent instanceof TemplateWrapper)
				return $this->parents[$parent->getSourceContext()->getName()] = $parent;

			if (!isset($this->parents[$parent]))
				$this->parents[$parent] = $this->loadTemplate($parent);
		}
		catch (LoaderError $e)
		{
			$e->setSourceContext(null);
			$e->guess();

			throw $e;
		}

		return $this->parents[$parent];
	}

	/**
	 * #oldcode: Do get parent.
	 *
	 * @param array $context
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function doGetParent(array $context)
	{
		return false;
	}

	/**
	 * #oldcode: Gets if the template is traitable.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isTraitable(): bool
	{
		return true;
	}

	/**
	 * Displays a parent block. This method is for internal use only and should never be called directly.
	 *
	 * @param string $name
	 * @param array  $context
	 * @param array  $blocks
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public function displayParentBlock($name, array $context, array $blocks = []): void
	{
		if (isset($this->traits[$name]))
			$this->traits[$name][0]->displayBlock($name, $context, $blocks, false);
		else if (false !== $parent = $this->getParent($context))
			$parent->displayBlock($name, $context, $blocks, false);
		else
			throw new RuntimeError(sprintf('The template has no parent and no traits defining the "%s" block.', $name), -1, $this->getSourceContext());
	}

	/**
	 * Displays a block. This method is for internal use only and should never be called directly.
	 *
	 * @param string        $name
	 * @param array         $context
	 * @param Template[][]  $blocks
	 * @param bool          $useBlocks
	 *
	 * @param Template|null $templateContext
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function displayBlock($name, array $context, array $blocks = [], $useBlocks = true, self $templateContext = null): void
	{
		if ($useBlocks && isset($blocks[$name]))
		{
			$template = $blocks[$name][0];
			$block = $blocks[$name][1];
		}
		else if (isset($this->blocks[$name]))
		{
			$template = $this->blocks[$name][0];
			$block = $this->blocks[$name][1];
		}
		else
		{
			$template = null;
			$block = null;
		}

		if ($template !== null && !$template instanceof self)
			throw new LogicException('A block must be a method on a \Cappuccino\Template instance.');

		if ($template !== null)
		{
			try
			{
				$template->$block($context, $blocks);
			}
			catch (Error $e)
			{
				if (!$e->getSourceContext())
					$e->setSourceContext($template->getSourceContext());

				if ($e->getTemplateLine() === -1)
					$e->guess();

				throw $e;
			}
			catch (Exception $e)
			{
				$e = new RuntimeError(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $template->getSourceContext(), $e);
				$e->guess();

				throw $e;
			}
		}
		else if (($parent = $this->getParent($context)) !== false)
		{
			$parent->displayBlock($name, $context, array_merge($this->blocks, $blocks), false, $templateContext ?? $this);
		}
		else if (isset($blocks[$name]))
		{
			throw new RuntimeError(sprintf('Block "%s" should not call parent() in "%s" as the block does not exist in the parent template "%s".', $name, $blocks[$name][0]->getTemplateName(), $this->getTemplateName()), -1, $blocks[$name][0]->getSourceContext());
		}
		else
		{
			throw new RuntimeError(sprintf('Block "%s" on template "%s" does not exist.', $name, $this->getTemplateName()), -1, ($templateContext ?? $this)->getSourceContext());
		}
	}

	/**
	 * Renders a parent block.
	 *
	 * @param string $name
	 * @param array  $context
	 * @param array  $blocks
	 *
	 * @return string
	 * @throws Error
	 * @since 1.0.0
	 * @internal
	 * @author Bas Milius <bas@mili.us>
	 */
	public function renderParentBlock($name, array $context, array $blocks = []): string
	{
		if ($this->cappuccino->isDebug())
			ob_start();
		else
			ob_start([EasyPeasyLemonSqueezy::class, 'returnEmptyString']);

		$this->displayParentBlock($name, $context, $blocks);

		return ob_get_clean();
	}

	/**
	 * Renders a block.
	 *
	 * @param string $name
	 * @param array  $context
	 * @param array  $blocks
	 * @param bool   $useBlocks
	 *
	 * @return string
	 * @throws Error
	 * @since 1.0.0
	 * @internal
	 * @author Bas Milius <bas@mili.us>
	 */
	public function renderBlock($name, array $context, array $blocks = [], $useBlocks = true): string
	{
		if ($this->cappuccino->isDebug())
			ob_start();
		else
			ob_start([EasyPeasyLemonSqueezy::class, 'returnEmptyString']);

		$this->displayBlock($name, $context, $blocks, $useBlocks);

		return ob_get_clean();
	}

	/**
	 * Returns whether a block exists or not in the current context of the template. This method checks blocks defined in the current
	 * template or defined in "used" traits or defined in parent templates.
	 *
	 * @param string $name
	 * @param array  $context
	 * @param array  $blocks
	 *
	 * @return bool
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function hasBlock($name, array $context, array $blocks = []): bool
	{
		if (isset($blocks[$name]))
			return $blocks[$name][0] instanceof self;

		if (isset($this->blocks[$name]))
			return true;

		if (($parent = $this->getParent($context)) !== false)
			return $parent->hasBlock($name, $context);

		return false;
	}

	/**
	 * Returns all block names in the current context of the template. This method checks blocks defined in the current template or
	 * defined in "used" traits or defined in parent templates.
	 *
	 * @param array $context
	 * @param array $blocks
	 *
	 * @return array
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function getBlockNames(array $context, array $blocks = []): array
	{
		$names = array_merge(array_keys($blocks), array_keys($this->blocks));

		if (($parent = $this->getParent($context)) !== false)
			$names = array_merge($names, $parent->getBlockNames($context));

		return array_unique($names);
	}

	/**
	 * Loads a template
	 *
	 * @param Template|string $template
	 * @param string|null     $templateName
	 * @param int|null        $line
	 * @param int|null        $index
	 *
	 * @return Template|TemplateWrapper
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	protected function loadTemplate($template, ?string $templateName = null, ?int $line = null, ?int $index = null)
	{
		try
		{
			if (is_array($template))
				return $this->cappuccino->resolveTemplate($template);

			if ($template instanceof self || $template instanceof TemplateWrapper)
				return $template;

			if ($template === $this->getTemplateName())
			{
				$class = get_class($this);

				if (($pos = strrpos($class, '___', -1)) !== false)
					$class = substr($class, 0, $pos);
			}
			else
			{
				$class = $this->cappuccino->getTemplateClass($template);
			}

			return $this->cappuccino->loadTemplate($class, $template, $index);
		}
		catch (Error $e)
		{
			if (!$e->getSourceContext())
				$e->setSourceContext($templateName !== null ? new Source('', $templateName) : $this->getSourceContext());

			if ($e->getTemplateLine() > 0)
				throw $e;

			if (!$line)
				$e->guess();
			else
				$e->setTemplateLine($line);

			throw $e;
		}
	}

	/**
	 * Gets the template instance.
	 *
	 * @return Template
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	protected function unwrap(): Template
	{
		return $this;
	}

	/**
	 * Returns all blocks.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBlocks(): array
	{
		return $this->blocks;
	}

	/**
	 * Display.
	 *
	 * @param array $context
	 * @param array $blocks
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function display(array $context, array $blocks = []): void
	{
		$this->displayWithErrorHandling($this->cappuccino->mergeGlobals($context), array_merge($this->blocks, $blocks));
	}

	/**
	 * Render.
	 *
	 * @param array $context
	 *
	 * @return string
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function render(array $context): string
	{
		$level = ob_get_level();

		if ($this->cappuccino->isDebug())
			ob_start();
		else
			ob_start([EasyPeasyLemonSqueezy::class, 'returnEmptyString']);

		try
		{
			$this->display($context);
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
	 * Display with error handling.
	 *
	 * @param array $context
	 * @param array $blocks
	 *
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function displayWithErrorHandling(array $context, array $blocks = []): void
	{
		try
		{
			$this->doDisplay($context, $blocks);
		}
		catch (Error $e)
		{
			if (!$e->getSourceContext())
				$e->setSourceContext($this->getSourceContext());

			if ($e->getTemplateLine() === -1)
				$e->guess();

			throw $e;
		}
		catch (Exception $e)
		{
			$e = new RuntimeError(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $this->getSourceContext(), $e);
			$e->guess();

			throw $e;
		}
	}

	/**
	 * Auto-generated method to display the template with the given context.
	 *
	 * @param array $context
	 * @param array $blocks
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected abstract function doDisplay(array $context, array $blocks = []): void;

}
