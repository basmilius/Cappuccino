<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class ExtendsTokenParser
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
final class ExtendsTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : ?Node
	{
		$stream = $this->parser->getStream();

		if (!$this->parser->isMainScope())
			throw new SyntaxError('Cannot extend from a block.', $token->getLine(), $stream->getSourceContext());

		if (null !== $this->parser->getParent())
			throw new SyntaxError('Multiple extends tags are forbidden.', $token->getLine(), $stream->getSourceContext());

		$this->parser->setParent($this->parser->getExpressionParser()->parseExpression());

		$stream->expect(Token::BLOCK_END_TYPE);

		return null;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getTag () : string
	{
		return 'extends';
	}

}
