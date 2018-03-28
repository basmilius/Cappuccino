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
 * Class EqualBinary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Binary
 * @since 1.0.0
 */
class EqualBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator(Compiler $compiler): Compiler
	{
		return $compiler->raw('==');
	}

}
