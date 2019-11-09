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

namespace Cappuccino\Node\Expression\Test;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\TestExpression;

/**
 * Class NullTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Test
 * @since 1.0.0
 */
class NullTest extends TestExpression
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->raw('(')
			->subcompile($this->getNode('node'))
			->raw(' === null)');
	}

}
