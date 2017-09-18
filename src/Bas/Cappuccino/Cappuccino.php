<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Cache\CacheInterface;
use Bas\Cappuccino\Cache\NullCache;
use Bas\Cappuccino\Error\Error;
use Bas\Cappuccino\Error\LoaderError;
use Bas\Cappuccino\Error\RuntimeError;
use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Extension\CoreExtension;
use Bas\Cappuccino\Extension\EscaperExtension;
use Bas\Cappuccino\Extension\ExtensionInterface;
use Bas\Cappuccino\Extension\OptimizerExtension;
use Bas\Cappuccino\Loader\ArrayLoader;
use Bas\Cappuccino\Loader\ChainLoader;
use Bas\Cappuccino\Loader\LoaderInterface;
use Bas\Cappuccino\Node\ModuleNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\RuntimeLoader\RuntimeLoaderInterface;
use Bas\Cappuccino\TokenParser\TokenParserInterface;
use Exception;
use LogicException;

/**
 * Class Cappuccino
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @since 1.0.0
 */
class Cappuccino
{

	public const VERSION = '1.0.0-dev';
	public const VERSION_ID = 10000;
	public const MAJOR_VERSION = 1;
	public const MINOR_VERSION = 0;
	public const RELEASE_VERSION = 0;
	public const EXTRA_VERSION = 'dev';

	public const DEFAULT_EXTENSION = '.capy';

	/**
	 * @var CacheInterface
	 */
	private $cache;

	private $charset;
	private $loader;
	private $debug;
	private $autoReload;
	private $lexer;
	private $parser;
	private $compiler;
	private $baseTemplateClass;
	private $globals = [];
	private $resolvedGlobals;
	private $loadedTemplates;
	private $strictVariables;
	private $templateClassPrefix = 'CappuccinoTemplate___';
	private $originalCache;
	private $extensionSet;
	private $runtimeLoaders = [];
	private $runtimes = [];
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
	public function __construct (LoaderInterface $loader, array $options = [])
	{
		$this->setLoader($loader);

		$options = array_merge([
			'debug' => true,
			'charset' => 'UTF-8',
			'base_template_class' => Template::class,
			'strict_variables' => false,
			'autoescape' => 'html',
			'cache' => new NullCache(),
			'auto_reload' => null,
			'optimizations' => -1,
		], $options);

		$this->debug = (bool)$options['debug'];
		$this->setCharset($options['charset']);
		$this->baseTemplateClass = $options['base_template_class'];
		$this->autoReload = null === $options['auto_reload'] ? $this->debug : (bool)$options['auto_reload'];
		$this->strictVariables = (bool)$options['strict_variables'];
		$this->setCache($options['cache']);
		$this->extensionSet = new ExtensionSet();

		$this->addExtension(new CoreExtension());
		$this->addExtension(new EscaperExtension($options['autoescape']));
		$this->addExtension(new OptimizerExtension($options['optimizations']));
	}

