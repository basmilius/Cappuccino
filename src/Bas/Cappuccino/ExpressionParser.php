<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\Expression\AbstractExpression;
use Bas\Cappuccino\Node\Expression\ArrayExpression;
use Bas\Cappuccino\Node\Expression\AssignNameExpression;
use Bas\Cappuccino\Node\Expression\Binary\AbstractBinary;
use Bas\Cappuccino\Node\Expression\Binary\ConcatBinary;
use Bas\Cappuccino\Node\Expression\BlockReferenceExpression;
use Bas\Cappuccino\Node\Expression\ConditionalExpression;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Expression\GetAttrExpression;
use Bas\Cappuccino\Node\Expression\MethodCallExpression;
use Bas\Cappuccino\Node\Expression\NameExpression;
use Bas\Cappuccino\Node\Expression\ParentExpression;
use Bas\Cappuccino\Node\Expression\Unary\AbstractUnary;
use Bas\Cappuccino\Node\Expression\Unary\NegUnary;
use Bas\Cappuccino\Node\Expression\Unary\NotUnary;
use Bas\Cappuccino\Node\Expression\Unary\PosUnary;
use Bas\Cappuccino\Node\Node;
use ReflectionClass;

/**
 * Class ExpressionParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @version 1.0.0
 */
class ExpressionParser
{

	public const OPERATOR_LEFT = 1;
	public const OPERATOR_RIGHT = 2;

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @var Cappuccino
	 */
	private $cappuccino;

	/**
	 * @var AbstractUnary[]
	 */
	private $unaryOperators;

	/**
	 * @var AbstractBinary[]
	 */
	private $binaryOperators;

