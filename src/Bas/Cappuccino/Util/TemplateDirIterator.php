<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Util;

use IteratorIterator;

/**
 * Class TemplateDirIterator
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Util
 * @since 1.0.0
 */
class TemplateDirIterator extends IteratorIterator
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function current (): string
	{
		return file_get_contents(parent::current());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function key (): string
	{
		return (string)parent::key();
	}

}
