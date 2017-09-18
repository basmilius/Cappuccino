<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Unary;

use Bas\Cappuccino\Compiler;

/**
 * Class NotUnary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Unary
 * @version 2.3.0
 */
class NotUnary extends AbstractUnary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function operator (Compiler $compiler) : void
	{
		$compiler->raw('!');
	}

}
