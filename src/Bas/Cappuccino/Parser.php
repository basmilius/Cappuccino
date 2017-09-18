<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\BlockNode;
use Bas\Cappuccino\Node\BlockReferenceNode;
use Bas\Cappuccino\Node\BodyNode;
use Bas\Cappuccino\Node\Expression\AbstractExpression;
use Bas\Cappuccino\Node\MacroNode;
use Bas\Cappuccino\Node\ModuleNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\NodeCaptureInterface;
use Bas\Cappuccino\Node\NodeOutputInterface;
use Bas\Cappuccino\Node\PrintNode;
use Bas\Cappuccino\Node\TextNode;
use Bas\Cappuccino\TokenParser\AbstractTokenParser;
use Bas\Cappuccino\TokenParser\TokenParserInterface;

/**
 * Class Parser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 1.0.0
 */
class Parser
{

	/**
	 * @var TokenStream
	 */
	private $stream;

	/**
	 * @var ExpressionParser
	 */
	private $expressionParser;

	/**
	 * @var AbstractTokenParser[]
	 */
	private $handlers;

	/**
	 * @var Node[]
	 */
	private $stack = [];

	/**
	 * @var AbstractExpression
	 */
	private $parent;

	/**
	 * @var NodeVisitorInterface[]
	 */
	private $visitors;

	/**
	 * @var BlockNode[]
	 */
	private $blocks;

	/**
	 * @var string[]
	 */
	private $blockStack;

	/**
	 * @var Node[]
	 */
	private $macros;

	/**
	 * @var Cappuccino
	 */
	private $cappuccino;

	/**
	 * @var array
	 */
	private $importedSymbols;

	/**
	 * @var Node[]
	 */
	private $traits;

	/**
	 * @var array
	 */
	private $embeddedTemplates = [];

	/**
	 * Parser constructor.
	 *
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Cappuccino $cappuccino)
	{
		$this->cappuccino = $cappuccino;
	}

	/**
	 * Gets the var name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getVarName () : string
	{
		return sprintf('__internal_%s', hash('sha256', uniqid(mt_rand(), true), false));
	}

	/**
	 * Parse.
	 *
	 * @param TokenStream   $stream
	 * @param callable|null $test
	 * @param bool          $dropNeedle
	 *
	 * @return ModuleNode
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (TokenStream $stream, ?callable $test = null, bool $dropNeedle = false) : ModuleNode
	{
		$vars = get_object_vars($this);
		unset($vars['stack'], $vars['env'], $vars['handlers'], $vars['visitors'], $vars['expressionParser'], $vars['reservedMacroNames']);
		$this->stack[] = $vars;

		if ($this->handlers === null)
		{
			$this->handlers = [];

			foreach ($this->cappuccino->getTokenParsers() as $handler)
			{
				$handler->setParser($this);

				$this->handlers[$handler->getTag()] = $handler;
			}
		}

		if ($this->visitors === null)
			$this->visitors = $this->cappuccino->getNodeVisitors();

		if ($this->expressionParser === null)
			$this->expressionParser = new ExpressionParser($this, $this->cappuccino);

		$this->stream = $stream;
		$this->parent = null;
		$this->blocks = [];
		$this->macros = [];
		$this->traits = [];
		$this->blockStack = [];
		$this->importedSymbols = [[]];
		$this->embeddedTemplates = [];

		try
		{
			$body = $this->subparse($test, $dropNeedle);

			if ($this->parent !== null && ($body = $this->filterBodyNodes($body)) === null)
				$body = new Node();
		}
		catch (SyntaxError $e)
		{
			if (!$e->getSourceContext())
				$e->setSourceContext($this->stream->getSourceContext());

			if (!$e->getTemplateLine())
				$e->setTemplateLine($this->stream->getCurrent()->getLine());

			throw $e;
		}

		$node = new ModuleNode(new BodyNode([$body]), $this->parent, new Node($this->blocks), new Node($this->macros), new Node($this->traits), $this->embeddedTemplates, $stream->getSourceContext());
		$traverser = new NodeTraverser($this->cappuccino, $this->visitors);

		/** @var ModuleNode $node */
		$node = $traverser->traverse($node);

