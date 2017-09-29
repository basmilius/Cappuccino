<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Test;

use Bas\Cappuccino\Compiler;

/**
 * Class DivisiblebyTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Test
 * @version 1.0.0
 */
class DivisiblebyTest extends TestExpression
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->raw('(0 == ')->subcompile($this->getNode('node'))->raw(' % ')->subcompile($this->getNode('arguments')->getNode(0))->raw(')');
	}

}
