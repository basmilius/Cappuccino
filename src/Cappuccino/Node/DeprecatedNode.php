<?php
declare(strict_types=1);

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Node\Expression\ConstantExpression;

/**
 * Class DeprecatedNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
 * @since 1.2.0
 */
final class DeprecatedNode extends Node
{

	/**
	 * DeprecatedNode constructor.
	 *
	 * @param AbstractExpression $expression
	 * @param int                $lineNumber
	 * @param string|null        $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public function __construct(AbstractExpression $expression, int $lineNumber, ?string $tag = null)
	{
		parent::__construct(['expr' => $expression], [], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function compile(Compiler $compiler): void
	{
		$compiler->addDebugInfo($this);

		$expression = $this->getNode('expr');

		if ($expression instanceof ConstantExpression)
		{
			$compiler
				->write('@trigger_error(')
				->subcompile($expression);
		}
		else
		{
			$variableName = $compiler->getVarName();

			$compiler
				->write(sprintf('$%s = ', $variableName))
				->subcompile($expression)
				->raw(";\n")
				->write(sprintf('@trigger_error($%s', $variableName));
		}

		$compiler
			->raw('.')
			->string(sprintf(' ("%s" at line %d).', $this->getTemplateName(), $this->getTemplateLine()))
			->raw(", E_USER_DEPRECATED);\n");
	}

}