	/**
	 * ExpressionParser constructor.
	 *
	 * @param Parser     $parser
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Parser $parser, Cappuccino $cappuccino)
	{
		$this->parser = $parser;
		$this->cappuccino = $cappuccino;
		$this->unaryOperators = $cappuccino->getUnaryOperators();
		$this->binaryOperators = $cappuccino->getBinaryOperators();
	}

	/**
	 * Parses the expression.
	 *
	 * @param int $precedence
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseExpression (int $precedence = 0): AbstractExpression
	{
		$expr = $this->getPrimary();
		$token = $this->parser->getCurrentToken();

		while ($this->isBinary($token) && $this->binaryOperators[$token->getValue()]['precedence'] >= $precedence)
		{
			$op = $this->binaryOperators[$token->getValue()];
			$this->parser->getStream()->next();

			if ('is not' === $token->getValue())
			{
				$expr = $this->parseNotTestExpression($expr);
			}
			else if ('is' === $token->getValue())
			{
				$expr = $this->parseTestExpression($expr);
			}
			else if (isset($op['callable']))
			{
				$expr = $op['callable']($this->parser, $expr);
			}
			else
			{
				$expr1 = $this->parseExpression(self::OPERATOR_LEFT === $op['associativity'] ? $op['precedence'] + 1 : $op['precedence']);
				$class = $op['class'];
				$expr = new $class($expr, $expr1, $token->getLine());
			}

			$token = $this->parser->getCurrentToken();
		}

		if ($precedence === 0)
		{
			return $this->parseConditionalExpression($expr);
		}

		return $expr;
	}

	/**
	 * Gets the primary expression.
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getPrimary (): AbstractExpression
	{
		$token = $this->parser->getCurrentToken();

		if ($this->isUnary($token))
		{
			$operator = $this->unaryOperators[$token->getValue()];
			$this->parser->getStream()->next();
			$expr = $this->parseExpression($operator['precedence']);
			$class = $operator['class'];

			return $this->parsePostfixExpression(new $class($expr, $token->getLine()));
		}
		else if ($token->test(Token::PUNCTUATION_TYPE, '('))
		{
			$this->parser->getStream()->next();
			$expr = $this->parseExpression();
			$this->parser->getStream()->expect(Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');

			return $this->parsePostfixExpression($expr);
		}

		return $this->parsePrimaryExpression();
	}

	/**
	 * Parses a ConditionalExpression.
	 *
	 * @param AbstractExpression $expr
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function parseConditionalExpression (AbstractExpression $expr): AbstractExpression
	{
		while ($this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, '?'))
		{
			if (!$this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ':'))
			{
				$expr2 = $this->parseExpression();
				if ($this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ':'))
				{
					$expr3 = $this->parseExpression();
				}
				else
				{
					$expr3 = new ConstantExpression('', $this->parser->getCurrentToken()->getLine());
				}
			}
			else
			{
				$expr2 = $expr;
				$expr3 = $this->parseExpression();
			}

			$expr = new ConditionalExpression($expr, $expr2, $expr3, $this->parser->getCurrentToken()->getLine());
		}

		return $expr;
	}

	/**
	 * Checks if the token is Unary.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function isUnary (Token $token): bool
	{
		return $token->test(Token::OPERATOR_TYPE) && isset($this->unaryOperators[$token->getValue()]);
	}

	/**
	 * Checks if the token is Binary.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function isBinary (Token $token): bool
	{
		return $token->test(Token::OPERATOR_TYPE) && isset($this->binaryOperators[$token->getValue()]);
	}

	/**
	 * Parses the Primary Expression.
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parsePrimaryExpression (): AbstractExpression
	{
		$node = null;
		$token = $this->parser->getCurrentToken();

		switch ($token->getType())
		{
			case Token::NAME_TYPE:
				$this->parser->getStream()->next();
				switch ($token->getValue())
				{
					case 'true':
					case 'TRUE':
						$node = new ConstantExpression(true, $token->getLine());
						break;

					case 'false':
					case 'FALSE':
						$node = new ConstantExpression(false, $token->getLine());
						break;

					case 'none':
					case 'NONE':
					case 'null':
					case 'NULL':
						$node = new ConstantExpression(null, $token->getLine());
						break;

					default:
						if ('(' === $this->parser->getCurrentToken()->getValue())
						{
							$node = $this->getFunctionNode($token->getValue(), $token->getLine());
						}
						else
						{
							$node = new NameExpression($token->getValue(), $token->getLine());
						}
				}
				break;

			case Token::NUMBER_TYPE:
				$this->parser->getStream()->next();
				$node = new ConstantExpression($token->getValue(), $token->getLine());
				break;

			case Token::STRING_TYPE:
			case Token::INTERPOLATION_START_TYPE:
				$node = $this->parseStringExpression();
				break;

			case Token::OPERATOR_TYPE:
				if (preg_match(Lexer::REGEX_NAME, $token->getValue(), $matches) && $matches[0] == $token->getValue())
				{
					// in this context, string operators are variable names
					$this->parser->getStream()->next();
					$node = new NameExpression($token->getValue(), $token->getLine());
				}
				else if (isset($this->unaryOperators[$token->getValue()]))
				{
					$class = $this->unaryOperators[$token->getValue()]['class'];

					$ref = new ReflectionClass($class);
					$negClass = NegUnary::class;
					$posClass = PosUnary::class;
					if (!(in_array($ref->getName(), [$negClass, $posClass]) || $ref->isSubclassOf($negClass) || $ref->isSubclassOf($posClass)))
					{
						throw new SyntaxError(sprintf('Unexpected unary operator "%s".', $token->getValue()), $token->getLine(), $this->parser->getStream()->getSourceContext());
					}

					$this->parser->getStream()->next();
					$expr = $this->parsePrimaryExpression();

					$node = new $class($expr, $token->getLine());
				}
				break;

			default:
				if ($token->test(Token::PUNCTUATION_TYPE, '['))
				{
					$node = $this->parseArrayExpression();
				}
				else if ($token->test(Token::PUNCTUATION_TYPE, '{'))
				{
					$node = $this->parseHashExpression();
				}
				else
				{
					throw new SyntaxError(sprintf('Unexpected token "%s" of value "%s".', Token::typeToEnglish($token->getType()), $token->getValue()), $token->getLine(), $this->parser->getStream()->getSourceContext());
				}
		}

		return $this->parsePostfixExpression($node);
	}

	/**
	 * Parses a String Expression.
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseStringExpression (): AbstractExpression
	{
		$stream = $this->parser->getStream();

		$nodes = [];
		$nextCanBeString = true;

		while (true)
		{
			if ($nextCanBeString && $token = $stream->nextIf(Token::STRING_TYPE))
			{
				$nodes[] = new ConstantExpression($token->getValue(), $token->getLine());
				$nextCanBeString = false;
			}
			else if ($stream->nextIf(Token::INTERPOLATION_START_TYPE))
			{
				$nodes[] = $this->parseExpression();
				$stream->expect(Token::INTERPOLATION_END_TYPE);
				$nextCanBeString = true;
			}
			else
			{
				break;
			}
		}

		$expr = array_shift($nodes);

		foreach ($nodes as $node)
			$expr = new ConcatBinary($expr, $node, $node->getTemplateLine());

		return $expr;
	}

	/**
	 * Parses an Array Expression.
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseArrayExpression (): AbstractExpression
	{
		$stream = $this->parser->getStream();
		$stream->expect(Token::PUNCTUATION_TYPE, '[', 'An array element was expected');

		$node = new ArrayExpression([], $stream->getCurrent()->getLine());
		$first = true;

		while (!$stream->test(Token::PUNCTUATION_TYPE, ']'))
		{
			if (!$first)
			{
				$stream->expect(Token::PUNCTUATION_TYPE, ',', 'An array element must be followed by a comma');

				if ($stream->test(Token::PUNCTUATION_TYPE, ']'))
					break;
			}

			$first = false;
			$node->addElement($this->parseExpression());
		}

		$stream->expect(Token::PUNCTUATION_TYPE, ']', 'An opened array is not properly closed');

		return $node;
	}

	/**
	 * Parses a Hash Expression.
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseHashExpression (): AbstractExpression
	{
		$stream = $this->parser->getStream();
		$stream->expect(Token::PUNCTUATION_TYPE, '{', 'A hash element was expected');

		$node = new ArrayExpression([], $stream->getCurrent()->getLine());
		$first = true;

		while (!$stream->test(Token::PUNCTUATION_TYPE, '}'))
		{
			if (!$first)
			{
				$stream->expect(Token::PUNCTUATION_TYPE, ',', 'A hash value must be followed by a comma');

				if ($stream->test(Token::PUNCTUATION_TYPE, '}'))
					break;
			}

			$first = false;

			if (($token = $stream->nextIf(Token::STRING_TYPE)) || ($token = $stream->nextIf(Token::NAME_TYPE)) || $token = $stream->nextIf(Token::NUMBER_TYPE))
			{
				$key = new ConstantExpression($token->getValue(), $token->getLine());
			}
			else if ($stream->test(Token::PUNCTUATION_TYPE, '('))
			{
				$key = $this->parseExpression();
			}
			else
			{
				$current = $stream->getCurrent();

				throw new SyntaxError(sprintf('A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "%s" of value "%s".', Token::typeToEnglish($current->getType()), $current->getValue()), $current->getLine(), $stream->getSourceContext());
			}

			$stream->expect(Token::PUNCTUATION_TYPE, ':', 'A hash key must be followed by a colon (:)');
			$value = $this->parseExpression();

			$node->addElement($value, $key);
		}

		$stream->expect(Token::PUNCTUATION_TYPE, '}', 'An opened hash is not properly closed');

		return $node;
	}

	/**
	 * Parses a Postfix Expression.
	 *
	 * @param AbstractExpression $node
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parsePostfixExpression (AbstractExpression $node): AbstractExpression
	{
		while (true)
		{
			$token = $this->parser->getCurrentToken();

			if ($token->getType() == Token::PUNCTUATION_TYPE)
			{
				if ('.' == $token->getValue() || '[' == $token->getValue())
				{
					$node = $this->parseSubscriptExpression($node);
				}
				else if ('|' == $token->getValue())
				{
					$node = $this->parseFilterExpression($node);
				}
				else
				{
					break;
				}
			}
			else
			{
				break;
			}
		}

		return $node;
	}

	/**
	 * Gets a Function Node.
	 *
	 * @param string $name
	 * @param int    $line
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctionNode (string $name, int $line): AbstractExpression
	{
		switch ($name)
		{
			case 'parent':
				$this->parseArguments();

				if (!count($this->parser->getBlockStack()))
					throw new SyntaxError('Calling "parent" outside a block is forbidden.', $line, $this->parser->getStream()->getSourceContext());

				if (!$this->parser->getParent() && !$this->parser->hasTraits())
					throw new SyntaxError('Calling "parent" on a template that does not extend nor "use" another template is forbidden.', $line, $this->parser->getStream()->getSourceContext());

				return new ParentExpression($this->parser->peekBlockStack(), $line);
			case 'block':
				$args = $this->parseArguments();

				if (count($args) < 1)
					throw new SyntaxError('The "block" function takes one argument (the block name).', $line, $this->parser->getStream()->getSourceContext());

				return new BlockReferenceExpression($args->getNode(0), count($args) > 1 ? $args->getNode(1) : null, $line);
			case 'attribute':
				$args = $this->parseArguments();

				if (count($args) < 2)
					throw new SyntaxError('The "attribute" function takes at least two arguments (the variable and the attributes).', $line, $this->parser->getStream()->getSourceContext());

				/** @var AbstractExpression $node0 */
				$node0 = $args->getNode(0);

