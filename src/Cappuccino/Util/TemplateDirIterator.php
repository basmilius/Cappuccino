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

namespace Cappuccino\Util;

use IteratorIterator;
use function file_get_contents;

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
	public function current()
	{
		return file_get_contents(parent::current());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function key()
	{
		return (string)parent::key();
	}

}
