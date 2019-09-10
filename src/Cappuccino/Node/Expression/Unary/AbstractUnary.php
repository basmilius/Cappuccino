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

namespace Cappuccino\Node\Expression\Unary;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Node\Node;

/**
 * Class AbstractUnary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Unary
 * @since 1.0.0
 */
abstract class AbstractUnary extends AbstractExpression
{

	/**
	 * AbstractUnary constructor.
	 *
	 * @param Node $node
	 * @param int  $lineNumber
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(Node $node, int $lineNumber)
	{
		parent::__construct(['node' => $node], [], $lineNumber);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->raw(' ');
		$this->operator($compiler);
		$compiler->subcompile($this->getNode('node'));
	}

	/**
	 * Compiles the operator part.
	 *
	 * @param Compiler $compiler
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public abstract function operator(Compiler $compiler): Compiler;

}
