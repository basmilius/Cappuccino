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

namespace Cappuccino\TokenParser;

use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\IfNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class IfTokenParser
 *
 * {% if users %}
 *     There are users!
 * {% endif %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class IfTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse(Token $token): Node
	{
		$lineno = $token->getLine();
		$expr = $this->parser->getExpressionParser()->parseExpression();
		$stream = $this->parser->getStream();
		$stream->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideIfFork']);
		$tests = [$expr, $body];
		$else = null;
		$end = false;

		while (!$end)
		{
			switch ($stream->next()->getValue())
			{
				case 'else':
					$stream->expect(Token::BLOCK_END_TYPE);
					$else = $this->parser->subparse([$this, 'decideIfEnd']);
					break;

				case 'elseif':
					$expr = $this->parser->getExpressionParser()->parseExpression();
					$stream->expect(Token::BLOCK_END_TYPE);
					$body = $this->parser->subparse([$this, 'decideIfFork']);
					$tests[] = $expr;
					$tests[] = $body;
					break;

				case 'endif':
					$end = true;
					break;

				default:
					throw new SyntaxError(sprintf('Unexpected end of template. Cappuccino was looking for the following tags "else", "elseif", or "endif" to close the "if" block started at line %d).', $lineno), $stream->getCurrent()->getLine(), $stream->getSourceContext());
			}
		}

		$stream->expect(Token::BLOCK_END_TYPE);

		return new IfNode(new Node($tests), $else, $lineno, $this->getTag());
	}

	/**
	 * Returns TRUE if more lexing is needed for an else block.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function decideIfFork(Token $token): bool
	{
		return $token->test(['elseif', 'else', 'endif']);
	}

	/**
	 * Returns TRUE if the block should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function decideIfEnd(Token $token): bool
	{
		return $token->test(['endif']);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'if';
	}

}
