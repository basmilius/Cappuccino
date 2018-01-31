<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\Profiler\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Node;
use Cappuccino\Profiler\Profile;

/**
 * Class EnterProfileNode
 *
 * @author Bas Milius <bas@mili.us>
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
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $extensionName, string $type, string $name, string $varName)
	{
		parent::__construct([], ['extension_name' => $extensionName, 'name' => $name, 'type' => $type, 'var_name' => $varName]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$classProfile = Profile::class;

		$compiler
			->write(sprintf('$%s = $this->cappuccino->getExtension(', $this->getAttribute('var_name')))
			->repr($this->getAttribute('extension_name'))
			->raw(");\n")
			->write(sprintf('$%s->enter($%s = new ' . $classProfile . '($this->getTemplateName(), ', $this->getAttribute('var_name'), $this->getAttribute('var_name') . '_prof'))
			->repr($this->getAttribute('type'))
			->raw(', ')
			->repr($this->getAttribute('name'))
			->raw("));\n\n");
	}

}
