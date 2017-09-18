<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression\Test;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\Expression\ArrayExpression;
use Bas\Cappuccino\Node\Expression\BlockReferenceExpression;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Expression\FunctionExpression;
use Bas\Cappuccino\Node\Expression\GetAttrExpression;
use Bas\Cappuccino\Node\Expression\NameExpression;
use Bas\Cappuccino\Node\Node;

/**
 * Class DefinedTest
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression\Test
 * @version 1.0.0
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
	public function compile (Compiler $compiler) : void
	{
		$compiler->subcompile($this->getNode('node'));
	}

}
