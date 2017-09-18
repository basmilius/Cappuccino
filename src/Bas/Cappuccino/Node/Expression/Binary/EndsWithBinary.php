<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Binary;

use Bas\Cappuccino\Compiler;

/**
 * Class EndsWithBinary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Binary
 * @version 1.0.0
 */
class EndsWithBinary extends AbstractBinary
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$left = $compiler->getVarName();
		$right = $compiler->getVarName();
		$compiler
			->raw(sprintf('(is_string($%s = ', $left))
			->subcompile($this->getNode('left'))
			->raw(sprintf(') && is_string($%s = ', $right))
			->subcompile($this->getNode('right'))
			->raw(sprintf(') && (\'\' === $%2$s || $%2$s === substr($%1$s, -strlen($%2$s))))', $left, $right));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function operator (Compiler $compiler) : Compiler
	{
		return $compiler->raw('');
	}

}
