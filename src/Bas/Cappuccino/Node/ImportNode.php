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

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\AbstractExpression;
use Bas\Cappuccino\Node\Expression\NameExpression;

/**
 * Class ImportNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @since 1.0.0
 */
class ImportNode extends Node
{

	/**
	 * ImportNode constructor.
	 *
	 * @param AbstractExpression $expr
	 * @param AbstractExpression $var
	 * @param int                $lineno
	 * @param string|null        $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (AbstractExpression $expr, AbstractExpression $var, int $lineno, ?string $tag = null)
	{
		parent::__construct(['expr' => $expr, 'var' => $var], [], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->write('')
			->subcompile($this->getNode('var'))
			->raw(' = ');

		if ($this->getNode('expr') instanceof NameExpression && '_self' === $this->getNode('expr')->getAttribute('name'))
			$compiler->raw('$this');
		else
			$compiler
				->raw('$this->loadTemplate(')
				->subcompile($this->getNode('expr'))
				->raw(', ')
				->repr($this->getTemplateName())
				->raw(', ')
				->repr($this->getTemplateLine())
				->raw(')');

		$compiler->raw(";\n");
	}

}
