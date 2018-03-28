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
use Cappuccino\Node\Node;
use Cappuccino\Node\SetNode;
use Cappuccino\Token;

/**
 * Class SetTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class SetTokenParser extends AbstractTokenParser
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
		$names = $this->parser->getExpressionParser()->parseAssignmentExpression();

		$capture = false;

		if ($stream->nextIf(/*Token::OPERATOR_TYPE*/
			8, '='))
		{
			$values = $this->parser->getExpressionParser()->parseMultitargetExpression();

			$stream->expect(/*Token::BLOCK_END_TYPE*/
				3);

			if (count($names) !== count($values))
				throw new SyntaxError('When using set, you must have the same number of variables and assignments.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
		}
		else
		{
			$capture = true;

			if (count($names) > 1)
				throw new SyntaxError('When using set with a block, you cannot have a multi-target.', $stream->getCurrent()->getLine(), $stream->getSourceContext());

			$stream->expect(/*Token::BLOCK_END_TYPE*/
				3);

			$values = $this->parser->subparse([$this, 'decideBlockEnd'], true);

			$stream->expect(/*Token::BLOCK_END_TYPE*/
				3);
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
	public function decideBlockEnd(Token $token): bool
	{
		return $token->test('endset');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'set';
	}

}
