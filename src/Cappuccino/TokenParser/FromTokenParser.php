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
 * Class FromTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class FromTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): Node
	{
		$macro = $this->parser->getExpressionParser()->parseExpression();
		$stream = $this->parser->getStream();
		$stream->expect('import');

		$targets = [];

		do
		{
			$name = $stream->expect(/*Token::NAME_TYPE*/ 5)->getValue();
			$alias = $name;

			if ($stream->nextIf('as'))
				$alias = $stream->expect(/*Token::NAME_TYPE*/ 5)->getValue();

			$targets[$name] = $alias;

			if (!$stream->nextIf(/*Token::PUNCTUATION_TYPE*/ 9, ','))
				break;
		}
		while (true);

		$stream->expect(/*Token::BLOCK_END_TYPE*/ 3);

		$node = new ImportNode($macro, new AssignNameExpression($this->parser->getVarName(), $token->getLine()), $token->getLine(), $this->getTag());

		foreach ($targets as $name => $alias)
			$this->parser->addImportedSymbol('function', $alias, 'macro_' . $name, $node->getNode('var'));

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'from';
	}

}
