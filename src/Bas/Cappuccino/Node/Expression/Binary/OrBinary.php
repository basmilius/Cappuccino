<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Binary;

use Bas\Cappuccino\Compiler;

/**
 * Class OrBinary
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Node\Expression\Binary
 * @version 2.3.0
 */
class OrBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function operator (Compiler $compiler) : Compiler
	{
		return $compiler->raw('||');
	}

}
