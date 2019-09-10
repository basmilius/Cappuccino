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
use LogicException;

/**
 * Class StagingExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Extension
 * @since 1.0.0
 * @internal
 */
final class StagingExtension extends AbstractExtension
{

	/**
	 * @var CappuccinoFunction[]
	 */
	private $functions = [];

	/**
	 * @var CappuccinoFilter[]
	 */
	private $filters = [];

	/**
	 * @var NodeVisitorInterface[]
	 */
	private $visitors = [];

	/**
	 * @var TokenParserInterface[]
	 */
	private $tokenParsers = [];

	/**
	 * @var CappuccinoTest[]
	 */
	private $tests = [];

	/**
	 * Adds a {@see CappuccinoFunction}.
	 *
	 * @param CappuccinoFunction $function
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function addFunction(CappuccinoFunction $function): void
	{
		if (isset($this->functions[$function->getName()]))
			throw new LogicException(sprintf('Function "%s" is already registered.', $function->getName()));

		$this->functions[$function->getName()] = $function;
	}

	/**
	 * Gets all registered {@see CappuccinoFunction}s.
	 *
	 * @return CappuccinoFunction[]
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getFunctions(): array
	{
		return $this->functions;
	}

	/**
	 * Adds a {@see CappuccinoFilter}.
	 *
	 * @param CappuccinoFilter $filter
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function addFilter(CappuccinoFilter $filter): void
	{
		if (isset($this->filters[$filter->getName()]))
			throw new LogicException(sprintf('Filter "%s" is already registered.', $filter->getName()));

		$this->filters[$filter->getName()] = $filter;
	}

	/**
	 * Gets all registered {@see CappuccinoFilter}s.
	 *
	 * @return CappuccinoFilter[]
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getFilters(): array
	{
		return $this->filters;
	}

	/**
	 * Adds a {@see NodeVisitorInterface}.
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function addNodeVisitor(NodeVisitorInterface $visitor): void
	{
		$this->visitors[] = $visitor;
	}

	/**
	 * Gets all registered {@see NodeVisitorInterface}s.
	 *
	 * @return NodeVisitorInterface[]
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array
	{
		return $this->visitors;
	}

	/**
	 * Adds a {@see TokenParserInterface}.
	 *
	 * @param TokenParserInterface $parser
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function addTokenParser(TokenParserInterface $parser): void
	{
		if (isset($this->tokenParsers[$parser->getTag()]))
			throw new LogicException(sprintf('Tag "%s" is already registered.', $parser->getTag()));

		$this->tokenParsers[$parser->getTag()] = $parser;
	}

	/**
	 * Gets all registered {@see TokenParserInterface}s.
	 *
	 * @return TokenParserInterface[]
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getTokenParsers(): array
	{
		return $this->tokenParsers;
	}

	/**
	 * Adds a {@see CappuccinoTest}.
	 *
	 * @param CappuccinoTest $test
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function addTest(CappuccinoTest $test): void
	{
		if (isset($this->tests[$test->getName()]))
			throw new LogicException(sprintf('Test "%s" is already registered.', $test->getName()));

		$this->tests[$test->getName()] = $test;
	}

	/**
	 * Gets all registered {@see CappuccinoTest}s.
	 *
	 * @return CappuccinoTest[]
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getTests(): array
	{
		return $this->tests;
	}

}
