<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;

/**
 * Class AutoEscapeNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @since 1.0.0
 */
class AutoEscapeNode extends Node
{

	/**
	 * AutoEscapeNode constructor.
	 *
	 * @param array       $value
	 * @param Node        $body
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct ($value, Node $body, int $lineno, ?string $tag = 'autoescape')
	{
		parent::__construct(['body' => $body], ['value' => $value], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->subcompile($this->getNode('body'));
	}

}
