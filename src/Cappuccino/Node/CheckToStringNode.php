<?php
declare(strict_types=1);

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\AbstractExpression;

/**
 * Class CheckToStringNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
 * @since 1.2.0
 */
final class CheckToStringNode extends AbstractExpression
{

	/**
	 * CheckToStringNode constructor.
	 *
	 * @param AbstractExpression $expr
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public function __construct(AbstractExpression $expr)
	{
		parent::__construct(['expr' => $expr], [], $expr->getTemplateLine(), $expr->getNodeTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
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
