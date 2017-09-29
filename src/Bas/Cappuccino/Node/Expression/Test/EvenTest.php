<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Test;

use Bas\Cappuccino\Compiler;

/**
 * Class EvenTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Test
 * @version 1.0.0
 */
class EvenTest extends TestExpression
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->raw('(')->subcompile($this->getNode('node'))->raw(' % 2 == 0')->raw(')');
	}

}
