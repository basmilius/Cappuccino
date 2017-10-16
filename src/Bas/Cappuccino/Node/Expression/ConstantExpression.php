<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class ConstantExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @since 1.0.0
 */
class ConstantExpression extends AbstractExpression
{

	/**
	 * ConstantExpression constructor.
	 *
	 * @param mixed $value
	 * @param int   $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct ($value, int $lineno)
	{
		parent::__construct([], ['value' => $value], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->repr($this->getAttribute('value'));
	}

}
