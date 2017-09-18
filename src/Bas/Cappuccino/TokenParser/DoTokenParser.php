<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Node\DoNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class DoTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 1.0.0
 */
final class DoTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token) : Node
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		return new DoNode($expr, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag () : string
	{
		return 'do';
	}

}
