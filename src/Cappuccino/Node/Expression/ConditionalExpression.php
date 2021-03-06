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

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;

/**
 * Class ConditionalExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
class ConditionalExpression extends AbstractExpression
{

	/**
	 * ConditionalExpression constructor.
	 *
	 * @param AbstractExpression $expr1
	 * @param AbstractExpression $expr2
	 * @param AbstractExpression $expr3
	 * @param int                $lineNumber
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $expr1, AbstractExpression $expr2, AbstractExpression $expr3, int $lineNumber)
	{
		parent::__construct(['expr1' => $expr1, 'expr2' => $expr2, 'expr3' => $expr3], [], $lineNumber);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->raw('((')
			->subcompile($this->getNode('expr1'))
			->raw(') ? (')
			->subcompile($this->getNode('expr2'))
			->raw(') : (')
			->subcompile($this->getNode('expr3'))
			->raw('))');
	}

}
