<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Filter;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\ConditionalExpression;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Expression\FilterExpression;
use Bas\Cappuccino\Node\Expression\GetAttrExpression;
use Bas\Cappuccino\Node\Expression\NameExpression;
use Bas\Cappuccino\Node\Expression\Test\DefinedTest;
use Bas\Cappuccino\Node\Node;

/**
 * Class DefaultFilter
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Node\Expression\Filter
 * @version 2.3.0
 */
class DefaultFilter extends FilterExpression
{

	/**
	 * DefaultFilter constructor.
	 *
	 * @param Node               $node
	 * @param ConstantExpression $filterName
	 * @param Node               $arguments
	 * @param int                $lineno
	 * @param mixed              $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (Node $node, ConstantExpression $filterName, Node $arguments, $lineno, $tag = null)
	{
		$default = new FilterExpression($node, new ConstantExpression('default', $node->getTemplateLine()), $arguments, $node->getTemplateLine());

		if ('default' === $filterName->getAttribute('value') && ($node instanceof NameExpression || $node instanceof GetAttrExpression))
		{
			$test = new DefinedTest(clone $node, 'defined', new Node(), $node->getTemplateLine());
			$false = count($arguments) ? $arguments->getNode(0) : new ConstantExpression('', $node->getTemplateLine());

			$node = new ConditionalExpression($test, $default, $false, $node->getTemplateLine());
		}
		else
		{
			$node = $default;
		}

		parent::__construct($node, $filterName, $arguments, $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->subcompile($this->getNode('node'));
	}

}
