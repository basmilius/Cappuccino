<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Node\Expression\Binary\AndBinary;
use Bas\Cappuccino\Node\Expression\Test\DefinedTest;
use Bas\Cappuccino\Node\Expression\Test\NullTest;
use Bas\Cappuccino\Node\Expression\Unary\NotUnary;
use Bas\Cappuccino\Node\Node;

class NullCoalesceExpression extends ConditionalExpression
{

	/**
	 * NullCoalesceExpression constructor.
	 *
	 * @param AbstractExpression $left
	 * @param AbstractExpression $right
	 * @param int                $lineno
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (AbstractExpression $left, AbstractExpression $right, int $lineno)
	{
		$test = new AndBinary(
			new DefinedTest(clone $left, 'defined', new Node(), $left->getTemplateLine()),
			new NotUnary(new NullTest($left, 'null', new Node(), $left->getTemplateLine()), $left->getTemplateLine()),
			$left->getTemplateLine()
		);

		parent::__construct($test, $left, $right, $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		if ($this->getNode('expr2') instanceof NameExpression)
		{
			$this->getNode('expr2')->setAttribute('always_defined', true);
			$compiler->raw('((')->subcompile($this->getNode('expr2'))->raw(') ?? (')->subcompile($this->getNode('expr3'))->raw('))');
		}
		else
		{
			parent::compile($compiler);
		}
	}

}
