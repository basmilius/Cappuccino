<?php
/**
 * Copyright (c) 2017 - 2019 - Bas Milius <bas@mili.us>
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\AbstractExpression;

/**
 * Class DoNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class DoNode extends Node
{

	/**
	 * DoNode constructor.
	 *
	 * @param AbstractExpression $expr
	 * @param int                $lineNumber
	 * @param string|null        $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $expr, int $lineNumber, ?string $tag = null)
	{
		parent::__construct(['expr' => $expr], [], $lineNumber, $tag);
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
			->write('')
			->subcompile($this->getNode('expr'))
			->raw(";\n");
	}

}
