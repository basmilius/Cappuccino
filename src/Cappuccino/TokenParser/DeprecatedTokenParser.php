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

use Cappuccino\Node\DeprecatedNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class DeprecatedTokenParser
 *
 * {% deprecated "The skeleton.cappy template is deprecated, use layout.cappy instead." %}
 * {% extends "layout.cappy" %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
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
	public function parse(Token $token): Node
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		return new DeprecatedNode($expr, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.2.0
	 */
	public function getTag(): string
	{
		return 'deprecated';
	}

}
