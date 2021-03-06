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
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class UseTokenParser
 *
 * {# Makes blocks from another cappy-file available #}
 *
 * {% extends "skeleton.cappy" %}
 * {% use "default-blocks.cappy" %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class UseTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse(Token $token): Node
	{
		$template = $this->parser->getExpressionParser()->parseExpression();
		$stream = $this->parser->getStream();

		if (!$template instanceof ConstantExpression)
			throw new SyntaxError('The template references in a "use" statement must be a string.', $stream->getCurrent()->getLine(), $stream->getSourceContext());

		$targets = [];

		if ($stream->nextIf('with'))
		{
			do
			{
				$name = $stream->expect(Token::NAME_TYPE)->getValue();
				$alias = $name;

				if ($stream->nextIf('as'))
					$alias = $stream->expect(Token::NAME_TYPE)->getValue();

				$targets[$name] = new ConstantExpression($alias, -1);

				if (!$stream->nextIf(Token::PUNCTUATION_TYPE, ','))
					break;
			}
			while (true);
		}

		$stream->expect(Token::BLOCK_END_TYPE);

		$this->parser->addTrait(new Node(['template' => $template, 'targets' => new Node($targets)]));

		return new Node();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'use';
	}

}
