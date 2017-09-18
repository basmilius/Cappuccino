<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

/**
 * Class Source
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino
 * @version 2.3.0
 */
final class Source
{

	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var string|null
	 */
	private $name;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * Source constructor.
	 *
	 * @param string      $code
	 * @param string|null $name
	 * @param string      $path
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (string $code, ?string $name, string $path = '')
	{
		$this->code = $code;
		$this->name = $name;
		$this->path = $path;
	}

	/**
	 * Gets the code.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getCode () : string
	{
		return $this->code;
	}

	/**
	 * Gets the name.
	 *
	 * @return string|null
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getName () : ?string
	{
		return $this->name;
	}

	/**
	 * Gets the path.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getPath () : string
	{
		return $this->path;
	}

}
