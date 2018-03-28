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

namespace Cappuccino\Util;

use IteratorIterator;

/**
 * Class TemplateDirIterator
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Util
 * @since 1.0.0
 */
class TemplateDirIterator extends IteratorIterator
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function current(): string
	{
		return file_get_contents(parent::current());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function key(): string
	{
		return (string)parent::key();
	}

}
