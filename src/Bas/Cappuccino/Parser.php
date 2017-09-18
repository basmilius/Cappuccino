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
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino
 * @version 2.3.0
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

	private $stack = [];
	private $parent;
	private $visitors;
	private $blocks;
	private $blockStack;
	private $macros;
	private $environment;
	private $importedSymbols;
	private $traits;
	private $embeddedTemplates = [];

	/**
	 * Parser constructor.
	 *
	 * @param Environment $environment
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (Environment $environment)
	{
		$this->environment = $environment;
	}

	/**
	 * Gets the var name.
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function parse (TokenStream $stream, ?callable $test = null, bool $dropNeedle = false) : ModuleNode
	{
		$vars = get_object_vars($this);
		unset($vars['stack'], $vars['env'], $vars['handlers'], $vars['visitors'], $vars['expressionParser'], $vars['reservedMacroNames']);
		$this->stack[] = $vars;

		if ($this->handlers === null)
		{
			$this->handlers = [];

			foreach ($this->environment->getTokenParsers() as $handler)
			{
				$handler->setParser($this);

				$this->handlers[$handler->getTag()] = $handler;
			}
		}

		if ($this->visitors === null)
			$this->visitors = $this->environment->getNodeVisitors();

		if ($this->expressionParser === null)
			$this->expressionParser = new ExpressionParser($this, $this->environment);

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
		$traverser = new NodeTraverser($this->environment, $this->visitors);

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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
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
							$e->addSuggestions($token->getValue(), array_keys($this->environment->getTags()));
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

	public function getBlockStack ()
	{
		return $this->blockStack;
	}

	public function peekBlockStack ()
	{
		return $this->blockStack[count($this->blockStack) - 1];
	}

	public function popBlockStack ()
	{
		array_pop($this->blockStack);
	}

	public function pushBlockStack ($name)
	{
		$this->blockStack[] = $name;
	}

	public function hasBlock ($name)
	{
		return isset($this->blocks[$name]);
	}

	public function getBlock ($name) : ?BlockNode
	{
		return $this->blocks[$name];
	}

	public function setBlock ($name, BlockNode $value)
	{
		$this->blocks[$name] = new BodyNode([$value], [], $value->getTemplateLine());
	}

	public function hasMacro ($name)
	{
		return isset($this->macros[$name]);
	}

	public function setMacro ($name, MacroNode $node)
	{
		$this->macros[$name] = $node;
	}

	public function isReservedMacroName ($name)
	{
		if ($name === 'bas')
			return true;

		return false;
	}

	public function addTrait ($trait)
	{
		$this->traits[] = $trait;
	}

	public function hasTraits ()
	{
		return count($this->traits) > 0;
	}

	public function embedTemplate (ModuleNode $template)
	{
		$template->setIndex(mt_rand());

		$this->embeddedTemplates[] = $template;
	}

	public function addImportedSymbol ($type, $alias, $name = null, AbstractExpression $node = null)
	{
		$this->importedSymbols[0][$type][$alias] = ['name' => $name, 'node' => $node];
	}

	public function getImportedSymbol ($type, $alias)
	{
		foreach ($this->importedSymbols as $functions)
			if (isset($functions[$type][$alias]))
				return $functions[$type][$alias];

		return null;
	}

	public function isMainScope ()
	{
		return 1 === count($this->importedSymbols);
	}

	public function pushLocalScope ()
	{
		array_unshift($this->importedSymbols, []);
	}

	public function popLocalScope ()
	{
		array_shift($this->importedSymbols);
	}

	/**
	 * @return ExpressionParser
	 */
	public function getExpressionParser ()
	{
		return $this->expressionParser;
	}

	public function getParent ()
	{
		return $this->parent;
	}

	public function setParent ($parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @return TokenStream
	 */
	public function getStream ()
	{
		return $this->stream;
	}

	/**
	 * @return Token
	 */
	public function getCurrentToken ()
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
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	private function filterBodyNodes (Node $node) : ?Node
	{
		if (($node instanceof TextNode && !ctype_space($node->getAttribute('data'))) || (!$node instanceof TextNode && !$node instanceof BlockReferenceNode && $node instanceof NodeOutputInterface))
		{
			if (strpos((string)$node, chr(0xEF) . chr(0xBB) . chr(0xBF)))
				throw new SyntaxError('A template that extends another one cannot start with a byte order mark (BOM); it must be removed.', $node->getTemplateLine(), $this->stream->getSourceContext());

			throw new SyntaxError('A template that extends another one cannot include contents outside Twig blocks. Did you forget to put the contents inside a {% block %} tag?', $node->getTemplateLine(), $this->stream->getSourceContext());
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