	/**
	 * Gets the base template class for compiled templates.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBaseTemplateClass () : string
	{
		return $this->baseTemplateClass;
	}

	/**
	 * Sets the base template class for compiled templates.
	 *
	 * @param string $class
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setBaseTemplateClass (string $class) : void
	{
		$this->baseTemplateClass = $class;
		$this->updateOptionsHash();
	}

	/**
	 * Enables debugging mode.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enableDebug () : void
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
	public function disableDebug () : void
	{
		$this->debug = false;
		$this->updateOptionsHash();
	}

	/**
	 * Checks if debugging mode is enabled.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isDebug () : bool
	{
		return $this->debug;
	}

	/**
	 * Enables the auto_reload option.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enableAutoReload () : void
	{
		$this->autoReload = true;
	}

	/**
	 * Disables the auto_reload option.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function disableAutoReload () : void
	{
		$this->autoReload = false;
	}

	/**
	 * Checks if the auto_reload option is enabled.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isAutoReload () : bool
	{
		return $this->autoReload;
	}

	/**
	 * Enables the strict_variables option.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function enableStrictVariables () : void
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
	public function disableStrictVariables () : void
	{
		$this->strictVariables = false;
		$this->updateOptionsHash();
	}

	/**
	 * Checks if the strict_variables option is enabled.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isStrictVariables () : bool
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
	public function getCache (bool $original = true) : CacheInterface
	{
		return $original ? $this->originalCache : $this->cache;
	}

	/**
	 * Sets the current cache implementation.
	 *
	 * @param CacheInterface $cache
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setCache (CacheInterface $cache) : void
	{
		if ($cache instanceof CacheInterface)
		{
			$this->originalCache = $this->cache = $cache;
		}
		else
		{
			throw new LogicException('Cache can only be a CacheImplementation.');
		}
	}

	/**
	 * Gets the template class assiciated with the given string.
	 *
	 * @param string   $name
	 * @param int|null $index
	 *
	 * @return string
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTemplateClass (string $name, ?int $index = null) : string
	{
		$key = $this->getLoader()->getCacheKey($name) . $this->optionsHash;

		return $this->templateClassPrefix . hash('sha256', $key) . (null === $index ? '' : '_' . $index);
	}

	/**
	 * Renders a template.
	 *
	 * @param string $name
	 * @param array  $context
	 *
	 * @return string
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function render (string $name, array $context = []) : string
	{
		return $this->loadTemplate($name)->render($context);
	}

	/**
	 * Displays a template.
	 *
	 * @param string $name
	 * @param array  $context
	 *
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @throws Exception
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function display (string $name, array $context = []) : void
	{
		$this->loadTemplate($name)->display($context);
	}

	/**
	 * Loads a template.
	 *
	 * @param string   $name
	 * @param int|null $index
	 *
	 * @return Template
	 * @throws LoaderError
	 * @throws SyntaxError
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function loadTemplate (string $name, ?int $index = null) : Template
	{
		$cls = $mainCls = $this->getTemplateClass($name);

		if (null !== $index)
			$cls .= '_' . $index;

		if (isset($this->loadedTemplates[$cls]))
			return $this->loadedTemplates[$cls];

		if (!class_exists($cls, false))
		{
			$key = $this->cache->generateKey($name, $mainCls);

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
					throw new RuntimeError(sprintf('Failed to load Cappuccino template "%s", index "%s": cache is corrupted.', $name, $index), -1, $source);
			}
		}

		$this->extensionSet->initRuntime($this);

		return $this->loadedTemplates[$cls] = new $cls($this);
	}

	/**
	 * Creates a template from source. This method should not be used as a generic way to load templates.
	 *
	 * @param string $template
	 *
	 * @return Template
	 * @throws Error
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function createTemplate (string $template) : Template
	{
		$name = sprintf('__string_template__%s', hash('sha256', $template, false));

		$loader = new ChainLoader([
			new ArrayLoader([$name => $template]),
			$current = $this->getLoader(),
		]);

		$this->setLoader($loader);

		try
		{
			$template = $this->loadTemplate($name);
		}
		finally
		{
			$this->setLoader($current);
		}

		return $template;
	}

	/**
	 * Returns TRUE if the template is still fresh. Besides checking the loaer for freshness information,
	 * this method also checks if the enabled extensions have not changed.
	 *
	 * @param string $name
	 * @param int    $time
	 *
	 * @return bool
	 * @throws LoaderError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isTemplateFresh (string $name, int $time) : bool
	{
		return $this->extensionSet->getLastModified() <= $time && $this->getLoader()->isFresh($name, $time);
	}

	/**
	 * Tries to load a template consecutively from an array. Similar to {@see Cappuccino::loadTemplate()} but it also accepts
	 * Template instances and an array of templates where each is tried to be loaded.
	 *
	 * @param Template|string|string[] $names
	 *
	 * @return Template
	 * @throws Error
	 * @throws LoaderError
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function resolveTemplate ($names) : Template
	{
		if (!is_array($names))
			$names = [$names];

		foreach ($names as $name)
		{
			if ($name instanceof Template)
				return $name;

			try
			{
				return $this->loadTemplate($name);
			}
			catch (LoaderError $e)
			{
				if (count($names) === 1)
					throw $e;
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
	public function setLexer (Lexer $lexer) : void
	{
		$this->lexer = $lexer;
	}

	/**
	 * Tokenizes a source code.
	 *
	 * @param Source $source
	 *
	 * @return TokenStream
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function tokenize (Source $source) : TokenStream
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
	public function setParser (Parser $parser) : void
	{
		$this->parser = $parser;
	}

	/**
	 * Converts a token stream to a node tree.
	 *
	 * @param TokenStream $stream
	 *
	 * @return ModuleNode
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (TokenStream $stream) : ModuleNode
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
	public function setCompiler (Compiler $compiler) : void
	{
		$this->compiler = $compiler;
	}

	/**
	 * Compiles a node and returns the PHP Code.
	 *
	 * @param Node $node
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Node $node) : string
	{
		if ($this->compiler === null)
			$this->compiler = new Compiler($this);

		return $this->compiler->compile($node)->getSource();
	}

	/**
	 * Compiles a template source code.
	 *
	 * @param Source $source
	 *
	 * @return string
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compileSource (Source $source) : string
	{
		try
		{
			return $this->compile($this->parse($this->tokenize($source)));
		}
		catch (SyntaxError $e)
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
	public function setLoader (LoaderInterface $loader) : void
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
	public function getLoader () : LoaderInterface
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
	public function setCharset (string $charset) : void
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
	public function getCharset () : string
	{
		return $this->charset;
	}

	/**
	 * Returns TRUE if the given extension is registred.
	 *
	 * @param string $class
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasExtension (string $class) : bool
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
	public function addRuntimeLoader (RuntimeLoaderInterface $loader) : void
	{
		$this->runtimeLoaders[] = $loader;
	}

	/**
	 * Gets an extension by class name.
	 *
	 * @param string $class
	 *
	 * @return ExtensionInterface
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExtension (string $class) : ExtensionInterface
	{
		return $this->extensionSet->getExtension($class);
	}

	/**
	 * Returns the runtime implementation of a Cappuccino element (filter/function/test).
	 *
	 * @param string $class
	 *
	 * @return mixed
	 * @throws RuntimeError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @todo Validate return type.
	 */
	public function getRuntime (string $class)
	{
		if (isset($this->runtimes[$class]))
			return $this->runtimes[$class];

		foreach ($this->runtimeLoaders as $loader)
			if (($runtime = $loader->load($class)) !== null)
				return $this->runtimes[$class] = $runtime;

		throw new RuntimeError(sprintf('Unable to load the "%s" runtime.', $class));
	}

