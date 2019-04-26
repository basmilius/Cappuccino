<?php
declare(strict_types=1);

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;

/**
 * Class VariadicExpression
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node\Expression
 * @since 1.2.0
 */
class VariadicExpression extends ArrayExpression
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->raw('...');

		parent::compile($compiler);
	}

}
