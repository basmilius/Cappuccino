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

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;
use Cappuccino\Node\Node;

/**
 * Class ArrowFunctionExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 2.0.0
 */
class ArrowFunctionExpression extends AbstractExpression
{

	/**
	 * ArrowFunctionExpression constructor.
	 *
	 * @param AbstractExpression $expr
	 * @param Node               $names
	 * @param int                $lineNumber
	 * @param string|null        $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	public function __construct(AbstractExpression $expr, Node $names, int $lineNumber, ?string $tag = null)
	{
		parent::__construct(['expr' => $expr, 'names' => $names], [], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->addDebugInfo($this)
			->raw('function (');

		/** @var Node[] $namesNodes */
		$namesNodes = $this->getNode('names');

		foreach ($namesNodes as $i => $name)
		{
			if ($i)
				$compiler->raw(', ');

			$compiler
				->raw('$__')
				->raw($name->getAttribute('name'))
				->raw('__');
		}

		$compiler
			->raw(') use ($context, $macros) { ');

		foreach ($this->getNode('names') as $name)
		{
			$compiler
				->raw('$context["')
				->raw($name->getAttribute('name'))
				->raw('"] = $__')
				->raw($name->getAttribute('name'))
				->raw('__; ');
		}

		$compiler
			->raw('return ')
			->subcompile($this->getNode('expr'))
			->raw('; }');
	}

}
