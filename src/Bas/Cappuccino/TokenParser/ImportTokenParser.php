<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Node\Expression\AssignNameExpression;
use Bas\Cappuccino\Node\ImportNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class ImportTokenParser
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
final class ImportTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : Node
	{
		$macro = $this->parser->getExpressionParser()->parseExpression();
		$this->parser->getStream()->expect('as');
		$var = new AssignNameExpression($this->parser->getStream()->expect(Token::NAME_TYPE)->getValue(), $token->getLine());
		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		$this->parser->addImportedSymbol('template', $var->getAttribute('name'));

		return new ImportNode($macro, $var, $token->getLine(), $this->getTag());
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getTag () : string
	{
		return 'import';
	}
}
