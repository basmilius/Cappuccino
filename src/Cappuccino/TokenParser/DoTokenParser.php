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

use Cappuccino\Node\DoNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class DoTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class DoTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function parse(Token $token): Node
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		$this->parser->getStream()->expect(3); // Token::BLOCK_END_TYPE

		return new DoNode($expr, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public final function getTag(): string
	{
		return 'do';
	}

}
