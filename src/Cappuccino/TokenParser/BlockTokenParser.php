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
use Cappuccino\Node\BlockNode;
use Cappuccino\Node\BlockReferenceNode;
use Cappuccino\Node\Node;
use Cappuccino\Node\PrintNode;
use Cappuccino\Token;

/**
 * Class BlockTokenParser
 *
 * {% block toolbar %}
 *     This section of the template is reusable.
 * {% endblock %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class BlockTokenParser extends AbstractTokenParser
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
		$name = $stream->expect(Token::NAME_TYPE)->getValue();

		if ($this->parser->hasBlock($name))
			throw new SyntaxError(sprintf("The block '%s' has already been defined line %d.", $name, $this->parser->getBlock($name)->getTemplateLine()), $stream->getCurrent()->getLine(), $stream->getSourceContext());

		$this->parser->setBlock($name, $block = new BlockNode($name, new Node([]), $lineno));
		$this->parser->pushLocalScope();
		$this->parser->pushBlockStack($name);

		if ($stream->nextIf(Token::BLOCK_END_TYPE))
		{
			$body = $this->parser->subparse([$this, 'decideBlockEnd'], true);

			if ($token = $stream->nextIf(Token::NAME_TYPE))
			{
				$value = $token->getValue();

				if ($value != $name)
					throw new SyntaxError(sprintf('Expected endblock for block "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
			}
		}
		else
		{
			$body = new Node([
				new PrintNode($this->parser->getExpressionParser()->parseExpression(), $lineno),
			]);
		}

		$stream->expect(Token::BLOCK_END_TYPE);

		$block->setNode('body', $body);
		$this->parser->popBlockStack();
		$this->parser->popLocalScope();

		return new BlockReferenceNode($name, $lineno, $this->getTag());
	}

	/**
	 * Returns TRUE if the block should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function decideBlockEnd(Token $token): bool
	{
		return $token->test('endblock');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'block';
	}

}
