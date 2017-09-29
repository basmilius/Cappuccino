<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Test;

use Bas\Cappuccino\Compiler;

/**
 * Class ConstantTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Test
 * @version 1.0.0
 */
class ConstantTest extends TestExpression
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->raw('(')->subcompile($this->getNode('node'))->raw(' === constant(');

		if ($this->getNode('arguments')->hasNode(1))
			$compiler->raw('get_class(')->subcompile($this->getNode('arguments')->getNode(1))->raw(')."::".');

		$compiler->subcompile($this->getNode('arguments')->getNode(0))->raw('))');
	}

}
