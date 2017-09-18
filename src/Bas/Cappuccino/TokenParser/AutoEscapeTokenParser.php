<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\AutoEscapeNode;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class AutoEscapeTokenParser
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
final class AutoEscapeTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : Node
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
				throw new SyntaxError('An escaping strategy must be a string or false.', $stream->getCurrent()->getLine(), $stream->getSourceContext());

			$value = $expr->getAttribute('value');
		}

		$stream->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
		$stream->expect(Token::BLOCK_END_TYPE);

		return new AutoEscapeNode($value, $body, $lineno, $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function decideBlockEnd (Token $token) : bool
	{
		return $token->test('endautoescape');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getTag () : string
	{
		return 'autoescape';
	}

}
