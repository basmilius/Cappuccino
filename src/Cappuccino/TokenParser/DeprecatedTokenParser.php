<?php
declare(strict_types=1);

namespace Cappuccino\TokenParser;

use Cappuccino\Node\DeprecatedNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class DeprecatedTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.2.0
 */
final class DeprecatedTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function parse(Token $token): ?Node
	{
		$expression = $this->parser->getExpressionParser()->parseExpression();

		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		return new DeprecatedNode($expression, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public final function getTag(): string
	{
		return 'deprecated';
	}

}
