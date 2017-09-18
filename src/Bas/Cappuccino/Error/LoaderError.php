<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Error;

use Exception;
use Bas\Cappuccino\Source;

/**
 * Class LoaderError
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Error
 * @version 2.3.0
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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (string $message, int $lineno = -1, $source = null, Exception $previous = null)
	{
		parent::__construct('', 0, null, $previous);

		$this->appendMessage($message);
		$this->setTemplateLine(false);
	}

}
