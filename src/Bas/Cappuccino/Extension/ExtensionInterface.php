<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\NodeVisitorInterface;
use Bas\Cappuccino\SimpleFilter;
use Bas\Cappuccino\SimpleFunction;
use Bas\Cappuccino\SimpleTest;
use Bas\Cappuccino\TokenParser\TokenParserInterface;

/**
 * Interface ExtensionInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @since 1.0.0
 */
interface ExtensionInterface
{

	/**
	 * Gets custom {@see TokenParserInterface}s defined by the {@see ExtensionInterface}.
	 *
	 * @return TokenParserInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers (): array;

	/**
	 * Gets custom {@see NodeVisitorInterface}s defined by the {@see ExtensionInterface}.
	 *
	 * @return NodeVisitorInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors (): array;

	/**
	 * Gets custom {@see SimpleFilter}s defined by the {@see ExtensionInterface}.
	 *
	 * @return SimpleFilter[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters (): array;

	/**
	 * Gets custom {@see SimpleTest}s defined by the {@see ExtensionInterface}.
	 *
	 * @return SimpleTest[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests (): array;

	/**
	 * Gets custom {@see SimpleFunction}s defined by the {@see ExtensionInterface}.
	 *
	 * @return SimpleFunction[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions (): array;

	/**
	 * Gets custom unary and binary operators defined by the {@see ExtensionInterface}.
	 *
	 * @return array[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getOperators (): array;

}
