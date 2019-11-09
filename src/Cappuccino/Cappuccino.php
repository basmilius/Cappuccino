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

use Cappuccino\Cache\CacheInterface;
use Cappuccino\Cache\FilesystemCache;
use Cappuccino\Cache\NullCache;
use Cappuccino\Error\Error;
use Cappuccino\Error\LoaderError;
use Cappuccino\Error\RuntimeError;
use Cappuccino\Error\SyntaxError;
use Cappuccino\Extension\CoreExtension;
use Cappuccino\Extension\EscaperExtension;
use Cappuccino\Extension\ExtensionInterface;
use Cappuccino\Extension\OptimizerExtension;
use Cappuccino\Loader\ArrayLoader;
use Cappuccino\Loader\ChainLoader;
use Cappuccino\Loader\LoaderInterface;
use Cappuccino\Node\Expression\Binary\AbstractBinary;
use Cappuccino\Node\ModuleNode;
use Cappuccino\Node\Node;
use Cappuccino\NodeVisitor\NodeVisitorInterface;
use Cappuccino\RuntimeLoader\RuntimeLoaderInterface;
use Cappuccino\TokenParser\TokenParserInterface;
use Exception;
use LogicException;

/**
 * Class Cappuccino
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
class Cappuccino
{

	const VERSION = '2.0.0-dev';
	const VERSION_CODE = 2000000;
	const MAJOR_VERSION = 2;
	const MINOR_VERSION = 0;
	const RELEASE_VERSION = 0;
	const EXTRA_VERSION = 'dev';

	/**
	 * @var string
	 */
	private $charset;

	/**
	 * @var LoaderInterface
	 */
	private $loader;

	/**
	 * @var bool
	 */
	private $debug;

	/**
	 * @var bool
	 */
	private $autoReload;

	/**
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * @var Lexer
	 */
	private $lexer;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var Compiler
	 */
	private $compiler;

	/**
	 * @var array
	 */
	private $globals = [];

	/**
	 * @var array
	 */
	private $resolvedGlobals;

	/**
	 * @var Template[]
	 */
	private $loadedTemplates;

	/**
	 * @var bool
	 */
	private $strictVariables;

	/**
	 * @var string
	 */
	private $templateClassPrefix = 'CT_';

	/**
	 * @var mixed
	 */
	private $originalCache;

	/**
	 * @var ExtensionSet
	 */
	private $extensionSet;

	/**
	 * @var RuntimeLoaderInterface[]
	 */
	private $runtimeLoaders = [];

	/**
	 * @var array
	 */
	private $runtimes = [];

	/**
	 * @var string
	 */
	private $optionsHash;

	/**
	 * Cappuccino constructor.
	 *
	 * @param LoaderInterface $loader
	 * @param array           $options
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(LoaderInterface $loader, $options = [])
	{
		$this->setLoader($loader);

		$options = array_merge([
			'debug' => false,
			'charset' => 'UTF-8',
			'strict_variables' => false,
			'autoescape' => 'html',
			'cache' => null,
			'auto_reload' => null,
			'optimizations' => -1,
		], $options);

		$this->debug = $options['debug'];
		$this->setCharset($options['charset'] ?? 'UTF-8');
		$this->autoReload = $options['auto_reload'] === null ? $this->debug : $options['auto_reload'];
		$this->strictVariables = $options['strict_variables'];
		$this->setCache($options['cache']);
		$this->extensionSet = new ExtensionSet();

		$this->addExtension(new CoreExtension());
		$this->addExtension(new EscaperExtension($options['autoescape']));
		$this->addExtension(new OptimizerExtension($options['optimizations']));
	}

	/**
	 * Enables debugging mode.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enableDebug(): void
	{
		$this->debug = true;
		$this->updateOptionsHash();
	}

	/**
	 * Disables debugging mode.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function disableDebug(): void
	{
		$this->debug = false;
		$this->updateOptionsHash();
	}

	/**
	 * Returns TRUE if debugging is enabled.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isDebug(): bool
	{
		return $this->debug;
	}

	/**
	 * Enables the auto_reload option.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enableAutoReload(): void
	{
		$this->autoReload = true;
	}

	/**
	 * Disables the auto_reload option.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function disableAutoReload(): void
	{
		$this->autoReload = false;
	}

	/**
	 * Returns TRUE if the auto_reload option is enabled.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isAutoReload(): bool
	{
		return $this->autoReload;
	}

	/**
	 * Enables the strict_variables option.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enableStrictVariables(): void
	{
		$this->strictVariables = true;
		$this->updateOptionsHash();
	}

	/**
	 * Disables the strict_variables option.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function disableStrictVariables(): void
	{
		$this->strictVariables = false;
		$this->updateOptionsHash();
	}

	/**
	 * Returns TRUE if the strict_variables option is enabled.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isStrictVariables(): bool
	{
		return $this->strictVariables;
	}

	/**
	 * Gets the current cache implementation.
	 *
	 * @param bool $original
	 *
	 * @return CacheInterface
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCache(bool $original = true): CacheInterface
	{
		return $original ? $this->originalCache : $this->cache;
	}

	/**
	 * Sets the current cache implementation.
	 *
	 * @param CacheInterface|null $cache
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setCache(?CacheInterface $cache)
	{
		if (is_string($cache))
		{
			$this->originalCache = $cache;
			$this->cache = new FilesystemCache($cache);
		}
		else if ($cache === null)
		{
			$this->originalCache = $cache;
			$this->cache = new NullCache();
		}
		else if ($cache instanceof CacheInterface)
		{
			$this->originalCache = $this->cache = $cache;
		}
		else
		{
			throw new LogicException(sprintf('Cache can only be a string, false, or a \Cappuccino\Cache\CacheInterface implementation.'));
		}
	}

	/**
	 * Gets the generated template class name for a template name.
	 *
	 * @param string   $name
	 * @param int|null $index
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTemplateClass(string $name, int $index = null): string
	{
		$key = $this->getLoader()->getCacheKey($name) . $this->optionsHash;

		return $this->templateClassPrefix . hash('sha256', $key) . (null === $index ? '' : '___' . $index);
	}

	/**
	 * Renders a template.
	 *
	 * @param string|TemplateWrapper $name
	 * @param array                  $context
	 *
	 * @return string
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function render($name, array $context = []): string
	{
		return $this->load($name)->render($context);
	}

	/**
	 * Displays a template.
	 *
	 * @param string|TemplateWrapper $name
	 * @param array                  $context
	 *
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function display($name, array $context = []): void
	{
		$this->load($name)->display($context);
	}

	/**
	 * Loads a template. This method also accepts a {@see TemplateWrapper}.
	 *
	 * @param string|TemplateWrapper $name
	 *
	 * @return TemplateWrapper
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function load($name): TemplateWrapper
	{
		if ($name instanceof TemplateWrapper)
			return $name;

		return new TemplateWrapper($this, $this->loadTemplate($this->getTemplateClass($name), $name));
	}

	/**
	 * Loads a template.
	 *
	 * @param string   $cls
	 * @param string   $name
	 * @param int|null $index
	 *
	 * @return Template
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function loadTemplate(string $cls, string $name, int $index = null): Template
	{
		$mainCls = $cls;

		if ($index !== null)
			$cls .= '___' . $index;

		if (isset($this->loadedTemplates[$cls]))
			return $this->loadedTemplates[$cls];

		if (!class_exists($cls, false))
		{
			$key = $this->cache->generateKey($name, $mainCls);
			$source = null;

			if (!$this->isAutoReload() || $this->isTemplateFresh($name, $this->cache->getTimestamp($key)))
				$this->cache->load($key);

			if (!class_exists($cls, false))
			{
				$source = $this->getLoader()->getSourceContext($name);
				$content = $this->compileSource($source);
				$this->cache->write($key, $content);
				$this->cache->load($key);

				if (!class_exists($mainCls, false))
					eval('?>' . $content);

				if (!class_exists($cls, false))
					throw new RuntimeError(sprintf('Failed to load Cappuccino template "%s", index "%s": cache might be corrupted.', $name, $index), -1, $source);
			}
		}

		$this->extensionSet->initRuntime();

		return $this->loadedTemplates[$cls] = new $cls($this);
	}

	/**
	 * Creates a template from string. This method should not be used as a generic way to load templates.
	 *
	 * @param string      $template
	 * @param string|null $name
	 *
	 * @return TemplateWrapper
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function createTemplate(string $template, string $name = null): TemplateWrapper
	{
		$hash = hash('sha256', $template, false);

		if ($name !== null)
			$name = sprintf('%s (string template %s)', $name, $hash);
		else
			$name = sprintf('__string_template__%s', $hash);

		$loader = new ChainLoader([
			new ArrayLoader([$name => $template]),
			$current = $this->getLoader()
		]);

		$this->setLoader($loader);

		try
		{
			return new TemplateWrapper($this, $this->loadTemplate($this->getTemplateClass($name), $name));
		}
		finally
		{
			$this->setLoader($current);
		}
	}

	/**
	 * Returns THE If the template is still fresh. Besides checking the loader for freshness information,
	 * this method also checks if the enabled extensions have changed.
	 *
	 * @param string $name
	 * @param int    $time
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isTemplateFresh(string $name, int $time): bool
	{
		return $this->extensionSet->getLastModified() <= $time && $this->getLoader()->isFresh($name, $time);
	}

	/**
	 * Tries resolve a template.
	 *
	 * @param $names
	 *
	 * @return TemplateWrapper
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @see Cappuccino::load()
	 */
	public function resolveTemplate($names): TemplateWrapper
	{
		if (!is_array($names))
			return $this->load($names);

		foreach ($names as $name)
		{
			try
			{
				return $this->load($name);
			}
			catch (LoaderError $e)
			{
			}
		}

		throw new LoaderError(sprintf('Unable to find one of the following templates: "%s".', implode('", "', $names)));
	}

	/**
	 * Sets the {@see Lexer}.
	 *
	 * @param Lexer $lexer
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setLexer(Lexer $lexer): void
	{
		$this->lexer = $lexer;
	}

	/**
	 * Tokenizes source.
	 *
	 * @param Source $source
	 *
	 * @return TokenStream
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function tokenize(Source $source): TokenStream
	{
		if ($this->lexer === null)
			$this->lexer = new Lexer($this);

		return $this->lexer->tokenize($source);
	}

	/**
	 * Sets the {@see Parser}.
	 *
	 * @param Parser $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setParser(Parser $parser): void
	{
		$this->parser = $parser;
	}

	/**
	 * Converts a token stream to a node tree.
	 *
	 * @param TokenStream $stream
	 *
	 * @return ModuleNode
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function parse(TokenStream $stream): ModuleNode
	{
		if ($this->parser === null)
			$this->parser = new Parser($this);

		return $this->parser->parse($stream);
	}

	/**
	 * Sets the {@see Compiler}.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setCompiler(Compiler $compiler): void
	{
		$this->compiler = $compiler;
	}

	/**
	 * Compiles a node and returns the PHP code.
	 *
	 * @param Node $node
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Node $node): string
	{
		if ($this->compiler === null)
			$this->compiler = new Compiler($this);

		return $this->compiler->compile($node)->getSource();
	}

	/**
	 * Compiles template source.
	 *
	 * @param Source $source
	 *
	 * @return string
	 * @throws Error
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compileSource(Source $source): string
	{
		try
		{
			return $this->compile($this->parse($this->tokenize($source)));
		}
		catch (Error $e)
		{
			$e->setSourceContext($source);
			throw $e;
		}
		catch (Exception $e)
		{
			throw new SyntaxError(sprintf('An exception has been thrown during the compilation of a template ("%s").', $e->getMessage()), -1, $source, $e);
		}
	}

	/**
	 * Sets the {@see LoaderInterface}.
	 *
	 * @param LoaderInterface $loader
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setLoader(LoaderInterface $loader): void
	{
		$this->loader = $loader;
	}

	/**
	 * Gets the {@see LoaderInterface} instance.
	 *
	 * @return LoaderInterface
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getLoader(): LoaderInterface
	{
		return $this->loader;
	}

	/**
	 * Sets the default template charset.
	 *
	 * @param string $charset
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setCharset(string $charset)
	{
		if (($charset = strtoupper($charset)) === 'UTF8')
			$charset = 'UTF-8';

		$this->charset = $charset;
	}

	/**
	 * Gets the default template charset.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCharset(): string
	{
		return $this->charset;
	}

	/**
	 * Returns TRUE if the given extension is registered.
	 *
	 * @param string $class
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasExtension(string $class): bool
	{
		return $this->extensionSet->hasExtension($class);
	}

	/**
	 * Adds a runtime loader.
	 *
	 * @param RuntimeLoaderInterface $loader
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addRuntimeLoader(RuntimeLoaderInterface $loader): void
	{
		$this->runtimeLoaders[] = $loader;
	}

	/**
	 * Gets a extension by class name.
	 *
	 * @param string $class
	 *
	 * @return ExtensionInterface
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function getExtension(string $class): ExtensionInterface
	{
		return $this->extensionSet->getExtension($class);
	}

	/**
	 * Returns the runtime implementation of a Cappuccino element (filter/function/test).
	 *
	 * @param string $class
	 *
	 * @return mixed
	 * @throws Error
	 * @since 1.0.0
	 * @author Bas Milius <bas@mili.us>
	 */
	public function getRuntime(string $class)
	{
		if (isset($this->runtimes[$class]))
			return $this->runtimes[$class];

		foreach ($this->runtimeLoaders as $loader)
			if (null !== $runtime = $loader->load($class))
				return $this->runtimes[$class] = $runtime;

		throw new RuntimeError(sprintf('Unable to load the "%s" runtime.', $class));
	}

	/**
	 * Adds an {@see ExtensionInterface}.
	 *
	 * @param ExtensionInterface $extension
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addExtension(ExtensionInterface $extension): void
	{
		$this->extensionSet->addExtension($extension);
		$this->updateOptionsHash();
	}

	/**
	 * Sets the registered extensions.
	 *
	 * @param ExtensionInterface[] $extensions
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setExtensions(array $extensions): void
	{
		$this->extensionSet->setExtensions($extensions);
		$this->updateOptionsHash();
	}

	/**
	 * Gets all registered extensions.
	 *
	 * @return ExtensionInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExtensions(): array
	{
		return $this->extensionSet->getExtensions();
	}

	/**
	 * Adds a {@see TokenParserInterface}.
	 *
	 * @param TokenParserInterface $parser
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTokenParser(TokenParserInterface $parser): void
	{
		$this->extensionSet->addTokenParser($parser);
	}

	/**
	 * Gets the registered token parsers.
	 *
	 * @return TokenParserInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers(): array
	{
		return $this->extensionSet->getTokenParsers();
	}

	/**
	 * Gets registered tags.
	 *
	 * @return TokenParserInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTags(): array
	{
		$tags = [];

		foreach ($this->getTokenParsers() as $parser)
			$tags[$parser->getTag()] = $parser;

		return $tags;
	}

	/**
	 * Adds a {@see NodeVisitorInterface}.
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addNodeVisitor(NodeVisitorInterface $visitor): void
	{
		$this->extensionSet->addNodeVisitor($visitor);
	}

	/**
	 * Gets the registered node visitors.
	 *
	 * @return NodeVisitorInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array
	{
		return $this->extensionSet->getNodeVisitors();
	}

	/**
	 * Adds a {@see CappuccinoFilter}.
	 *
	 * @param CappuccinoFilter $filter
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addFilter(CappuccinoFilter $filter): void
	{
		$this->extensionSet->addFilter($filter);
	}

	/**
	 * Gets a filter by name. Subclasses may override this method and load filters differently; so no list of filters is available.
	 *
	 * @param string $name
	 *
	 * @return CappuccinoFilter|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilter(string $name): ?CappuccinoFilter
	{
		return $this->extensionSet->getFilter($name);
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
		$this->extensionSet->registerUndefinedFilterCallback($callable);
	}

	/**
	 * Gets registered filters. Be warned that this method cannot return filters defined with {@see Cappuccino::registerUndefinedFilterCallback()}.
	 *
	 * @return CappuccinoFilter[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters(): array
	{
		return $this->extensionSet->getFilters();
	}

	/**
	 * Adds a {@see CappuccinoTest}.
	 *
	 * @param CappuccinoTest $test
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTest(CappuccinoTest $test): void
	{
		$this->extensionSet->addTest($test);
	}

	/**
	 * Gets registered tests.
	 *
	 * @return CappuccinoTest[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests(): array
	{
		return $this->extensionSet->getTests();
	}

	/**
	 * Gets a test by name. Subclasses may override this method and load tests differently; so no list of tests is available.
	 *
	 * @param string $name
	 *
	 * @return CappuccinoTest|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTest(string $name): ?CappuccinoTest
	{
		return $this->extensionSet->getTest($name);
	}

	/**
	 * Adds a {@see CappuccinoFunction}.
	 *
	 * @param CappuccinoFunction $function
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addFunction(CappuccinoFunction $function): void
	{
		$this->extensionSet->addFunction($function);
	}

	/**
	 * Gets a function by name. Subclasses may override this method and load functions differently; so no list of functions is available.
	 *
	 * @param string $name
	 *
	 * @return CappuccinoFunction|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunction(string $name): ?CappuccinoFunction
	{
		return $this->extensionSet->getFunction($name);
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
		$this->extensionSet->registerUndefinedFunctionCallback($callable);
	}

	/**
	 * Gets registered functions. Be warned that this method cannot return functions defined with {@see Cappuccino::registerUndefinedFunctionCallback()}.
	 *
	 * @return CappuccinoFunction[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions(): array
	{
		return $this->extensionSet->getFunctions();
	}

	/**
	 * Registers a global. New globals can be added before compiling or rendering a template; but after, you can only update existing ones.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addGlobal(string $name, $value): void
	{
		if ($this->extensionSet->isInitialized() && !isset($this->getGlobals()[$name]))
			throw new LogicException(sprintf('Unable to add global "%s" as the runtime or the extensions have already been initialized.', $name));

		if ($this->resolvedGlobals !== null)
			$this->resolvedGlobals[$name] = $value;
		else
			$this->globals[$name] = $value;
	}

	/**
	 * Gets registered globals.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getGlobals(): array
	{
		if ($this->extensionSet->isInitialized())
		{
			if ($this->resolvedGlobals === null)
				$this->resolvedGlobals = array_merge($this->extensionSet->getGlobals(), $this->globals);

			return $this->resolvedGlobals;
		}

		return array_merge($this->extensionSet->getGlobals(), $this->globals);
	}

	/**
	 * Merges a context with the registered globals.
	 *
	 * @param array $context
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function mergeGlobals(array $context): array
	{
		foreach ($this->getGlobals() as $key => $value)
			if (!isset($contextp[$key]))
				$context[$key] = $value;

		return $context;
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
		return $this->extensionSet->getUnaryOperators();
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
		return $this->extensionSet->getBinaryOperators();
	}

	/**
	 * Updates the options hash.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function updateOptionsHash(): void
	{
		$this->optionsHash = implode(':', [$this->extensionSet->getSignature(), PHP_MAJOR_VERSION, PHP_MINOR_VERSION, self::VERSION, (int)$this->debug, (int)$this->strictVariables]);
	}

}
