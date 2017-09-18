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
 * @version 2.3.0
 */
interface ExtensionInterface
{

	/**
	 * Returns the token parser instances to add to the existing list.
	 *
	 * @return TokenParserInterface[]
	 */
	public function getTokenParsers () : array;

	/**
	 * Returns the node visitor instances to add to the existing list.
	 *
	 * @return NodeVisitorInterface[]
	 */
	public function getNodeVisitors () : array;

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return SimpleFilter[]
	 */
	public function getFilters () : array;

	/**
	 * Returns a list of tests to add to the existing list.
	 *
	 * @return SimpleTest[]
	 */
	public function getTests () : array;

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return SimpleFunction[]
	 */
	public function getFunctions () : array;

	/**
	 * Returns a list of operators to add to the existing list.
	 *
	 * @return array<array> First array of unary operators, second array of binary operators
	 */
	public function getOperators () : array;

}
