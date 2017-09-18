<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class AssignNameExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 1.0.0
 */
class AssignNameExpression extends NameExpression
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->raw('$context[')->string($this->getAttribute('name'))->raw(']');
	}

}
