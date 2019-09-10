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

use Cappuccino\Node\Expression\AssignNameExpression;
use Cappuccino\Node\ForNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class ForTokenParser
 *
 * {% for user in users %}
 *     {{ user.name }}
 * {% endfor %}
 *
 * {% for notice in notices %}
 *     {{ notice.message }}
 * {% else %}
 *     There are no notices!
 * {% endfor %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
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
	public function parse(Token $token): Node
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$targets = $this->parser->getExpressionParser()->parseAssignmentExpression();
		$stream->expect(Token::OPERATOR_TYPE, 'in');
		$seq = $this->parser->getExpressionParser()->parseExpression();

		$stream->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideForFork']);

		if ($stream->next()->getValue() === 'else')
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

		return new ForNode($keyTarget, $valueTarget, $seq, $body, $else, $lineno, $this->getTag());
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
	public function decideForFork(Token $token): bool
	{
		return $token->test(['else', 'endfor']);
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
	public function decideForEnd(Token $token): bool
	{
		return $token->test('endfor');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'for';
	}

}
