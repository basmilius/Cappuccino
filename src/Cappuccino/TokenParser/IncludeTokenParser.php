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

use Cappuccino\Error\RuntimeError;
use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\IncludeNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class IncludeTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
class IncludeTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): Node
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		list($variables, $only, $ignoreMissing) = $this->parseArguments();

		return new IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
	}

	/**
	 * Parses arguments.
	 *
	 * @return array
	 * @throws RuntimeError
	 * @throws SyntaxError
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function parseArguments (): array
	{
		$stream = $this->parser->getStream();
		$ignoreMissing = false;
		$variables = null;
		$only = false;

		if ($stream->nextIf(/*Token::NAME_TYPE*/ 5, 'ignore'))
		{
			$stream->expect(/*Token::NAME_TYPE*/ 5, 'missing');

			$ignoreMissing = true;
		}

		if ($stream->nextIf(/*Token::NAME_TYPE*/ 5, 'with'))
			$variables = $this->parser->getExpressionParser()->parseExpression();

		if ($stream->nextIf(/*Token::NAME_TYPE*/ 5, 'only'))
			$only = true;

		$stream->expect(/*Token::BLOCK_END_TYPE*/ 3);

		return [$variables, $only, $ignoreMissing];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'include';
	}

}
