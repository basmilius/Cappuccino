<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class MethodCallExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 2.3.0
 */
class MethodCallExpression extends AbstractExpression
{

	/**
	 * MethodCallExpression constructor.
	 *
	 * @param AbstractExpression $node
	 * @param string             $method
	 * @param ArrayExpression    $arguments
	 * @param int                $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (AbstractExpression $node, string $method, ArrayExpression $arguments, int $lineno)
	{
		parent::__construct(['node' => $node, 'arguments' => $arguments], ['method' => $method, 'safe' => false], $lineno);

		if ($node instanceof NameExpression)
			$node->setAttribute('always_defined', true);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->subcompile($this->getNode('node'))->raw('->')->raw($this->getAttribute('method'))->raw('(');
		$first = true;

		/** @var ArrayExpression $arguments */
		$arguments = $this->getNode('arguments');

		foreach ($arguments->getKeyValuePairs() as $pair)
		{
			if (!$first)
			{
				$compiler->raw(', ');
			}
			$first = false;

			$compiler->subcompile($pair['value']);
		}
		$compiler->raw(')');
	}

}
