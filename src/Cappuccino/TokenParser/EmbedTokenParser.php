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

use Cappuccino\Node\EmbedNode;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Expression\NameExpression;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class EmbedTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class EmbedTokenParser extends IncludeTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): Node
	{
		$stream = $this->parser->getStream();
		$parent = $this->parser->getExpressionParser()->parseExpression();

		[$variables, $only, $ignoreMissing] = $this->parseArguments();

		$parentToken = $fakeParentToken = new Token(Token::STRING_TYPE, '__parent__', $token->getLine());

		if ($parent instanceof ConstantExpression)
			$parentToken = new Token(Token::STRING_TYPE, $parent->getAttribute('value'), $token->getLine());
		else if ($parent instanceof NameExpression)
			$parentToken = new Token(Token::NAME_TYPE, $parent->getAttribute('name'), $token->getLine());

		$stream->injectTokens([
			new Token(Token::BLOCK_START_TYPE, '', $token->getLine()),
			new Token(Token::NAME_TYPE, 'extends', $token->getLine()),
			$parentToken,
			new Token(Token::BLOCK_END_TYPE, '', $token->getLine()),
		]);

		$module = $this->parser->parse($stream, [$this, 'decideBlockEnd'], true);

		if ($fakeParentToken === $parentToken)
			$module->setNode('parent', $parent);

		$this->parser->embedTemplate($module);
		$stream->expect(Token::BLOCK_END_TYPE);

		return new EmbedNode($module->getTemplateName(), $module->getAttribute('index'), $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideBlockEnd (Token $token): bool
	{
		return $token->test('endembed');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'embed';
	}

}