				/** @var AbstractExpression $node1 */
				$node1 = $args->getNode(0);

				return new GetAttrExpression($node0, $node1, count($args) > 2 ? $args->getNode(2) : null, Template::ANY_CALL, $line);
			default:
				if (($alias = $this->parser->getImportedSymbol('function', $name)) !== null)
				{
					$arguments = new ArrayExpression([], $line);

					foreach ($this->parseArguments() as $n)
						$arguments->addElement($n);

					$node = new MethodCallExpression($alias['node'], $alias['name'], $arguments, $line);
					$node->setAttribute('safe', true);

					return $node;
				}

				$args = $this->parseArguments(true);
				$class = $this->getFunctionNodeClass($name, $line);

				return new $class($name, $args, $line);
		}
	}

	/**
	 * Parses a Subscript Expression.
	 *
	 * @param AbstractExpression $node
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseSubscriptExpression (AbstractExpression $node): AbstractExpression
	{
		$stream = $this->parser->getStream();
		$token = $stream->next();
		$lineno = $token->getLine();
		$arguments = new ArrayExpression([], $lineno);
		$type = Template::ANY_CALL;

		if ($token->getValue() === '.')
		{
			$token = $stream->next();
			if ($token->getType() == Token::NAME_TYPE || $token->getType() == Token::NUMBER_TYPE || ($token->getType() == Token::OPERATOR_TYPE && preg_match(Lexer::REGEX_NAME, $token->getValue())))
			{
				$arg = new ConstantExpression($token->getValue(), $lineno);

				if ($stream->test(Token::PUNCTUATION_TYPE, '('))
				{
					$type = Template::METHOD_CALL;

					foreach ($this->parseArguments() as $n)
						$arguments->addElement($n);
				}
			}
			else
			{
				throw new SyntaxError('Expected name or number.', $lineno, $stream->getSourceContext());
			}

			if ($node instanceof NameExpression && null !== $this->parser->getImportedSymbol('template', $node->getAttribute('name')))
			{
				if (!$arg instanceof ConstantExpression)
					throw new SyntaxError(sprintf('Dynamic macro names are not supported (called on "%s").', $node->getAttribute('name')), $token->getLine(), $stream->getSourceContext());

				$name = $arg->getAttribute('value');

				$node = new MethodCallExpression($node, 'macro_' . $name, $arguments, $lineno);
				$node->setAttribute('safe', true);

				return $node;
			}
		}
		else
		{
			$type = Template::ARRAY_CALL;

			$slice = false;

			if ($stream->test(Token::PUNCTUATION_TYPE, ':'))
			{
				$slice = true;
				$arg = new ConstantExpression(0, $token->getLine());
			}
			else
			{
				$arg = $this->parseExpression();
			}

			if ($stream->nextIf(Token::PUNCTUATION_TYPE, ':'))
				$slice = true;

			if ($slice)
			{
				if ($stream->test(Token::PUNCTUATION_TYPE, ']'))
					$length = new ConstantExpression(null, $token->getLine());
				else
					$length = $this->parseExpression();

				$class = $this->getFilterNodeClass('slice', $token->getLine());
				$arguments = new Node([$arg, $length]);
				$filter = new $class($node, new ConstantExpression('slice', $token->getLine()), $arguments, $token->getLine());

				$stream->expect(Token::PUNCTUATION_TYPE, ']');

				return $filter;
			}

			$stream->expect(Token::PUNCTUATION_TYPE, ']');
		}

		return new GetAttrExpression($node, $arg, $arguments, $type, $lineno);
	}

	/**
	 * Parses a Filter Expression.
	 *
	 * @param AbstractExpression $node
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseFilterExpression (AbstractExpression $node): AbstractExpression
	{
		$this->parser->getStream()->next();

		return $this->parseFilterExpressionRaw($node);
	}

	/**
	 * Parses a raw Filter Expression.
	 *
	 * @param AbstractExpression $node
	 * @param string|null        $tag
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseFilterExpressionRaw (AbstractExpression $node, ?string $tag = null): AbstractExpression
	{
		while (true)
		{
			$token = $this->parser->getStream()->expect(Token::NAME_TYPE);
			$name = new ConstantExpression($token->getValue(), $token->getLine());

			if (!$this->parser->getStream()->test(Token::PUNCTUATION_TYPE, '('))
				$arguments = new Node();
			else
				$arguments = $this->parseArguments(true);

			$class = $this->getFilterNodeClass($name->getAttribute('value'), $token->getLine());
			$node = new $class($node, $name, $arguments, $token->getLine(), $tag);

			if (!$this->parser->getStream()->test(Token::PUNCTUATION_TYPE, '|'))
				break;

			$this->parser->getStream()->next();
		}

		return $node;
	}

	/**
	 * Parses arguments.
	 *
	 * @param bool $namedArguments
	 * @param bool $definition
	 *
	 * @return Node
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseArguments (bool $namedArguments = false, bool $definition = false): Node
	{
		$args = [];
		$stream = $this->parser->getStream();
		$stream->expect(Token::PUNCTUATION_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');

		while (!$stream->test(Token::PUNCTUATION_TYPE, ')'))
		{
			if (!empty($args))
				$stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');

			if ($definition)
			{
				$token = $stream->expect(Token::NAME_TYPE, null, 'An argument must be a name');
				$value = new NameExpression($token->getValue(), $this->parser->getCurrentToken()->getLine());
			}
			else
			{
				$value = $this->parseExpression();
			}

			$name = null;

			if ($namedArguments && $token = $stream->nextIf(Token::OPERATOR_TYPE, '='))
			{
				if (!$value instanceof NameExpression)
					throw new SyntaxError(sprintf('A parameter name must be a string, "%s" given.', get_class($value)), $token->getLine(), $stream->getSourceContext());

				$name = $value->getAttribute('name');

				if ($definition)
				{
					$value = $this->parsePrimaryExpression();

					if (!$this->checkConstantExpression($value))
						throw new SyntaxError(sprintf('A default value for an argument must be a constant (a boolean, a string, a number, or an array).'), $token->getLine(), $stream->getSourceContext());
				}
				else
				{
					$value = $this->parseExpression();
				}
			}

			if ($definition)
			{
				if (null === $name)
				{
					$name = $value->getAttribute('name');
					$value = new ConstantExpression(null, $this->parser->getCurrentToken()->getLine());
				}

				$args[$name] = $value;
			}
			else
			{
				if ($name === null)
					$args[] = $value;
				else
					$args[$name] = $value;
			}
		}

		$stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');

		return new Node($args);
	}

	/**
	 * Parses an Assignment Expression.
	 *
	 * @return Node
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseAssignmentExpression (): Node
	{
		$stream = $this->parser->getStream();
		$targets = [];

		while (true)
		{
			$token = $stream->expect(Token::NAME_TYPE, null, 'Only variables can be assigned to');
			$value = $token->getValue();

			if (in_array(strtolower($value), ['true', 'false', 'none', 'null']))
				throw new SyntaxError(sprintf('You cannot assign a value to "%s".', $value), $token->getLine(), $stream->getSourceContext());

			$targets[] = new AssignNameExpression($value, $token->getLine());

			if (!$stream->nextIf(Token::PUNCTUATION_TYPE, ','))
				break;
		}

		return new Node($targets);
	}

	/**
	 * Parses multi target expression.
	 *
	 * @return Node
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parseMultitargetExpression (): Node
	{
		$targets = [];

		while (true)
		{
			$targets[] = $this->parseExpression();

			if (!$this->parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ','))
				break;
		}

		return new Node($targets);
	}

	/**
	 * Parses not test expression.
	 *
	 * @param Node $node
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function parseNotTestExpression (Node $node): AbstractExpression
	{
		return new NotUnary($this->parseTestExpression($node), $this->parser->getCurrentToken()->getLine());
	}

	/**
	 * Parses a Test Expression.
	 *
	 * @param Node $node
	 *
	 * @return AbstractExpression
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function parseTestExpression (Node $node): AbstractExpression
	{
		$stream = $this->parser->getStream();
		[$name, $test] = $this->getTest($node->getTemplateLine());

		$class = $this->getTestNodeClass($test);
		$arguments = null;

		if ($stream->test(Token::PUNCTUATION_TYPE, '('))
			$arguments = $this->parser->getExpressionParser()->parseArguments(true);

		return new $class($node, $name, $arguments, $this->parser->getCurrentToken()->getLine());
	}

	/**
	 * Gets a Test.
	 *
	 * @param int $line
	 *
	 * @return array
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getTest (int $line)
	{
		$stream = $this->parser->getStream();
		$name = $stream->expect(Token::NAME_TYPE)->getValue();

		if ($test = $this->cappuccino->getTest($name))
			return [$name, $test];

		if ($stream->test(Token::NAME_TYPE))
		{
			$name = $name . ' ' . $this->parser->getCurrentToken()->getValue();

			if ($test = $this->cappuccino->getTest($name))
			{
				$stream->next();

				return [$name, $test];
			}
		}

		$e = new SyntaxError(sprintf('Unknown "%s" test.', $name), $line, $stream->getSourceContext());
		$e->addSuggestions($name, array_keys($this->cappuccino->getTests()));

		throw $e;
	}

	/**
	 * Gets a SimpleTest class.
	 *
	 * @param SimpleTest $test
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getTestNodeClass (SimpleTest $test)
	{
		if ($test->isDeprecated())
		{
			$stream = $this->parser->getStream();
			$message = sprintf('Cappuccino Test "%s" is deprecated', $test->getName());

			if (!is_bool($test->getDeprecatedVersion()))
				$message .= sprintf(' since version %s', $test->getDeprecatedVersion());

			if ($test->getAlternative())
				$message .= sprintf('. Use "%s" instead', $test->getAlternative());

			$src = $stream->getSourceContext();
			$message .= sprintf(' in %s at line %d.', $src->getPath() ?: $src->getName(), $stream->getCurrent()->getLine());

			@trigger_error($message, E_USER_DEPRECATED);
		}

		return $test->getNodeClass();
	}

	/**
	 * Gets a SimpleFunction class.
	 *
	 * @param string $name
	 * @param int    $line
	 *
	 * @return mixed
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getFunctionNodeClass (string $name, int $line)
	{
		if (($function = $this->cappuccino->getFunction($name)) === false)
		{
			$e = new SyntaxError(sprintf('Unknown "%s" function.', $name), $line, $this->parser->getStream()->getSourceContext());
			$e->addSuggestions($name, array_keys($this->cappuccino->getFunctions()));

			throw $e;
		}

		if ($function->isDeprecated())
		{
			$message = sprintf('Cappuccino Function "%s" is deprecated', $function->getName());

			if (!is_bool($function->getDeprecatedVersion()))
				$message .= sprintf(' since version %s', $function->getDeprecatedVersion());

			if ($function->getAlternative())
				$message .= sprintf('. Use "%s" instead', $function->getAlternative());

			$src = $this->parser->getStream()->getSourceContext();
			$message .= sprintf(' in %s at line %d.', $src->getPath() ?: $src->getName(), $line);

			@trigger_error($message, E_USER_DEPRECATED);
		}

		return $function->getNodeClass();
	}

	/**
	 * Gets a SimpleFilter class.
	 *
	 * @param string $name
	 * @param int    $line
	 *
	 * @return mixed
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function getFilterNodeClass (string $name, int $line)
	{
		if (false === $filter = $this->cappuccino->getFilter($name))
		{
			$e = new SyntaxError(sprintf('Unknown "%s" filter.', $name), $line, $this->parser->getStream()->getSourceContext());
			$e->addSuggestions($name, array_keys($this->cappuccino->getFilters()));

			throw $e;
		}

		if ($filter->isDeprecated())
		{
			$message = sprintf('Cappuccino Filter "%s" is deprecated', $filter->getName());
			if (!is_bool($filter->getDeprecatedVersion()))
			{
				$message .= sprintf(' since version %s', $filter->getDeprecatedVersion());
			}
			if ($filter->getAlternative())
			{
				$message .= sprintf('. Use "%s" instead', $filter->getAlternative());
			}
			$src = $this->parser->getStream()->getSourceContext();
			$message .= sprintf(' in %s at line %d.', $src->getPath() ?: $src->getName(), $line);

			@trigger_error($message, E_USER_DEPRECATED);
		}

		return $filter->getNodeClass();
	}

	/**
	 * Checks constant expression.
	 *
	 * @param Node $node
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function checkConstantExpression (Node $node): bool
	{
		if (!($node instanceof ConstantExpression || $node instanceof ArrayExpression || $node instanceof NegUnary || $node instanceof PosUnary))
			return false;

		foreach ($node as $n)
			if (!$this->checkConstantExpression($n))
				return false;

		return true;
	}

}
