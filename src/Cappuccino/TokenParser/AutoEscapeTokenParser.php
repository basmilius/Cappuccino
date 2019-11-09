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
use Cappuccino\Node\AutoEscapeNode;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class AutoEscapeTokenParser
 *
 * {% autoescape %}
 *     Everything will be automatically escaped in this block using the HTML strategy.
 * {% endautoescape %}
 *
 * {% autoescape "js" %}
 *     Everything will be automatically escaped in this block using the JS strategy.
 * {% endautoescape %}
 *
 * {% autoescape false %}
 *     Everything will be outputted as is in this block.
 * {% endautoescape %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class AutoEscapeTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse(Token $token): Node
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();

		if ($stream->test(Token::BLOCK_END_TYPE))
		{
			$value = 'html';
		}
		else
		{
			$expr = $this->parser->getExpressionParser()->parseExpression();
			if (!$expr instanceof ConstantExpression)
			{
				throw new SyntaxError('An escaping strategy must be a string or false.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
			}
			$value = $expr->getAttribute('value');
		}

		$stream->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
		$stream->expect(Token::BLOCK_END_TYPE);

		return new AutoEscapeNode($value, $body, $lineno, $this->getTag());
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
	public function decideBlockEnd(Token $token): bool
	{
		return $token->test('endautoescape');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'autoescape';
	}

}
