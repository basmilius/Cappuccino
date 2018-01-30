<?php
/**
 * This file is part of the Bas\Cappuccino package.
 *
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\NodeVisitorInterface;
use Bas\Cappuccino\SimpleFilter;
use Bas\Cappuccino\SimpleFunction;
use Bas\Cappuccino\SimpleTest;
use Bas\Cappuccino\TokenParser\TokenParserInterface;
use LogicException;

/**
 * Class StagingExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @since 1.0.0
 */
final class StagingExtension extends AbstractExtension
{

	/**
	 * @var SimpleFunction[]
	 */
	private $functions = [];

	/**
	 * @var SimpleFilter[]
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
	 * @var SimpleTest[]
	 */
	private $tests = [];

	/**
	 * Adds a {@see SimpleFunction.
	 *
	 * @param SimpleFunction $function
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function addFunction (SimpleFunction $function)
	{
		if (isset($this->functions[$function->getName()]))
		{
			throw new LogicException(sprintf('Function "%s" is already registered.', $function->getName()));
		}

		$this->functions[$function->getName()] = $function;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getFunctions (): array
	{
		return $this->functions;
	}

	/**
	 * Adds a {@see SimpleFilter}.
	 *
	 * @param SimpleFilter $filter
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function addFilter (SimpleFilter $filter)
	{
		if (isset($this->filters[$filter->getName()]))
		{
			throw new LogicException(sprintf('Filter "%s" is already registered.', $filter->getName()));
		}

		$this->filters[$filter->getName()] = $filter;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getFilters (): array
	{
		return $this->filters;
	}

	/**
	 * Adds a {@see NodeVisitorInterface}.
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function addNodeVisitor (NodeVisitorInterface $visitor)
	{
		$this->visitors[] = $visitor;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getNodeVisitors (): array
	{
		return $this->visitors;
	}

	/**
	 * Adds a {@see TokenParserInterface}.
	 *
	 * @param TokenParserInterface $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function addTokenParser (TokenParserInterface $parser)
	{
		if (isset($this->tokenParsers[$parser->getTag()]))
		{
			throw new LogicException(sprintf('Tag "%s" is already registered.', $parser->getTag()));
		}

		$this->tokenParsers[$parser->getTag()] = $parser;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getTokenParsers (): array
	{
		return $this->tokenParsers;
	}

	/**
	 * Adds a {@see SimpleTest}.
	 *
	 * @param SimpleTest $test
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function addTest (SimpleTest $test)
	{
		if (isset($this->tests[$test->getName()]))
			throw new LogicException(sprintf('Test "%s" is already registered.', $test->getName()));

		$this->tests[$test->getName()] = $test;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getTests (): array
	{
		return $this->tests;
	}
}
