<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class TempNameExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 1.0.0
 */
class TempNameExpression extends AbstractExpression
{

	/**
	 * TempNameExpression constructor.
	 *
	 * @param string $name
	 * @param int    $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $name, int $lineno)
	{
		parent::__construct([], ['name' => $name], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->raw('$_')->raw($this->getAttribute('name'))->raw('_');
	}

}
