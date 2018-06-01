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

namespace Cappuccino;

use Cappuccino\Error\Error;
use Cappuccino\Error\LoaderError;
use Cappuccino\Error\RuntimeError;
use Cappuccino\Extension\ExtensionInterface;
use Cappuccino\Node\BlockNode;
use Exception;
use LogicException;

/**
 * Class Template
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
abstract class Template
{

	public const ANY_CALL = 'any';
	public const ARRAY_CALL = 'array';
	public const METHOD_CALL = 'method';

	/**
	 * @var Cappuccino
	 */
	protected $cappuccino;

	/**
	 * @var Template
	 */
	protected $parent;

	/**
	 * @var Template[]
	 */
	protected $parents = [];

	/**
	 * @var BlockNode[][]
	 */
	protected $blocks = [];

	/**
	 * @var Template[][]
	 */
	protected $traits = [];

	/**
	 * @var ExtensionInterface[]
	 */
	protected $extensions = [];

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
		$this->extensions = $this->cappuccino->getExtensions();
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
	 * Gets debug information about this template.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public abstract function getDebugInfo(): array;

	/**
	 * Gets information about the original template source code.
	 *
	 * @return Source
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext(): Source
	{
		return new Source('', $this->getTemplateName());
	}

	/**
	 * Gets the parent template. This method is for internal use only and should never be called directly.
	 *
	 * @param array $context
	 *
	 * @return Template|bool
	 * @throws LoaderError
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public function getParent(array $context)
	{
		if ($this->parent !== null)
			return $this->parent;

		try
		{
			$parent = $this->doGetParent($context);

			if (!$parent)
				return false;

			if ($parent instanceof self)
				return $this->parents[$parent->getTemplateName()] = $parent;

			if (!isset($this->parents[$parent]))
			{
				$this->parents[$parent] = $this->loadTemplate($parent);
			}
		}
		catch (LoaderError $e)
		{
			$e->setSourceContext(null);
			$e->guess();

			throw $e;
		}

		return $this->parents[$parent];
	}

	/** @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * #oldcode: Do get parent.
	 *
	 * @param array $context
	 *
	 * @return Template|TemplateWrapper|bool
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function doGetParent(array $context)
	{
		return count($context) === -1;
	}

	/**
	 * #oldcode: Gets if this template is traitable.
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
	 * @throws Exception
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public function displayParentBlock(string $name, array $context, array $blocks = [])
	{
		if (isset($this->traits[$name]))
		{
			$this->traits[$name][0]->displayBlock($name, $context, $blocks, false);
		}
		else if (false !== $parent = $this->getParent($context))
		{
			$parent->displayBlock($name, $context, $blocks, false);
		}
		else
		{
			throw new RuntimeError(sprintf('The template has no parent and no traits defining the "%s" block.', $name), -1, $this->getSourceContext());
		}
	}

	/**
	 * Displays a block. This method is for internal use only and should never be called directly.
	 *
	 * @param string        $name
	 * @param array         $context
	 * @param BlockNode[][] $blocks
	 * @param bool          $useBlocks
	 *
	 * @throws Error
	 * @throws Exception
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function displayBlock(string $name, array $context, array $blocks = [], bool $useBlocks = true)
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

		if (null !== $template && !$template instanceof self)
		{
			throw new LogicException('A block must be a method on a Template instance.');
		}

		if (null !== $template)
		{
			try
			{
				$template->$block($context, $blocks);
			}
				/** @noinspection PhpRedundantCatchClauseInspection */
			catch (Error $e)
			{
				if (!$e->getSourceContext())
					$e->setSourceContext($template->getSourceContext());

				if (false === $e->getTemplateLine())
				{
					$e->setTemplateLine(-1);
					$e->guess();
				}

				throw $e;
			}
			catch (Exception $e)
			{
				throw new RuntimeError(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $template->getSourceContext(), $e);
			}
		}
		else if (false !== $parent = $this->getParent($context))
		{
			$parent->displayBlock($name, $context, array_merge($this->blocks, $blocks), false);
		}
		else if (isset($blocks[$name]))
		{
			throw new RuntimeError(sprintf('Block "%s" should not call parent() in "%s" as the block does not exist in the parent template "%s".', $name, $blocks[$name][0]->getTemplateName(), $this->getTemplateName()), -1, $blocks[$name][0]->getTemplateName());
		}
		else
		{
			throw new RuntimeError(sprintf('Block "%s" on template "%s" does not exist.', $name, $this->getTemplateName()), -1, $this->getTemplateName());
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
	 * @throws Exception
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public function renderParentBlock(string $name, array $context, array $blocks = []): string
	{
		ob_start();
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
	 * @throws Exception
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @internal
	 */
	public function renderBlock(string $name, array $context, array $blocks = [], bool $useBlocks = true): string
	{
		ob_start();
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
	 * @throws Exception
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasBlock(string $name, array $context, array $blocks = []): bool
	{
		if (isset($blocks[$name]))
			return $blocks[$name][0] instanceof self;

		if (isset($this->blocks[$name]))
			return true;

		if ($parent = $this->getParent($context))
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
	 * @throws Exception
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBlockNames(array $context, array $blocks = [])
	{
		$names = array_merge(array_keys($blocks), array_keys($this->blocks));

		if ($parent = $this->getParent($context))
		{
			$names = array_merge($names, $parent->getBlockNames($context));
		}

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
	 * @return Template
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function loadTemplate($template, ?string $templateName = null, ?int $line = null, ?int $index = null)
	{
		try
		{
			if (is_array($template))
				return $this->cappuccino->resolveTemplate($template);

			if ($template instanceof self)
				return $template;

			if ($template instanceof TemplateWrapper)
				return $template;

			return $this->cappuccino->loadTemplate($template, $index);
		}
		catch (Error $e)
		{
			if (!$e->getSourceContext())
				$e->setSourceContext($templateName ? new Source('', $templateName) : $this->getSourceContext());

			if ($e->getTemplateLine())
				throw $e;

			if (!$line)
				$e->guess();
			else
				$e->setTemplateLine($line);

			throw $e;
		}
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
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function display(array $context, array $blocks = [])
	{
		$this->displayWithErrorHandling($this->cappuccino->mergeGlobals($context), array_merge($this->blocks, $blocks));
	}

	/**
	 * Render.
	 *
	 * @param array $context
	 *
	 * @return string
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function render(array $context)
	{
		$level = ob_get_level();
		ob_start();

		try
		{
			$this->display($context);
		}
		catch (RuntimeError $e)
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
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function displayWithErrorHandling(array $context, array $blocks = []): void
	{
		try
		{
			$this->doDisplay($context, $blocks);
		}
		catch (RuntimeError $e)
		{
			if (!$e->getSourceContext())
				$e->setSourceContext($this->getSourceContext());

			if (false === $e->getTemplateLine())
			{
				$e->setTemplateLine(-1);
				$e->guess();
			}

			throw $e;
		}
		catch (Exception $e)
		{
			throw new RuntimeError(sprintf('An exception has been thrown during the rendering of a template ("%s").', $e->getMessage()), -1, $this->getSourceContext(), $e);
		}
	}

	/**
	 * Auto-generated method to display the template with the given context.
	 *
	 * @param array $context
	 * @param array $blocks
	 *
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected abstract function doDisplay(array $context, array $blocks = []): void;

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __toString()
	{
		return $this->getTemplateName();
	}

}
