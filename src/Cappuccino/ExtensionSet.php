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

namespace Cappuccino;

use Cappuccino\Error\RuntimeError;
use Cappuccino\Extension\ExtensionInterface;
use Cappuccino\Extension\GlobalsInterface;
use Cappuccino\Extension\StagingExtension;
use Cappuccino\Node\Expression\Binary\AbstractBinary;
use Cappuccino\NodeVisitor\NodeVisitorInterface;
use Cappuccino\TokenParser\TokenParserInterface;
use InvalidArgumentException;
use LogicException;
use ReflectionObject;
use UnexpectedValueException;
use function array_keys;
use function array_merge;
use function array_shift;
use function count;
use function file_exists;
use function filemtime;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function is_resource;
use function json_encode;
use function ltrim;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_replace;

/**
 * Class ExtensionSet
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
final class ExtensionSet
{

	/**
	 * @var ExtensionInterface[]
	 */
	private $extensions;

	/**
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * @var bool
	 */
	private $runtimeInitialized = false;

	/**
	 * @var StagingExtension
	 */
	private $staging;

	/**
	 * @var TokenParserInterface[]
	 */
	private $parsers;

	/**
	 * @var NodeVisitorInterface[]
	 */
	private $visitors;

	/**
	 * @var CappuccinoFilter[]
	 */
	private $filters;

	/**
	 * @var CappuccinoTest[]
	 */
	private $tests;

	/**
	 * @var CappuccinoFunction[]
	 */
	private $functions;

	/**
	 * @var AbstractBinary[]
	 */
	private $unaryOperators;

	/**
	 * @var AbstractBinary[]
	 */
	private $binaryOperators;

	/**
	 * @var array
	 */
	private $globals;

	/**
	 * @var callable[]
	 */
	private $functionCallbacks = [];

	/**
	 * @var callable[]
	 */
	private $filterCallbacks = [];

	/**
	 * @var int
	 */
	private $lastModified = 0;

	/**
	 * ExtensionSet constructor.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct()
	{
		$this->staging = new StagingExtension();
	}

	/**
	 * Call when runtime is initialized.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function initRuntime(): void
	{
		$this->runtimeInitialized = true;
	}

	/**
	 * Returns TRUE if an extension is present.
	 *
	 * @param string $class
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasExtension(string $class): bool
	{
		return isset($this->extensions[ltrim($class, '\\')]);
	}

	/**
	 * Gets an extension.
	 *
	 * @param string $class
	 *
	 * @return ExtensionInterface
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExtension(string $class): ExtensionInterface
	{
		$class = ltrim($class, '\\');

		if (!isset($this->extensions[$class]))
			throw new RuntimeError(sprintf('The "%s" extension is not enabled.', $class));

		return $this->extensions[$class];
	}

	/**
	 * Sets extensions.
	 *
	 * @param ExtensionInterface[] $extensions
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setExtensions(array $extensions): void
	{
		foreach ($extensions as $extension)
			$this->addExtension($extension);
	}

	/**
	 * Gets all extensions.
	 *
	 * @return ExtensionInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

	/**
	 * Gets the signature.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSignature(): string
	{
		return json_encode(array_keys($this->extensions));
	}

	/**
	 * Returns TRUE if initialized.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isInitialized(): bool
	{
		return $this->initialized || $this->runtimeInitialized;
	}

	/**
	 * Gets last modified by all extensions.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getLastModified(): int
	{
		if ($this->lastModified !== 0)
			return $this->lastModified;

		foreach ($this->extensions as $extension)
		{
			$r = new ReflectionObject($extension);

			if (file_exists($r->getFileName()) && ($extensionTime = filemtime($r->getFileName())) > $this->lastModified)
				$this->lastModified = $extensionTime;
		}

		return $this->lastModified;
	}

	/**
	 * Adds an extension.
	 *
	 * @param ExtensionInterface $extension
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addExtension(ExtensionInterface $extension): void
	{
		$class = get_class($extension);

		if ($this->initialized)
			throw new LogicException(sprintf('Unable to register extension "%s" as extensions have already been initialized.', $class));

		if (isset($this->extensions[$class]))
			throw new LogicException(sprintf('Unable to register extension "%s" as it is already registered.', $class));

		$this->extensions[$class] = $extension;
	}

	/**
	 * Adds a function.
	 *
	 * @param CappuccinoFunction $function
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addFunction(CappuccinoFunction $function): void
	{
		if ($this->initialized)
			throw new LogicException(sprintf('Unable to add function "%s" as extensions have already been initialized.', $function->getName()));

		$this->staging->addFunction($function);
	}

	/**
	 * Gets all defined functions.
	 *
	 * @return CappuccinoFunction[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions(): array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->functions;
	}

	/**
	 * Gets a function.
	 *
	 * @param string $name
	 *
	 * @return CappuccinoFunction|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunction(string $name): ?CappuccinoFunction
	{
		if (!$this->initialized)
			$this->initExtensions();

		if (isset($this->functions[$name]))
			return $this->functions[$name];

		foreach ($this->functions as $pattern => $function)
		{
			$pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);

			if ($count && preg_match('#^' . $pattern . '$#', $name, $matches))
			{
				array_shift($matches);
				$function->setArguments($matches);

				return $function;
			}
		}

		foreach ($this->functionCallbacks as $callback)
			if (($function = $callback($name)) !== false)
				return $function;

		return null;
	}

	/**
	 * Registers an undefined function callback.
	 *
	 * @param callable $callable
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function registerUndefinedFunctionCallback(callable $callable): void
	{
		$this->functionCallbacks[] = $callable;
	}

	/**
	 * Adds a filter.
	 *
	 * @param CappuccinoFilter $filter
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addFilter(CappuccinoFilter $filter): void
	{
		if ($this->initialized)
			throw new LogicException(sprintf('Unable to add filter "%s" as extensions have already been initialized.', $filter->getName()));

		$this->staging->addFilter($filter);
	}

	/**
	 * Gets all defined filters.
	 *
	 * @return CappuccinoFilter[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters(): array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->filters;
	}

	/**
	 * Gets a filter.
	 *
	 * @param string $name
	 *
	 * @return CappuccinoFilter|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilter(string $name): ?CappuccinoFilter
	{
		if (!$this->initialized)
			$this->initExtensions();

		if (isset($this->filters[$name]))
			return $this->filters[$name];

		foreach ($this->filters as $pattern => $filter)
		{
			$pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);

			if ($count && preg_match('#^' . $pattern . '$#', $name, $matches))
			{
				array_shift($matches);
				$filter->setArguments($matches);

				return $filter;
			}
		}

		foreach ($this->filterCallbacks as $callback)
			if (($filter = $callback($name)) !== false)
				return $filter;

		return null;
	}

	/**
	 * Registers an undefined filter callback.
	 *
	 * @param callable $callable
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function registerUndefinedFilterCallback(callable $callable): void
	{
		$this->filterCallbacks[] = $callable;
	}

	/**
	 * Adds a node visitor.
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addNodeVisitor(NodeVisitorInterface $visitor): void
	{
		if ($this->initialized)
			throw new LogicException('Unable to add a node visitor as extensions have already been initialized.');

		$this->staging->addNodeVisitor($visitor);
	}

	/**
	 * Gets all defined node visitors.
	 *
	 * @return NodeVisitorInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->visitors;
	}

	/**
	 * Adds a token parser.
	 *
	 * @param TokenParserInterface $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTokenParser(TokenParserInterface $parser): void
	{
		if ($this->initialized)
			throw new LogicException('Unable to add a token parser as extensions have already been initialized.');

		$this->staging->addTokenParser($parser);
	}

	/**
	 * Gets all defined token parsers.
	 *
	 * @return TokenParserInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers(): array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->parsers;
	}

	/**
	 * Gets the globals.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getGlobals(): array
	{
		if ($this->globals !== null)
			return $this->globals;

		$globals = [];

		foreach ($this->extensions as $extension)
		{
			if (!$extension instanceof GlobalsInterface)
				continue;

			$extGlobals = $extension->getGlobals();

			if (!is_array($extGlobals))
				throw new UnexpectedValueException(sprintf('"%s::getGlobals()" must return an array of globals.', get_class($extension)));

			$globals = array_merge($globals, $extGlobals);
		}

		if ($this->initialized)
			$this->globals = $globals;

		return $globals;
	}

	/**
	 * Adds a Test.
	 *
	 * @param CappuccinoTest $test
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTest(CappuccinoTest $test): void
	{
		if ($this->initialized)
			throw new LogicException(sprintf('Unable to add test "%s" as extensions have already been initialized.', $test->getName()));

		$this->staging->addTest($test);
	}

	/**
	 * Gets all defined tests.
	 *
	 * @return CappuccinoTest[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests(): array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->tests;
	}

	/**
	 * Gets a test.
	 *
	 * @param string $name
	 *
	 * @return CappuccinoTest|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTest(string $name): ?CappuccinoTest
	{
		if (!$this->initialized)
			$this->initExtensions();

		if (isset($this->tests[$name]))
			return $this->tests[$name];

		foreach ($this->tests as $pattern => $test)
		{
			$pattern = str_replace('\\*', '(.*?)', preg_quote($pattern, '#'), $count);

			if ($count && preg_match('#^' . $pattern . '$#', $name, $matches))
			{
				array_shift($matches);
				$test->setArguments($matches);

				return $test;
			}
		}

		return null;
	}

	/**
	 * Gets the registered unary operators.
	 *
	 * @return AbstractBinary[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getUnaryOperators(): array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->unaryOperators;
	}

	/**
	 * Gets the registered binary operators.
	 *
	 * @return AbstractBinary[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBinaryOperators(): array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->binaryOperators;
	}

	/**
	 * Initializes the extensions.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function initExtensions(): void
	{
		$this->parsers = [];
		$this->filters = [];
		$this->functions = [];
		$this->tests = [];
		$this->visitors = [];
		$this->unaryOperators = [];
		$this->binaryOperators = [];

		foreach ($this->extensions as $extension)
			$this->initExtension($extension);

		$this->initExtension($this->staging);

		$this->initialized = true;
	}

	/**
	 * Initializes an extension.
	 *
	 * @param ExtensionInterface $extension
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function initExtension(ExtensionInterface $extension): void
	{
		foreach ($extension->getFilters() as $filter)
			$this->filters[$filter->getName()] = $filter;

		foreach ($extension->getFunctions() as $function)
			$this->functions[$function->getName()] = $function;

		foreach ($extension->getTests() as $test)
			$this->tests[$test->getName()] = $test;

		foreach ($extension->getTokenParsers() as $parser)
		{
			if (!$parser instanceof TokenParserInterface)
				throw new LogicException('getTokenParsers() must return an array of \Cappuccino\TokenParser\TokenParserInterface.');

			$this->parsers[] = $parser;
		}

		foreach ($extension->getNodeVisitors() as $visitor)
			$this->visitors[] = $visitor;

		if ($operators = $extension->getOperators())
		{
			if (!is_array($operators))
				throw new InvalidArgumentException(sprintf('"%s::getOperators()" must return an array with operators, got "%s".', get_class($extension), is_object($operators) ? get_class($operators) : gettype($operators) . (is_resource($operators) ? '' : '#' . $operators)));

			if (count($operators) !== 2)
				throw new InvalidArgumentException(sprintf('"%s::getOperators()" must return an array of 2 elements, got %d.', get_class($extension), count($operators)));

			$this->unaryOperators = array_merge($this->unaryOperators, $operators[0]);
			$this->binaryOperators = array_merge($this->binaryOperators, $operators[1]);
		}
	}

}
