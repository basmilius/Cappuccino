<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\AbstractExpression;

/**
 * Class DoNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @since 1.0.0
 */
class DoNode extends Node
{

	/**
	 * DoNode constructor.
	 *
	 * @param AbstractExpression $expr
	 * @param int                $lineno
	 * @param null|string        $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (AbstractExpression $expr, int $lineno, ?string $tag = null)
	{
		parent::__construct(['expr' => $expr], [], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->write('')
			->subcompile($this->getNode('expr'))
			->raw(";\n");
	}

}
