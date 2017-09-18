<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Node\Expression\AssignNameExpression;
use Bas\Cappuccino\Node\ImportNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class FromTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
final class FromTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : Node
	{
		$macro = $this->parser->getExpressionParser()->parseExpression();
		$stream = $this->parser->getStream();
		$stream->expect('import');

		$targets = [];

		do
		{
			$name = $stream->expect(Token::NAME_TYPE)->getValue();
			$alias = $name;

			if ($stream->nextIf('as'))
				$alias = $stream->expect(Token::NAME_TYPE)->getValue();

			$targets[$name] = $alias;

			if (!$stream->nextIf(Token::PUNCTUATION_TYPE, ','))
				break;
		}
		while (true);

		$stream->expect(Token::BLOCK_END_TYPE);

		$node = new ImportNode($macro, new AssignNameExpression($this->parser->getVarName(), $token->getLine()), $token->getLine(), $this->getTag());

		foreach ($targets as $name => $alias)
			$this->parser->addImportedSymbol('function', $alias, 'macro_' . $name, $node->getNode('var'));

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getTag () : string
	{
		return 'from';
	}

}
