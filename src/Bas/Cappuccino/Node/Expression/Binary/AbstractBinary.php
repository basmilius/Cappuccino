<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Binary;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\AbstractExpression;
use Bas\Cappuccino\Node\Node;

/**
 * Class AbstractBinary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Binary
 * @version 1.0.0
 */
abstract class AbstractBinary extends AbstractExpression
{

	/**
	 * AbstractBinary constructor.
	 *
	 * @param Node $left
	 * @param Node $right
	 * @param int  $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Node $left, Node $right, int $lineno)
	{
		parent::__construct(['left' => $left, 'right' => $right], [], $lineno);
	}

	/**
	 * Compiles the {@see AbstractBinary}.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->raw('(')->subcompile($this->getNode('left'))->raw(' ');

		$this->operator($compiler);

		$compiler->raw(' ')->subcompile($this->getNode('right'))->raw(')');
	}

	/**
	 * Gets the operator.
	 *
	 * @param Compiler $compiler
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public abstract function operator (Compiler $compiler) : Compiler;

}
