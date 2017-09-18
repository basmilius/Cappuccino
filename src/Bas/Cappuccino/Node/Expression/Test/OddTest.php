<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Test;

use Bas\Cappuccino\Compiler;

/**
 * Class OddTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Test
 * @version 2.3.0
 */
class OddTest extends TestExpression
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->raw('(')->subcompile($this->getNode('node'))->raw(' % 2 == 1')->raw(')');
	}

}
