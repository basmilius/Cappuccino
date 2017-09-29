<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Countable;
use JsonSerializable;

/**
 * Class Markup
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 1.0.0
 */
class Markup implements Countable, JsonSerializable
{

	/**
	 * @var string
	 */
	private $content;

	/**
	 * @var string
	 */
	private $charset;

	/**
	 * Markup constructor.
	 *
	 * @param string $content
	 * @param string $charset
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $content, string $charset)
	{
		$this->content = (string)$content;
		$this->charset = $charset;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __toString (): string
	{
		return $this->content;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function count (): int
	{
		return mb_strlen($this->content, $this->charset);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function jsonSerialize (): string
	{
		return $this->content;
	}

}
