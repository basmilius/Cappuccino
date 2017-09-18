<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class ConditionalExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 2.3.0
 */
class ConditionalExpression extends AbstractExpression
{

	/**
	 * ConditionalExpression constructor.
	 *
	 * @param AbstractExpression $expr1
	 * @param AbstractExpression $expr2
	 * @param AbstractExpression $expr3
	 * @param int                $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (AbstractExpression $expr1, AbstractExpression $expr2, AbstractExpression $expr3, int $lineno)
	{
		parent::__construct(['expr1' => $expr1, 'expr2' => $expr2, 'expr3' => $expr3], [], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->raw('((')->subcompile($this->getNode('expr1'))->raw(') ? (')->subcompile($this->getNode('expr2'))->raw(') : (')->subcompile($this->getNode('expr3'))->raw('))');
	}

}
