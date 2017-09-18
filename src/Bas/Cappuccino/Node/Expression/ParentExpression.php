<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class ParentExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 2.3.0
 */
class ParentExpression extends AbstractExpression
{

	/**
	 * ParentExpression constructor.
	 *
	 * @param string $name
	 * @param int    $lineno
	 * @param mixed  $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (string $name, int $lineno, $tag = null)
	{
		parent::__construct([], ['output' => false, 'name' => $name], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		if ($this->getAttribute('output'))
			$compiler->addDebugInfo($this)->write('$this->displayParentBlock(')->string($this->getAttribute('name'))->raw(", \$context, \$blocks);\n");
		else
			$compiler->raw('$this->renderParentBlock(')->string($this->getAttribute('name'))->raw(', $context, $blocks)');
	}

}
