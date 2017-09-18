<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Unary;

use Bas\Cappuccino\Compiler;

/**
 * Class PosUnary
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Node\Expression\Unary
 * @version 2.3.0
 */
class PosUnary extends AbstractUnary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function operator (Compiler $compiler) : void
	{
		$compiler->raw('+');
	}

}
