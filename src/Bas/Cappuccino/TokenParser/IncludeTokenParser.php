<?php
declare(strict_types=1);

namespace Bas\Cappuccino\TokenParser;

use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Node\IncludeNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Token;

/**
 * Class IncludeTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\TokenParser
 * @version 1.0.0
 */
class IncludeTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token) : Node
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		list($variables, $only, $ignoreMissing) = $this->parseArguments();

		return new IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
	}

	/**
	 * Parses arguments.
	 *
	 * @return array
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function parseArguments () : array
	{
		$stream = $this->parser->getStream();
		$ignoreMissing = false;
		$variables = null;
		$only = false;

		if ($stream->nextIf(Token::NAME_TYPE, 'ignore'))
		{
			$stream->expect(Token::NAME_TYPE, 'missing');

			$ignoreMissing = true;
		}

		if ($stream->nextIf(Token::NAME_TYPE, 'with'))
			$variables = $this->parser->getExpressionParser()->parseExpression();

		if ($stream->nextIf(Token::NAME_TYPE, 'only'))
			$only = true;

		$stream->expect(Token::BLOCK_END_TYPE);

		return [$variables, $only, $ignoreMissing];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag () : string
	{
		return 'include';
	}

}
