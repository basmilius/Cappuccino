<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Binary;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Util\StaticMethods;

/**
 * Class NotInBinary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Binary
 * @version 1.0.0
 */
class NotInBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->raw('!' . StaticMethods::class . '::inFilter(')->subcompile($this->getNode('left'))->raw(', ')->subcompile($this->getNode('right'))->raw(')');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator (Compiler $compiler): Compiler
	{
		return $compiler->raw('not in');
	}

}
