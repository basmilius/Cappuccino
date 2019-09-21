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
use Cappuccino\Node\Expression\Binary\AndBinary;
use Cappuccino\Node\Expression\Test\DefinedTest;
use Cappuccino\Node\Expression\Test\NullTest;
use Cappuccino\Node\Expression\Unary\NotUnary;
use Cappuccino\Node\Node;

/**
 * Class NullCoalesceExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
class NullCoalesceExpression extends ConditionalExpression
{

	/**
	 * NullCoalesceExpression constructor.
	 *
	 * @param AbstractExpression $left
	 * @param AbstractExpression $right
	 * @param int                $lineNumber
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $left, AbstractExpression $right, int $lineNumber)
	{
		$test = new DefinedTest(clone $left, 'defined', new Node(), $left->getTemplateLine());

		if (!$left instanceof BlockReferenceExpression)
			$test = new AndBinary($test, new NotUnary(new NullTest($left, 'null', new Node(), $left->getTemplateLine()), $left->getTemplateLine()), $left->getTemplateLine());

		parent::__construct($test, $left, $right, $lineNumber);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		if ($this->getNode('expr2') instanceof NameExpression)
		{
			$this->getNode('expr2')->setAttribute('always_defined', true);
			$compiler
				->raw('((')
				->subcompile($this->getNode('expr2'))
				->raw(') ?? (')
				->subcompile($this->getNode('expr3'))
				->raw('))');
		}
		else
		{
			parent::compile($compiler);
		}
	}

}
