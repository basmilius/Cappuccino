<?php
/**
 * This file is part of the Bas\Cappuccino package.
 *
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class ExtendsTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @since 1.0.0
 */
final class ExtendsTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): ?Node
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
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'extends';
	}

}
