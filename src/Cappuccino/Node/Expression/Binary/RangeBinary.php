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

namespace Cappuccino\Node\Expression\Binary;

use Cappuccino\Compiler;

/**
 * Class RangeBinary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Binary
 * @since 1.0.0
 */
class RangeBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.3
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->raw('range(')->subcompile($this->getNode('left'))->raw(', ')->subcompile($this->getNode('right'))->raw(')');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator(Compiler $compiler): Compiler
	{
		return $compiler->raw('..');
	}

}
