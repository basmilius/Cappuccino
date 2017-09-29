<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Binary;

use Bas\Cappuccino\Compiler;

/**
 * Class MulBinary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Binary
 * @version 1.0.0
 */
class MulBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator (Compiler $compiler): Compiler
	{
		return $compiler->raw('*');
	}

}
