<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\SetNode;
use Bas\Cappuccino\Token;

/**
 * Class SetTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @since 1.0.0
 */
final class SetTokenParser extends AbstractTokenParser
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
		$names = $this->parser->getExpressionParser()->parseAssignmentExpression();

		$capture = false;

		if ($stream->nextIf(Token::OPERATOR_TYPE, '='))
		{
			$values = $this->parser->getExpressionParser()->parseMultitargetExpression();

			$stream->expect(Token::BLOCK_END_TYPE);

			if (count($names) !== count($values))
				throw new SyntaxError('When using set, you must have the same number of variables and assignments.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
		}
		else
		{
			$capture = true;

			if (count($names) > 1)
				throw new SyntaxError('When using set with a block, you cannot have a multi-target.', $stream->getCurrent()->getLine(), $stream->getSourceContext());

			$stream->expect(Token::BLOCK_END_TYPE);

			$values = $this->parser->subparse([$this, 'decideBlockEnd'], true);

			$stream->expect(Token::BLOCK_END_TYPE);
		}

		return new SetNode($capture, $names, $values, $lineno, $this->getTag());
	}

	/**
	 * Decide if the block should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideBlockEnd (Token $token): bool
	{
		return $token->test('endset');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'set';
	}

}
