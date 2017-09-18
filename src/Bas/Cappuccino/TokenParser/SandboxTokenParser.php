<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\IncludeNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\SandboxNode;
use Bas\Cappuccino\Node\TextNode;
use Bas\Cappuccino\Token;

/**
 * Class SandboxTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
final class SandboxTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : Node
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
	 * @since 2.3.0
	 */
	public function decideBlockEnd (Token $token) : bool
	{
		return $token->test('endsandbox');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getTag () : string
	{
		return 'sandbox';
	}

}
