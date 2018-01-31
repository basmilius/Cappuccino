<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cappuccino\TokenParser;

use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\Expression\AssignNameExpression;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\GetAttrExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\ForNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;
use Cappuccino\TokenStream;

/**
 * Class ForTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class ForTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): Node
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
		$stream->expect(Token::OPERATOR_TYPE, 'in');
		$seq = $this->parser->getExpressionParser()->parseExpression();

		$ifexpr = null;

		if ($stream->nextIf(Token::NAME_TYPE, 'if'))
			$ifexpr = $this->parser->getExpressionParser()->parseExpression();

		$stream->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideForFork']);

		if ($stream->next()->getValue() == 'else')
		{
			$stream->expect(Token::BLOCK_END_TYPE);
			$else = $this->parser->subparse([$this, 'decideForEnd'], true);
		}
		else
		{
			$else = null;
		}

		$stream->expect(Token::BLOCK_END_TYPE);

		if (count($targets) > 1)
		{
			$keyTarget = $targets->getNode(0);
			$keyTarget = new AssignNameExpression($keyTarget->getAttribute('name'), $keyTarget->getTemplateLine());
			$valueTarget = $targets->getNode(1);
			$valueTarget = new AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
		}
		else
		{
			$keyTarget = new AssignNameExpression('_key', $lineno);
			$valueTarget = $targets->getNode(0);
			$valueTarget = new AssignNameExpression($valueTarget->getAttribute('name'), $valueTarget->getTemplateLine());
		}

		if ($ifexpr)
		{
			$this->checkLoopUsageCondition($stream, $ifexpr);
			$this->checkLoopUsageBody($stream, $body);
		}

		return new ForNode($keyTarget, $valueTarget, $seq, $ifexpr, $body, $else, $lineno, $this->getTag());
	}

	/**
	 * Decide if a fork is needed.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideForFork (Token $token): bool
	{
		return $token->test(['else', 'endfor']);
	}

	/**
	 * Decide for end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideForEnd (Token $token): bool
	{
		return $token->test('endfor');
	}

	/**
	 * The loop variable cannot be used in the condition.
	 *
	 * @param TokenStream $stream
	 * @param Node        $node
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function checkLoopUsageCondition (TokenStream $stream, Node $node)
	{
		if ($node instanceof GetAttrExpression && $node->getNode('node') instanceof NameExpression && $node->getNode('node')->getAttribute('name') === 'loop')
			throw new SyntaxError('The "loop" variable cannot be used in a looping condition.', $node->getTemplateLine(), $stream->getSourceContext());

		foreach ($node as $n)
		{
			if (!$n)
				continue;

			$this->checkLoopUsageCondition($stream, $n);
		}
	}

	/**
	 * Check usage of non-defined loop-items. It does not catch all problems (for instance when a for is included into another or when the variable is used in an include).
	 *
	 * @param TokenStream $stream
	 * @param Node        $node
	 *
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function checkLoopUsageBody (TokenStream $stream, Node $node)
	{
		if ($node instanceof GetAttrExpression && $node->getNode('node') instanceof NameExpression && $node->getNode('node')->getAttribute('name') === 'loop')
		{
			$attribute = $node->getNode('attribute');

			if ($attribute instanceof ConstantExpression && in_array($attribute->getAttribute('value'), ['length', 'revindex0', 'revindex', 'last']))
				throw new SyntaxError(sprintf('The "loop.%s" variable is not defined when looping with a condition.', $attribute->getAttribute('value')), $node->getTemplateLine(), $stream->getSourceContext());
		}

		if ($node instanceof ForNode)
			return;

		foreach ($node as $n)
		{
			if (!$n)
				continue;

			$this->checkLoopUsageBody($stream, $n);
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'for';
	}

}