	/**
	 * Adds an extension.
	 *
	 * @param ExtensionInterface $extension
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addExtension (ExtensionInterface $extension) : void
	{
		$this->extensionSet->addExtension($extension);
		$this->updateOptionsHash();
	}

	/**
	 * Registers an array of extensions.
	 *
	 * @param array $extensions
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setExtensions (array $extensions) : void
	{
		$this->extensionSet->setExtensions($extensions);
	}

	/**
	 * Returns all registred extensions.
	 *
	 * @return ExtensionInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExtensions () : array
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
	public function addTokenParser (TokenParserInterface $parser) : void
	{
		$this->extensionSet->addTokenParser($parser);
	}

	/**
	 * Gets the registered Token Parsers.
	 *
	 * @return TokenParserInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers () : array
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
	public function getTags () : array
	{
		$tags = [];

		foreach ($this->getTokenParsers() as $parser)
			$tags[$parser->getTag()] = $parser;

		return $tags;
	}

	/**
	 * Adds a {@see NodeVisitorInterface}
	 *
	 * @param NodeVisitorInterface $visitor
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addNodeVisitor (NodeVisitorInterface $visitor) : void
	{
		$this->extensionSet->addNodeVisitor($visitor);
	}

	/**
	 * Gets the registered Node Visitors.
	 *
	 * @return NodeVisitorInterface[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors () : array
	{
		return $this->extensionSet->getNodeVisitors();
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
		$this->extensionSet->addFilter($filter);
	}

	/**
	 * Gets a filter by name. Subclasses may override this method and load filters differently; so no list of filters is available.
	 *
	 * @param string $name
	 *
	 * @return SimpleFilter|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilter (string $name) : ?SimpleFilter
	{
		return $this->extensionSet->getFilter($name);
	}

	/**
	 * Gets the registered filters.
	 *
	 * @return SimpleFilter[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters () : array
	{
		return $this->extensionSet->getFilters();
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
		$this->extensionSet->registerUndefinedFilterCallback($callable);
	}

	/**
	 * Gets a test by name. Subclasses may override this method and load tests differently; so no list of tests is available.
	 *
	 * @param string $name
	 *
	 * @return SimpleTest|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTest (string $name) : ?SimpleTest
	{
		return $this->extensionSet->getTest($name);
	}

	/**
	 * Gets registered tests.
	 *
	 * @return SimpleTest[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests () : array
	{
		return $this->extensionSet->getTests();
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
		$this->extensionSet->addFunction($function);
	}

	/**
	 * Gets a function by name. Subclasses may override this method and load functions differently; so no list of functions is available.
	 *
	 * @param string $name
	 *
	 * @return SimpleFunction|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunction (string $name) : ?SimpleFunction
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
	public function registerUndefinedFunctionCallback (callable $callable) : void
	{
		$this->extensionSet->registerUndefinedFunctionCallback($callable);
	}

	/**
	 * Gets registered functions. Be warned that this method cannot return functions defined with {@see Cappuccino::registerUndefinedFunctionCallback()}.
	 *
	 * @return SimpleFunction[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions () : array
	{
		return $this->extensionSet->getFunctions();
	}

	/**
	 * Registers a global. New globals can be added before compiling or rendering a template; but after, you can only update existing globals.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addGlobal (string $name, $value) : void
	{
		if ($this->extensionSet->isInitialized() && !array_key_exists($name, $this->getGlobals()))
			throw new LogicException(sprintf('Unable to add global "%s" as the runtime or the extensions have already been initialized.', $name));

		if ($this->resolvedGlobals !== null)
			$this->resolvedGlobals[$name] = $value;
		else
			$this->globals[$name] = $value;
	}

	/**
	 * Gets the registered globals.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getGlobals () : array
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
	 * Merges a context with the defined globals.
	 *
	 * @param array $context
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function mergeGlobals (array $context) : array
	{
		foreach ($this->getGlobals() as $key => $value)
			if (!array_key_exists($key, $context))
				$context[$key] = $value;

		return $context;
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
		return $this->extensionSet->getUnaryOperators();
	}

	/**
	 * Gets registered binary operators.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBinaryOperators () : array
	{
		return $this->extensionSet->getBinaryOperators();
	}

	/**
	 * Updates the options hash.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function updateOptionsHash () : void
	{
		$this->optionsHash = implode(':', [
			$this->extensionSet->getSignature(),
			PHP_MAJOR_VERSION,
			PHP_MINOR_VERSION,
			self::VERSION,
			(int)$this->debug,
			$this->baseTemplateClass,
			(int)$this->strictVariables,
		]);
	}

}
