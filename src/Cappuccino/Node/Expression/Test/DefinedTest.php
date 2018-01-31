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

namespace Cappuccino\Node\Expression\Test;

use Cappuccino\Compiler;
use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\Expression\ArrayExpression;
use Cappuccino\Node\Expression\BlockReferenceExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\FunctionExpression;
use Cappuccino\Node\Expression\GetAttrExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\Node;

/**
 * Class DefinedTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Test
 * @since 1.0.0
 */
class DefinedTest extends TestExpression
{

	/**
	 * DefinedTest constructor.
	 *
	 * @param Node      $node
	 * @param string    $name
	 * @param Node|null $arguments
	 * @param int       $lineno
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Node $node, string $name, Node $arguments = null, int $lineno)
	{
		if ($node instanceof NameExpression)
		{
			$node->setAttribute('is_defined_test', true);
		}
		else if ($node instanceof GetAttrExpression)
		{
			$node->setAttribute('is_defined_test', true);
			$this->changeIgnoreStrictCheck($node);
		}
		else if ($node instanceof BlockReferenceExpression)
		{
			$node->setAttribute('is_defined_test', true);
		}
		else if ($node instanceof FunctionExpression && 'constant' === $node->getAttribute('name'))
		{
			$node->setAttribute('is_defined_test', true);
		}
		else if ($node instanceof ConstantExpression || $node instanceof ArrayExpression)
		{
			$node = new ConstantExpression(true, $node->getTemplateLine());
		}
		else
		{
			throw new SyntaxError('The "defined" test only works with simple variables.', $this->getTemplateLine());
		}

		parent::__construct($node, $name, $arguments, $lineno);
	}

	private function changeIgnoreStrictCheck (GetAttrExpression $node)
	{
		$node->setAttribute('ignore_strict_check', true);

		$expression = $node->getNode('node');

		if ($expression instanceof GetAttrExpression)
		{
			$this->changeIgnoreStrictCheck($expression);
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->subcompile($this->getNode('node'));
	}

}
