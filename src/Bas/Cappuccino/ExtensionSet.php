<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Error\RuntimeError;
use Bas\Cappuccino\Extension\ExtensionInterface;
use Bas\Cappuccino\Extension\GlobalsInterface;
use Bas\Cappuccino\Extension\InitRuntimeInterface;
use Bas\Cappuccino\Extension\StagingExtension;
use Bas\Cappuccino\TokenParser\TokenParserInterface;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionObject;
use UnexpectedValueException;

/**
 * Class ExtensionSet
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 1.0.0
 */
final class ExtensionSet implements ExtensionInterface
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
	 * @var SimpleFilter[]
	 */
	private $filters;

	/**
	 * @var SimpleTest[]
	 */
	private $tests;

	/**
	 * @var SimpleFunction[]
	 */
	private $functions;

	/**
	 * @var array
	 */
	private $unaryOperators;

	/**
	 * @var array
	 */
	private $binaryOperators;

	/**
	 * @var array
	 */
	private $globals;

	/**
	 * @var array
	 */
	private $functionCallbacks = [];

	/**
	 * @var array
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
	public function __construct ()
	{
		$this->staging = new StagingExtension();
	}

	/**
	 * Initializes the runtime environment.
	 *
	 * @param Cappuccino $env
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function initRuntime (Cappuccino $env)
	{
		if ($this->runtimeInitialized)
			return;

		$this->runtimeInitialized = true;

		foreach ($this->extensions as $extension)
			if ($extension instanceof InitRuntimeInterface)
				$extension->initRuntime($env);
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
	public function hasExtension (string $class) : bool
	{
		$class = ltrim($class, '\\');

		if (!isset($this->extensions[$class]) && class_exists($class, false))
			$class = (new ReflectionClass($class))->name;

		return isset($this->extensions[$class]);
	}

	/**
	 * Gets an extension by class name.
	 *
	 * @param string $class
	 *
	 * @return ExtensionInterface|null
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExtension (string $class) : ?ExtensionInterface
	{
		$class = ltrim($class, '\\');

		if (!isset($this->extensions[$class]) && class_exists($class, false))
			$class = (new ReflectionClass($class))->name;

		if (!isset($this->extensions[$class]))
			throw new RuntimeError(sprintf('The "%s" extension is not enabled.', $class));

		return $this->extensions[$class];
	}

	/**
	 * Adds extensions.
	 *
	 * @param array $extensions
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setExtensions (array $extensions) : void
	{
		foreach ($extensions as $extension)
			$this->addExtension($extension);
	}

	/**
	 * Gets extensions.
	 *
	 * @return ExtensionInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExtensions () : array
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
	public function getSignature () : string
	{
		return json_encode(array_keys($this->extensions));
	}

	/**
	 * Returns TRUE if we're initialized.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isInitialized () : bool
	{
		return $this->initialized || $this->runtimeInitialized;
	}

	/**
	 * Gets the last modified integer.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getLastModified () : int
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
	 * Adds an {@see ExtensionInterface}.
	 *
	 * @param ExtensionInterface $extension
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addExtension (ExtensionInterface $extension) : void
	{
		$class = get_class($extension);

		if ($this->initialized)
			throw new LogicException(sprintf('Unable to register extension "%s" as extensions have already been initialized.', $class));

		if (isset($this->extensions[$class]))
			throw new LogicException(sprintf('Unable to register extension "%s" as it is already registered.', $class));

		$this->extensions[$class] = $extension;
	}

	/**
	 * Adds a {@see SimpleFunction}.
	 *
	 * @param SimpleFunction $function
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addFunction (SimpleFunction $function) : void
	{
		if ($this->initialized)
			throw new LogicException(sprintf('Unable to add function "%s" as extensions have already been initialized.', $function->getName()));

		$this->staging->addFunction($function);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions () : array
	{
		if (!$this->initialized)
		{
			$this->initExtensions();
		}

		return $this->functions;
	}

	/**
	 * Gets a {@see SimpleFunction}.
	 *
	 * @param string $name
	 *
	 * @return SimpleFunction|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunction (string $name) : ?SimpleFunction
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
			if (false !== $function = $callback($name))
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
	public function registerUndefinedFunctionCallback (callable $callable) : void
	{
		$this->functionCallbacks[] = $callable;
	}

	/**
	 * Adds a {@see SimpleFilter}.
	 *
	 * @param SimpleFilter $filter
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addFilter (SimpleFilter $filter) : void
	{
		if ($this->initialized)
			throw new LogicException(sprintf('Unable to add filter "%s" as extensions have already been initialized.', $filter->getName()));

		$this->staging->addFilter($filter);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters () : array
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
	 * @return SimpleFilter|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilter (string $name) : ?SimpleFilter
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
			if (false !== $filter = $callback($name))
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
	public function registerUndefinedFilterCallback (callable $callable) : void
	{
		$this->filterCallbacks[] = $callable;
	}

	/**
	 * Adds a Node Visitor.
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addNodeVisitor (NodeVisitorInterface $visitor) : void
	{
		if ($this->initialized)
			throw new LogicException('Unable to add a node visitor as extensions have already been initialized.');

		$this->staging->addNodeVisitor($visitor);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors () : array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->visitors;
	}

	/**
	 * Adds a Token Parser.
	 *
	 * @param TokenParserInterface $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTokenParser (TokenParserInterface $parser) : void
	{
		if ($this->initialized)
			throw new LogicException('Unable to add a token parser as extensions have already been initialized.');

		$this->staging->addTokenParser($parser);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers () : array
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
	public function getGlobals () : array
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
	 * @param SimpleTest $test
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTest (SimpleTest $test) : void
	{
		if ($this->initialized)
			throw new LogicException(sprintf('Unable to add test "%s" as extensions have already been initialized.', $test->getName()));

		$this->staging->addTest($test);
	}

	/**
	 * Gets a test by name.
	 *
	 * @param string $name
	 *
	 * @return SimpleTest|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTest (string $name) : ?SimpleTest
	{
		if (!$this->initialized)
			$this->initExtensions();

		if (isset($this->tests[$name]))
			return $this->tests[$name];

		return null;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests () : array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->tests;
	}

	/**
	 * Gets the registered unary operators.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getUnaryOperators () : array
	{
		if (!$this->initialized)
			$this->initExtensions();

		return $this->unaryOperators;
	}

	/**
	 * Gets the registered binary operators.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBinaryOperators () : array
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
	private function initExtensions () : void
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
	 * Initializes a extension.
	 *
	 * @param ExtensionInterface $extension
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function initExtension (ExtensionInterface $extension) : void
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
				throw new LogicException('getTokenParsers() must return an array of TokenParserInterface.');

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

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getOperators () : array
	{
		return [];
	}

}
