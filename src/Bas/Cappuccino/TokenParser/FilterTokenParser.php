<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Bas\Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Node\BlockNode;
use Bas\Cappuccino\Node\Expression\BlockReferenceExpression;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\PrintNode;
use Bas\Cappuccino\Token;

/**
 * Class FilterTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @since 1.0.0
 */
final class FilterTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): Node
	{
		$name = $this->parser->getVarName();
		$ref = new BlockReferenceExpression(new ConstantExpression($name, $token->getLine()), null, $token->getLine(), $this->getTag());

		$filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());
		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		$body = $this->parser->subparse([$this, 'decideBlockEnd'], true);
		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		$block = new BlockNode($name, $body, $token->getLine());
		$this->parser->setBlock($name, $block);

		return new PrintNode($filter, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideBlockEnd (Token $token): bool
	{
		return $token->test('endfilter');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'filter';
	}

}
