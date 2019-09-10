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

namespace Cappuccino\Node\Expression\Filter;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\ConditionalExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\FilterExpression;
use Cappuccino\Node\Expression\GetAttrExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\Expression\Test\DefinedTest;
use Cappuccino\Node\Node;

/**
 * Class DefaultFilter
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression\Filter
 * @since 1.0.0
 */
class DefaultFilter extends FilterExpression
{

	/**
	 * DefaultFilter constructor.
	 *
	 * @param Node               $node
	 * @param ConstantExpression $filterName
	 * @param Node               $arguments
	 * @param int                $lineNumber
	 * @param string|null        $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(Node $node, ConstantExpression $filterName, Node $arguments, int $lineNumber, ?string $tag = null)
	{
		$default = new FilterExpression($node, new ConstantExpression('default', $node->getTemplateLine()), $arguments, $node->getTemplateLine());

		if ($filterName->getAttribute('value') === 'default' && ($node instanceof NameExpression || $node instanceof GetAttrExpression))
		{
			$test = new DefinedTest(clone $node, 'defined', new Node(), $node->getTemplateLine());
			$false = count($arguments) ? $arguments->getNode(0) : new ConstantExpression('', $node->getTemplateLine());
			$node = new ConditionalExpression($test, $default, $false, $node->getTemplateLine());
		}
		else
		{
			$node = $default;
		}

		parent::__construct($node, $filterName, $arguments, $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->subcompile($this->getNode('node'));
	}

}
