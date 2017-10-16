<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\AbstractExpression;

/**
 * Class PrintNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @since 1.0.0
 */
class PrintNode extends Node implements NodeOutputInterface
{

	/**
	 * PrintNode constructor.
	 *
	 * @param AbstractExpression $expr
	 * @param int                $lineno
	 * @param string|null        $tag
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
			->write('echo ')
			->subcompile($this->getNode('expr'))
			->raw(";\n");
	}

}
