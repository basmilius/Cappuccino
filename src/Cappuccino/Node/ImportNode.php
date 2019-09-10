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

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Node\Expression\NameExpression;

/**
 * Class ImportNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class ImportNode extends Node
{

	/**
	 * ImportNode constructor.
	 *
	 * @param AbstractExpression $expr
	 * @param AbstractExpression $var
	 * @param int                $lineNumber
	 * @param string|null        $tag
	 * @param bool               $global
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $expr, AbstractExpression $var, int $lineNumber, ?string $tag = null, bool $global = true)
	{
		parent::__construct(['expr' => $expr, 'var' => $var], ['global' => $global], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.00.
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->write('$macros[')
			->repr($this->getNode('var')->getAttribute('name'))
			->raw('] = ');

		if ($this->getAttribute('global'))
		{
			$compiler
				->raw('$this->macros[')
				->repr($this->getNode('var')->getAttribute('name'))
				->raw('] = ');
		}

		if ($this->getNode('expr') instanceof NameExpression && '_self' === $this->getNode('expr')->getAttribute('name'))
		{
			$compiler->raw('$this');
		}
		else
		{
			$compiler
				->raw('$this->loadTemplate(')
				->subcompile($this->getNode('expr'))
				->raw(', ')
				->repr($this->getTemplateName())
				->raw(', ')
				->repr($this->getTemplateLine())
				->raw(')->unwrap()');
		}

		$compiler->raw(";\n");
	}

}
