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

namespace Cappuccino\Profiler\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Node;

/**
 * Class LeaveProfileNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler\Node
 * @since 1.0.0
 */
class LeaveProfileNode extends Node
{

	/**
	 * LeaveProfileNode constructor.
	 *
	 * @param string $varName
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $varName)
	{
		parent::__construct([], ['var_name' => $varName]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->write("\n")
			->write(sprintf("\$%s->leave(\$%s);\n\n", $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'));
	}

}
