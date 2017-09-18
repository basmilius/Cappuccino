<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class UseTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 1.0.0
 */
final class UseTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token) : ?Node
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

		return null;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag () : string
	{
		return 'use';
	}

}
