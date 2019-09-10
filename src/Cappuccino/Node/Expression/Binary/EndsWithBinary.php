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

namespace Cappuccino\Node\Expression\Binary;

use Cappuccino\Compiler;

/**
 * Class EndsWithBinary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Binary
 * @since 1.0.0
 */
class EndsWithBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$left = $compiler->getVarName();
		$right = $compiler->getVarName();
		$compiler
			->raw(sprintf('(is_string($%s = ', $left))
			->subcompile($this->getNode('left'))
			->raw(sprintf(') && is_string($%s = ', $right))
			->subcompile($this->getNode('right'))
			->raw(sprintf(') && (\'\' === $%2$s || $%2$s === substr($%1$s, -strlen($%2$s))))', $left, $right));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator(Compiler $compiler): Compiler
	{
		return $compiler->raw('');
	}

}
