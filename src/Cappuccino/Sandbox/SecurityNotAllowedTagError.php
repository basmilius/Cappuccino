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

namespace Cappuccino\Sandbox;

/**
 * Class SecurityNotAllowedTagError
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Sandbox
 * @since 1.0.0
 */
final class SecurityNotAllowedTagError extends SecurityError
{

	/**
	 * @var string
	 */
	private $tagName;

	/**
	 * SecurityNotAllowedTagError constructor.
	 *
	 * @param string $message
	 * @param string $tagName
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $message, string $tagName)
	{
		parent::__construct($message);

		$this->tagName = $tagName;
	}

	/**
	 * Gets the tag name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getTagName(): string
	{
		return $this->tagName;
	}

}
