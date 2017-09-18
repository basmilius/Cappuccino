<?php
declare(strict_types=1);

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\Binary\AbstractBinary;

/**
 * Class NotEqualBinary
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @version 2.3.0
 */
class NotEqualBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function operator (Compiler $compiler) : Compiler
	{
		return $compiler->raw('!=');
	}

}
