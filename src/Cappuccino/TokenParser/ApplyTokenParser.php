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

use Cappuccino\Node\Expression\TempNameExpression;
use Cappuccino\Node\Node;
use Cappuccino\Node\PrintNode;
use Cappuccino\Node\SetNode;
use Cappuccino\Token;

/**
 * Class ApplyTokenParser
 *
 * {% apply upper %}
 *     This text becomes uppercase.
 * {% endapply %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\TokenParser
 * @since 2.0.0
 */
final class ApplyTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function parse(Token $token): Node
	{
		$lineno = $token->getLine();
		$name = $this->parser->getVarName();

		$ref = new TempNameExpression($name, $lineno);
		$ref->setAttribute('always_defined', true);

		$filter = $this->parser->getExpressionParser()->parseFilterExpressionRaw($ref, $this->getTag());

		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideApplyEnd'], true);
		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		return new Node([
			new SetNode(true, $ref, $body, $lineno, $this->getTag()),
			new PrintNode($filter, $lineno, $this->getTag()),
		]);
	}

	/**
	 * Returns TRUE if the block should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	public function decideApplyEnd(Token $token): bool
	{
		return $token->test('endapply');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.0.0
	 */
	public function getTag(): string
	{
		return 'apply';
	}

}
