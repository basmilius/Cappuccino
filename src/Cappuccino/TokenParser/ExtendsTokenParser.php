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

use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class ExtendsTokenParser
 *
 * {% extends "skeleton.cappy" %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class ExtendsTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse(Token $token): Node
	{
		$stream = $this->parser->getStream();

		if ($this->parser->peekBlockStack())
			throw new SyntaxError('Cannot use "extend" in a block.', $token->getLine(), $stream->getSourceContext());
		else if (!$this->parser->isMainScope())
			throw new SyntaxError('Cannot use "extend" in a macro.', $token->getLine(), $stream->getSourceContext());

		if ($this->parser->getParent() !== null)
			throw new SyntaxError('Multiple extends tags are forbidden.', $token->getLine(), $stream->getSourceContext());

		$this->parser->setParent($this->parser->getExpressionParser()->parseExpression());
		$stream->expect(Token::BLOCK_END_TYPE);

		return new Node();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'extends';
	}

}
