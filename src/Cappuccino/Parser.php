<?php
/**
 * Copyright (c) 2017 - 2019 - Bas Milius <bas@mili.us>
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=0); // TODO(Bas): Figure out if we can enable this again.

namespace Cappuccino;

use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\BlockNode;
use Cappuccino\Node\BlockReferenceNode;
use Cappuccino\Node\BodyNode;
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Node\MacroNode;
use Cappuccino\Node\ModuleNode;
use Cappuccino\Node\Node;
use Cappuccino\Node\NodeCaptureInterface;
use Cappuccino\Node\NodeOutputInterface;
use Cappuccino\Node\PrintNode;
use Cappuccino\Node\TextNode;
use Cappuccino\NodeVisitor\NodeVisitorInterface;
use Cappuccino\TokenParser\AbstractTokenParser;
use Cappuccino\TokenParser\TokenParserInterface;

/**
 * Class Parser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
class Parser
{

	/**
	 * @var Node[]
	 */
	private $stack = [];

	/**
	 * @var TokenStream
	 */
	private $stream;

	/**
	 * @var Node|null
	 */
	private $parent;

	/**
	 * @var AbstractTokenParser[]
	 */
	private $handlers;

	/**
	 * @var NodeVisitorInterface[]
	 */
	private $visitors;

	/**
	 * @var ExpressionParser
	 */
	private $expressionParser;

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
	 * @var int
	 */
	private $varNameSalt = 0;

	/**
	 * Parser constructor.
	 *
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(Cappuccino $cappuccino)
	{
		$this->cappuccino = $cappuccino;
	}

	/**
	 * Gets the variable name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getVarName(): string
	{
		return sprintf('__internal_%s', hash('sha256', __METHOD__ . $this->stream->getSourceContext()->getCode() . $this->varNameSalt++));
	}

	/**
	 * Parse.
	 *
	 * @param TokenStream   $stream
	 * @param callable|null $test
	 * @param bool          $dropNeedle
	 *
	 * @return ModuleNode
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse(TokenStream $stream, $test = null, bool $dropNeedle = false): ModuleNode
	{
		$vars = get_object_vars($this);
		unset($vars['stack'], $vars['cappuccino'], $vars['handlers'], $vars['visitors'], $vars['expressionParser'], $vars['reservedMacroNames']);
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
		$this->varNameSalt = 0;

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

		/** @var AbstractExpression|null $parent */
		$parent = $this->parent;

		$node = new ModuleNode(new BodyNode([$body]), $parent, new Node($this->blocks), new Node($this->macros), new Node($this->traits), $this->embeddedTemplates, $stream->getSourceContext());
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
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function subparse($test, bool $dropNeedle = false): Node
	{
		$lineNumber = $this->getCurrentToken()->getLine();
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

						return new Node($rv, [], $lineNumber);
					}

					if (!isset($this->handlers[$token->getValue()]))
					{
						if ($test !== null)
						{
							$e = new SyntaxError(sprintf('Unexpected "%s" tag', $token->getValue()), $token->getLine(), $this->stream->getSourceContext());

							if (is_array($test) && isset($test[0]) && $test[0] instanceof TokenParserInterface)
								$e->appendMessage(sprintf(' (expecting closing tag for the "%s" tag defined near line %s).', $test[0]->getTag(), $lineNumber));
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

		return new Node($rv, [], $lineNumber);
	}

	/**
	 * Gets the block stack.
	 *
	 * @return string[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getBlockStack(): array
	{
		return $this->blockStack;
	}

	/**
	 * Peeks the block stack.
	 *
	 * @return string|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function peekBlockStack(): ?string
	{
		return isset($this->blockStack[count($this->blockStack) - 1]) ? $this->blockStack[count($this->blockStack) - 1] : null;
	}

	/**
	 * Pops the block stack.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function popBlockStack(): void
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
	public function pushBlockStack($name): void
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
	public function hasBlock(string $name): bool
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
	public function getBlock(string $name): Node
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
	public function setBlock(string $name, BlockNode $value): void
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
	public function hasMacro(string $name): bool
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
	public function setMacro(string $name, MacroNode $node): void
	{
		$this->macros[$name] = $node;
	}

	/**
	 * Adds a trait.
	 *
	 * @param Node $trait
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addTrait(Node $trait): void
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
	public function hasTraits(): bool
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
	public function embedTemplate(ModuleNode $template)
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
	public function addImportedSymbol(string $type, string $alias, string $name = null, ?AbstractExpression $node = null): void
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
	public function getImportedSymbol(string $type, string $alias): ?array
	{
		return $this->importedSymbols[0][$type][$alias] ?? ($this->importedSymbols[count($this->importedSymbols) - 1][$type][$alias] ?? null);
	}

	/**
	 * Returns TRUE if this is the main scope.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isMainScope(): bool
	{
		return count($this->importedSymbols) === 1;
	}

	/**
	 * Pushes the local scope.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function pushLocalScope(): void
	{
		array_unshift($this->importedSymbols, []);
	}

	/**
	 * Pops the local scope.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function popLocalScope(): void
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
	public function getExpressionParser(): ExpressionParser
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
	public function getParent(): ?Node
	{
		return $this->parent;
	}

	/**
	 * Sets the parent {@see AbstractExpression}.
	 *
	 * @param Node|null $parent
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setParent(?Node $parent): void
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
	public function getStream(): TokenStream
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
	public function getCurrentToken(): Token
	{
		return $this->stream->getCurrent();
	}

	/**
	 * Filters body nodes.
	 *
	 * @param Node $node
	 * @param bool $nested
	 *
	 * @return Node|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function filterBodyNodes(Node $node, bool $nested = false): ?Node
	{
		if (($node instanceof TextNode && !ctype_space($node->getAttribute('data'))) || (!$node instanceof TextNode && !$node instanceof BlockReferenceNode && $node instanceof NodeOutputInterface))
		{
			if (strpos((string)$node, chr(0xEF) . chr(0xBB) . chr(0xBF)) !== false)
			{
				$t = substr($node->getAttribute('data'), 3);

				if ($t === '' || ctype_space($t))
					return null;
			}

			throw new SyntaxError('A template that extends another one cannot include content outside Cappuccino blocks. Did you forget to put the content inside a {% block %} tag?', $node->getTemplateLine(), $this->stream->getSourceContext());
		}

		if ($node instanceof NodeCaptureInterface)
			return $node;

		if ($nested && $node instanceof BlockReferenceNode)
			throw new SyntaxError('A block definition cannot be nested under non-capturing nodes.', $node->getTemplateLine(), $this->stream->getSourceContext());

		if ($node instanceof NodeOutputInterface)
			return null;

		$nested = $nested || get_class($node) !== Node::class;

		foreach ($node as $k => $n)
			if ($n !== null && $this->filterBodyNodes($n, $nested) === null)
				$node->removeNode($k);

		return $node;
	}

}
