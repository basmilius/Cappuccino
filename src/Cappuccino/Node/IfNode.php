<?php
/**
 * Copyright (c) 2017 - 2019 - Bas Milius <bas@mili.us>
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use function count;

/**
 * Class IfNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class IfNode extends Node
{

	/**
	 * IfNode constructor.
	 *
	 * @param Node        $tests
	 * @param Node|null   $else
	 * @param int         $lineNumber
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(Node $tests, Node $else = null, int $lineNumber = 0, ?string $tag = null)
	{
		$nodes = ['tests' => $tests];

		if ($else !== null)
			$nodes['else'] = $else;

		parent::__construct($nodes, [], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->addDebugInfo($this);

		for ($i = 0, $count = count($this->getNode('tests')); $i < $count; $i += 2)
		{
			if ($i > 0)
			{
				$compiler
					->outdent()
					->write('} elseif (');
			}
			else
			{
				$compiler
					->write('if (');
			}

			$compiler
				->subcompile($this->getNode('tests')->getNode($i))
				->raw(") {\n")
				->indent()
				->subcompile($this->getNode('tests')->getNode($i + 1));
		}

		if ($this->hasNode('else'))
		{
			$compiler
				->outdent()
				->write("} else {\n")
				->indent()
				->subcompile($this->getNode('else'));
		}

		$compiler
			->outdent()
			->write("}\n");
	}

}
