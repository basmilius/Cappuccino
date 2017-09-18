<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\BodyNode;
use Bas\Cappuccino\Node\MacroNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class MacroTokenParser
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\TokenParser
 * @version 2.3.0
 */
final class MacroTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function parse (Token $token) : ?Node
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$name = $stream->expect(Token::NAME_TYPE)->getValue();

		$arguments = $this->parser->getExpressionParser()->parseArguments(true, true);

		$stream->expect(Token::BLOCK_END_TYPE);
		$this->parser->pushLocalScope();
		$body = $this->parser->subparse([$this, 'decideBlockEnd'], true);

		if ($token = $stream->nextIf(Token::NAME_TYPE))
		{
			$value = $token->getValue();

			if ($value != $name)
				throw new SyntaxError(sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
		}

		$this->parser->popLocalScope();
		$stream->expect(Token::BLOCK_END_TYPE);

		$this->parser->setMacro($name, new MacroNode($name, new BodyNode([$body]), $arguments, $lineno, $this->getTag()));

		return null;
	}

	/**
	 * Decide if the block should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function decideBlockEnd (Token $token)
	{
		return $token->test('endmacro');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getTag () : string
	{
		return 'macro';
	}

}
