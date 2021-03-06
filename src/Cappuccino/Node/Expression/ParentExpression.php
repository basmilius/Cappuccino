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

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;

/**
 * Class ParentExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
class ParentExpression extends AbstractExpression
{

	/**
	 * ParentExpression constructor.
	 *
	 * @param string      $name
	 * @param int         $lineNumber
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $name, int $lineNumber, ?string $tag = null)
	{
		parent::__construct([], ['output' => false, 'name' => $name], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		if ($this->getAttribute('output'))
		{
			$compiler
				->addDebugInfo($this)
				->write('$this->displayParentBlock(')
				->string($this->getAttribute('name'))
				->raw(", \$context, \$blocks);\n");
		}
		else
		{
			$compiler
				->raw('$this->renderParentBlock(')
				->string($this->getAttribute('name'))
				->raw(', $context, $blocks)');
		}
	}

}
