<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Unary;

use Bas\Cappuccino\Compiler;

/**
 * Class PosUnary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Unary
 * @version 1.0.0
 */
class PosUnary extends AbstractUnary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator (Compiler $compiler) : void
	{
		$compiler->raw('+');
	}

}
