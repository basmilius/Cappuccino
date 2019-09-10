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
 * Class CheckToStringNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class CheckToStringNode extends AbstractExpression
{

	/**
	 * CheckToStringNode constructor.
	 *
	 * @param AbstractExpression $expr
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $expr)
	{
		parent::__construct(['expr' => $expr], [], $expr->getTemplateLine(), $expr->getNodeTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$expr = $this->getNode('expr');
		$compiler
			->raw('$this->sandbox->ensureToStringAllowed(')
			->subcompile($expr)
			->raw(', ')
			->repr($expr->getTemplateLine())
			->raw(', $this->source)');
	}

}
