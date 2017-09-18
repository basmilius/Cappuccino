<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\SpacelessNode;
use Bas\Cappuccino\Token;

/**
 * Class SpacelessTokenParser
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
final class SpacelessTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : Node
	{
		$lineno = $token->getLine();

		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
		$body = $this->parser->subparse([$this, 'decideSpacelessEnd'], true);
		$this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

		return new SpacelessNode($body, $lineno, $this->getTag());
	}

	/**
	 * Decide if spaceless should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function decideSpacelessEnd (Token $token) : bool
	{
		return $token->test('endspaceless');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getTag () : string
	{
		return 'spaceless';
	}

}
