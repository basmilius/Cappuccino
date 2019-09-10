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
 * Class EnterProfileNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Profiler\Node
 * @since 1.0.0
 */
class EnterProfileNode extends Node
{

	/**
	 * EnterProfileNode constructor.
	 *
	 * @param string $extensionName
	 * @param string $type
	 * @param string $name
	 * @param string $varName
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $extensionName, string $type, string $name, string $varName)
	{
		parent::__construct([], ['extension_name' => $extensionName, 'name' => $name, 'type' => $type, 'var_name' => $varName]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler
			->write(sprintf('$%s = $this->extensions[', $this->getAttribute('var_name')))
			->repr($this->getAttribute('extension_name'))
			->raw("];\n")
			->write(sprintf('$%s->enter($%s = new \Cappuccino\Profiler\Profile($this->getTemplateName(), ', $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'))
			->repr($this->getAttribute('type'))
			->raw(', ')
			->repr($this->getAttribute('name'))
			->raw("));\n\n");
	}

}
