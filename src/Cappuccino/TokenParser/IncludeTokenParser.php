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

use Cappuccino\Node\IncludeNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class IncludeTokenParser
 *
 * {% include "header.cappy" %}
 *
 * @author Bas Milius <bas@ideemedia.nl>
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
	public function parse(Token $token): Node
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		[$variables, $only, $ignoreMissing] = $this->parseArguments();

		return new IncludeNode($expr, $variables, $only, $ignoreMissing, $token->getLine(), $this->getTag());
	}

	/**
	 * Parses arguments.
	 *
	 * @return array
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	protected function parseArguments(): array
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
	public function getTag(): string
	{
		return 'include';
	}

}
