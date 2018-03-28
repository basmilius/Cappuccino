<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\AbstractExpression;

/**
 * Class PrintNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
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
	public function __construct(AbstractExpression $expr, int $lineno, ?string $tag = null)
	{
		parent::__construct(['expr' => $expr], [], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->write('echo ')
			->subcompile($this->getNode('expr'))
			->raw(";\n");
	}

}
