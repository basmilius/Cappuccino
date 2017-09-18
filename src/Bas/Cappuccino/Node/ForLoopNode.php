<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;

/**
 * Class ForLoopNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @version 1.0.0
 */
class ForLoopNode extends Node
{

	/**
	 * ForLoopNode constructor.
	 *
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (int $lineno, ?string $tag = null)
	{
		parent::__construct([], ['with_loop' => false, 'ifexpr' => false, 'else' => false], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler) : void
	{
		if ($this->getAttribute('else'))
			$compiler->write("\$context['_iterated'] = true;\n");

		if ($this->getAttribute('with_loop'))
		{
			$compiler
				->write("++\$context['loop']['index0'];\n")
				->write("++\$context['loop']['index'];\n")
				->write("\$context['loop']['first'] = false;\n");

			if (!$this->getAttribute('ifexpr'))
				$compiler
					->write("if (isset(\$context['loop']['length'])) {\n")
					->indent()
					->write("--\$context['loop']['revindex0'];\n")
					->write("--\$context['loop']['revindex'];\n")
					->write("\$context['loop']['last'] = 0 === \$context['loop']['revindex0'];\n")
					->outdent()
					->write("}\n");
		}
	}

}
