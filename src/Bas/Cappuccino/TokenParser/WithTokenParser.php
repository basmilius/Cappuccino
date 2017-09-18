<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\WithNode;
use Bas\Cappuccino\Token;

/**
 * Class WithTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 1.0.0
 */
final class WithTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token) : Node
	{
		$stream = $this->parser->getStream();

		$variables = null;
		$only = false;

		if (!$stream->test(Token::BLOCK_END_TYPE))
		{
			$variables = $this->parser->getExpressionParser()->parseExpression();
			$only = $stream->nextIf(Token::NAME_TYPE, 'only');
		}

		$stream->expect(Token::BLOCK_END_TYPE);

		$body = $this->parser->subparse([$this, 'decideWithEnd'], true);

		$stream->expect(Token::BLOCK_END_TYPE);

		return new WithNode($body, $variables, $only, $token->getLine(), $this->getTag());
	}

	/**
	 * Decide if the with should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideWithEnd (Token $token) : bool
	{
		return $token->test('endwith');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag () : string
	{
		return 'with';
	}

}
