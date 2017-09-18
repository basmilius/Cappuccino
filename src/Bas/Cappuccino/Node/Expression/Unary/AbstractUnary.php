<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Unary;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\AbstractExpression;
use Bas\Cappuccino\Node\Node;

/**
 * Class AbstractUnary
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Unary
 * @version 1.0.0
 */
abstract class AbstractUnary extends AbstractExpression
{

	/**
	 * AbstractUnary constructor.
	 *
	 * @param Node $node
	 * @param int  $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 */
	public function __construct (Node $node, int $lineno)
	{
		parent::__construct(['node' => $node], [], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->raw(' ');
		$this->operator($compiler);
		$compiler->subcompile($this->getNode('node'));
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public abstract function operator (Compiler $compiler) : void;

}
