<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Error;

use Bas\Cappuccino\Source;
use Exception;

/**
 * Class LoaderError
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Error
 * @version 1.0.0
 */
final class LoaderError extends Error
{

	/**
	 * LoaderError constructor.
	 *
	 * @param string             $message
	 * @param int                $lineno
	 * @param Source|string|null $source
	 * @param Exception|null     $previous
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $message, int $lineno = -1, $source = null, Exception $previous = null)
	{
		parent::__construct('', 0, null, $previous);

		$this->appendMessage($message);
		$this->setTemplateLine(false);
	}

}
