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
use Cappuccino\Node\Expression\ConstantExpression;

/**
 * Class DeprecatedNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class DeprecatedNode extends Node
{

	/**
	 * DeprecatedNode constructor.
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
		$compiler->addDebugInfo($this);

		$expr = $this->getNode('expr');

		if ($expr instanceof ConstantExpression)
		{
			$compiler->write('@trigger_error(')
				->subcompile($expr);
		}
		else
		{
			$varName = $compiler->getVarName();
			$compiler->write(sprintf('$%s = ', $varName))
				->subcompile($expr)
				->raw(";\n")
				->write(sprintf('@trigger_error($%s', $varName));
		}

		$compiler
			->raw('.')
			->string(sprintf(' ("%s" at line %d).', $this->getTemplateName(), $this->getTemplateLine()))
			->raw(", E_USER_DEPRECATED);\n");
	}

}
