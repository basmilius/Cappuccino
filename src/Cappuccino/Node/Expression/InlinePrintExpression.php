<?php
declare(strict_types=1);

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;
use Cappuccino\Node\Node;

/**
 * Class InlinePrintExpression
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node\Expression
 * @since 1.2.0
 */
final class InlinePrintExpression extends AbstractExpression
{

	/**
	 * InlinePrintExpression constructor.
	 *
	 * @param Node $node
	 * @param      $lineno
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.2.0
	 */
	public function __construct(Node $node, $lineno)
	{
		parent::__construct(['node' => $node], [], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->raw('print (')
			->subcompile($this->getNode('node'))
			->raw(')');
	}

}
