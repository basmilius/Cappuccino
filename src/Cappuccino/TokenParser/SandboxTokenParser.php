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
use Cappuccino\Node\IncludeNode;
use Cappuccino\Node\Node;
use Cappuccino\Node\SandboxNode;
use Cappuccino\Node\TextNode;
use Cappuccino\Token;

/**
 * Class SandboxTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class SandboxTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): Node
	{
		$stream = $this->parser->getStream();
		$stream->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
		$stream->expect(Token::BLOCK_END_TYPE);

		if (!$body instanceof IncludeNode)
			foreach ($body as $node)
			{
				if ($node instanceof TextNode && ctype_space($node->getAttribute('data')))
					continue;

				if (!$node instanceof IncludeNode)
					throw new SyntaxError('Only "include" tags are allowed within a "sandbox" section.', $node->getTemplateLine(), $stream->getSourceContext());
			}

		return new SandboxNode($body, $token->getLine(), $this->getTag());
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
		return $token->test('endsandbox');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'sandbox';
	}

}