		foreach (array_pop($this->stack) as $key => $val)
			$this->{$key} = $val;

		return $node;
	}

	/**
	 * Sub Parse.
	 *
	 * @param callable|null $test
	 * @param bool          $dropNeedle
	 *
	 * @return Node
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function subparse (?callable $test, bool $dropNeedle = false) : Node
	{
		$lineno = $this->getCurrentToken()->getLine();
		$rv = [];

		while (!$this->stream->isEOF())
		{
			switch ($this->getCurrentToken()->getType())
			{
				case Token::TEXT_TYPE:
					$token = $this->stream->next();
					$rv[] = new TextNode($token->getValue(), $token->getLine());
					break;

				case Token::VAR_START_TYPE:
					$token = $this->stream->next();
					$expr = $this->expressionParser->parseExpression();
					$this->stream->expect(Token::VAR_END_TYPE);
					$rv[] = new PrintNode($expr, $token->getLine());
					break;

				case Token::BLOCK_START_TYPE:
					$this->stream->next();
					$token = $this->getCurrentToken();

					if ($token->getType() !== Token::NAME_TYPE)
						throw new SyntaxError('A block must start with a tag name.', $token->getLine(), $this->stream->getSourceContext());

					if ($test !== null && $test($token))
					{
						if ($dropNeedle)
							$this->stream->next();

						if (count($rv) === 1)
							return $rv[0];

						return new Node($rv, [], $lineno);
					}

					if (!isset($this->handlers[$token->getValue()]))
					{
						if ($test !== null)
						{
							$e = new SyntaxError(sprintf('Unexpected "%s" tag', $token->getValue()), $token->getLine(), $this->stream->getSourceContext());

							if (is_array($test) && isset($test[0]) && $test[0] instanceof TokenParserInterface)
								$e->appendMessage(sprintf(' (expecting closing tag for the "%s" tag defined near line %s).', $test[0]->getTag(), $lineno));
						}
						else
						{
							$e = new SyntaxError(sprintf('Unknown "%s" tag.', $token->getValue()), $token->getLine(), $this->stream->getSourceContext());
							$e->addSuggestions($token->getValue(), array_keys($this->cappuccino->getTags()));
						}

						throw $e;
					}

					$this->stream->next();

					$subparser = $this->handlers[$token->getValue()];
					$node = $subparser->parse($token);

					if ($node !== null)
						$rv[] = $node;

					break;

				default:
					throw new SyntaxError('Lexer or parser ended up in unsupported state.', $this->getCurrentToken()->getLine(), $this->stream->getSourceContext());
			}
		}

		if (count($rv) === 1)
			return $rv[0];

		return new Node($rv, [], $lineno);
	}

	/**
	 * Gets the block stack.
	 *
	 * @return string[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBlockStack () : array
	{
		return $this->blockStack;
	}

	/**
	 * Peeks the block stack.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function peekBlockStack ()
	{
		return $this->blockStack[count($this->blockStack) - 1];
	}

	/**
	 * Pops the block stack.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function popBlockStack () : void
	{
		array_pop($this->blockStack);
	}

	/**
	 * Push to the block stack.
	 *
	 * @param string $name
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function pushBlockStack (string $name) : void
	{
		$this->blockStack[] = $name;
	}

	/**
	 * Returns TRUE if there is a block available with the given name.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasBlock (string $name) : bool
	{
		return isset($this->blocks[$name]);
	}

	/**
	 * Gets a block by the given name.
	 *
	 * @param string $name
	 *
	 * @return BlockNode|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBlock (string $name) : ?BlockNode
	{
		return $this->blocks[$name];
	}

	/**
	 * Sets a block.
	 *
	 * @param string    $name
	 * @param BlockNode $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setBlock (string $name, BlockNode $value) : void
	{
		$this->blocks[$name] = new BodyNode([$value], [], $value->getTemplateLine());
	}

	/**
	 * Returns TRUE if there is a macro with the given name.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasMacro (string $name) : bool
	{
		return isset($this->macros[$name]);
	}

	/**
	 * Sets a macro.
	 *
	 * @param string    $name
	 * @param MacroNode $node
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setMacro (string $name, MacroNode $node) : void
	{
		$this->macros[$name] = $node;
	}

	/**
	 * Returns TRUE if the given name is reserved.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isReservedMacroName (string $name) : bool
	{
		if ($name === 'bas')
			return true;

		return false;
	}

	/**
	 * Adds a trait.
	 *
	 * @param Node $trait
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTrait (Node $trait) : void
	{
		$this->traits[] = $trait;
	}

	/**
	 * Returns TRUE if there are Traits available.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasTraits () : bool
	{
		return count($this->traits) > 0;
	}

	/**
	 * Embeds a template.
	 *
	 * @param ModuleNode $template
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function embedTemplate (ModuleNode $template) : void
	{
		$template->setIndex(mt_rand());

		$this->embeddedTemplates[] = $template;
	}

	/**
	 * Adds an imported symbol.
	 *
	 * @param string                  $type
	 * @param string                  $alias
	 * @param string|null             $name
	 * @param AbstractExpression|null $node
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addImportedSymbol (string $type, string $alias, ?string $name = null, ?AbstractExpression $node = null) : void
	{
		$this->importedSymbols[0][$type][$alias] = ['name' => $name, 'node' => $node];
	}

	/**
	 * Gets an imported symbol.
	 *
	 * @param string $type
	 * @param string $alias
	 *
	 * @return array|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getImportedSymbol (string $type, string $alias) : ?array
	{
		foreach ($this->importedSymbols as $functions)
			if (isset($functions[$type][$alias]))
				return $functions[$type][$alias];

		return null;
	}

	/**
	 * Returns TRUE if this is the main scope.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isMainScope () : bool
	{
		return count($this->importedSymbols) === 1;
	}

	/**
	 * Pushes the local scope.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function pushLocalScope () : void
	{
		array_unshift($this->importedSymbols, []);
	}

	/**
	 * Pops the local scope.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function popLocalScope () : void
	{
		array_shift($this->importedSymbols);
	}

	/**
	 * Gets the {@see ExpressionParser}.
	 *
	 * @return ExpressionParser
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getExpressionParser () : ExpressionParser
	{
		return $this->expressionParser;
	}

	/**
	 * Gets the parent {@see AbstractExpression}.
	 *
	 * @return AbstractExpression
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getParent () : AbstractExpression
	{
		return $this->parent;
	}

	/**
	 * Sets the parent {@see AbstractExpression}.
	 *
	 * @param AbstractExpression $parent
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setParent (AbstractExpression $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * Gets the {@see TokenStream}.
	 *
	 * @return TokenStream
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getStream () : TokenStream
	{
		return $this->stream;
	}

	/**
	 * Gets the current {@see Token}.
	 *
	 * @return Token
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCurrentToken () : Token
	{
		return $this->stream->getCurrent();
	}

	/**
	 * Filters body nodes.
	 *
	 * @param Node $node
	 *
	 * @return Node|null
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function filterBodyNodes (Node $node) : ?Node
	{
		if (($node instanceof TextNode && !ctype_space($node->getAttribute('data'))) || (!$node instanceof TextNode && !$node instanceof BlockReferenceNode && $node instanceof NodeOutputInterface))
		{
			if (strpos((string)$node, chr(0xEF) . chr(0xBB) . chr(0xBF)))
				throw new SyntaxError('A template that extends another one cannot start with a byte order mark (BOM); it must be removed.', $node->getTemplateLine(), $this->stream->getSourceContext());

			throw new SyntaxError('A template that extends another one cannot include contents outside Cappuccino blocks. Did you forget to put the contents inside a {% block %} tag?', $node->getTemplateLine(), $this->stream->getSourceContext());
		}

		if ($node instanceof NodeCaptureInterface)
			return $node;

		if ($node instanceof NodeOutputInterface)
			return null;

		foreach ($node as $k => $n)
			if ($n !== null && $this->filterBodyNodes($n) === null)
				$node->removeNode($k);

		return $node;
	}
}
