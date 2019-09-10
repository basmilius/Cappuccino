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

namespace Cappuccino\Extension;

use Cappuccino\CappuccinoFilter;
use Cappuccino\CappuccinoFunction;
use Cappuccino\CappuccinoTest;
use Cappuccino\NodeVisitor\NodeVisitorInterface;
use Cappuccino\TokenParser\TokenParserInterface;

/**
 * Interface ExtensionInterface
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Extension
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
	public function getTokenParsers(): array;

	/**
	 * Gets custom {@see NodeVisitorInterface}s defined by the {@see ExtensionInterface}.
	 *
	 * @return NodeVisitorInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array;

	/**
	 * Gets custom {@see CappuccinoFilter}s defined by the {@see ExtensionInterface}.
	 *
	 * @return CappuccinoFilter[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters(): array;

	/**
	 * Gets custom {@see CappuccinoTest}s defined by the {@see ExtensionInterface}.
	 *
	 * @return CappuccinoTest[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests(): array;

	/**
	 * Gets custom {@see CappuccinoFunction}s defined by the {@see ExtensionInterface}.
	 *
	 * @return CappuccinoFunction[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions(): array;

	/**
	 * Gets custom unary and binary operators defined by the {@see ExtensionInterface}.
	 *
	 * @return array[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getOperators(): array;

}
