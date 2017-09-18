<?php
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
 * @version 1.0.0
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

	public function addFunction (SimpleFunction $function)
	{
		if (isset($this->functions[$function->getName()]))
		{
			throw new LogicException(sprintf('Function "%s" is already registered.', $function->getName()));
		}

		$this->functions[$function->getName()] = $function;
	}

	public function getFunctions () : array
	{
		return $this->functions;
	}

	public function addFilter (SimpleFilter $filter)
	{
		if (isset($this->filters[$filter->getName()]))
		{
			throw new LogicException(sprintf('Filter "%s" is already registered.', $filter->getName()));
		}

		$this->filters[$filter->getName()] = $filter;
	}

	public function getFilters () : array
	{
		return $this->filters;
	}

	public function addNodeVisitor (NodeVisitorInterface $visitor)
	{
		$this->visitors[] = $visitor;
	}

	public function getNodeVisitors () : array
	{
		return $this->visitors;
	}

	public function addTokenParser (TokenParserInterface $parser)
	{
		if (isset($this->tokenParsers[$parser->getTag()]))
		{
			throw new LogicException(sprintf('Tag "%s" is already registered.', $parser->getTag()));
		}

		$this->tokenParsers[$parser->getTag()] = $parser;
	}

	public function getTokenParsers () : array
	{
		return $this->tokenParsers;
	}

	public function addTest (SimpleTest $test)
	{
		if (isset($this->tests[$test->getName()]))
			throw new LogicException(sprintf('Test "%s" is already registered.', $test->getName()));

		$this->tests[$test->getName()] = $test;
	}

	public function getTests () : array
	{
		return $this->tests;
	}
}
