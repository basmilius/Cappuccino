<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Util;

use IteratorIterator;

/**
 * Class TemplateDirIterator
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Util
 * @version 2.3.0
 */
class TemplateDirIterator extends IteratorIterator
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function current () : string
	{
		return file_get_contents(parent::current());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function key () : string
	{
		return (string)parent::key();
	}

}
