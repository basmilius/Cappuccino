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

/**
 * Class NotUnary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Unary
 * @since 1.0.0
 */
class NotUnary extends AbstractUnary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator(Compiler $compiler): Compiler
	{
		return $compiler->raw('!');
	}

}
