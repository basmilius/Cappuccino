<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Sandbox;

use Exception;

/**
 * Class SecurityNotAllowedFilterError
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Sandbox
 * @version 2.3.0
 */
class SecurityNotAllowedFilterError extends SecurityError
{

	/**
	 * @var string
	 */
	private $filterName;

	/**
	 * SecurityNotAllowedFilterError constructor.
	 *
	 * @param string         $message
	 * @param string         $functionName
	 * @param int            $lineno
	 * @param string|null    $filename
	 * @param Exception|null $previous
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (string $message, string $functionName, int $lineno = -1, ?string $filename = null, Exception $previous = null)
	{
		parent::__construct($message, $lineno, $filename, $previous);

		$this->filterName = $functionName;
	}

	/**
	 * Gets the filter name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getFilterName () : string
	{
		return $this->filterName;
	}

}
