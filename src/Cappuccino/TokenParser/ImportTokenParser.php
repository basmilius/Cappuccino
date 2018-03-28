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

use Cappuccino\Node\Expression\AssignNameExpression;
use Cappuccino\Node\ImportNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class ImportTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class ImportTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse(Token $token): Node
	{
		$macro = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect('as');
		$var = new AssignNameExpression($this->parser->getStream()->expect(/*Token::NAME_TYPE*/
			5)->getValue(), $token->getLine());
		$this->parser->getStream()->expect(/*Token::BLOCK_END_TYPE*/
			3);

		$this->parser->addImportedSymbol('template', $var->getAttribute('name'));

		return new ImportNode($macro, $var, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag(): string
	{
		return 'import';
	}
}
