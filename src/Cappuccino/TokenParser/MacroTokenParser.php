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

use Cappuccino\Error\SyntaxError;
use Cappuccino\Node\BodyNode;
use Cappuccino\Node\MacroNode;
use Cappuccino\Node\Node;
use Cappuccino\Token;

/**
 * Class MacroTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class MacroTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): ?Node
	{
		$lineno = $token->getLine();
		$stream = $this->parser->getStream();
		$name = $stream->expect(/*Token::NAME_TYPE*/ 5)->getValue();

		$arguments = $this->parser->getExpressionParser()->parseArguments(true, true);

		$stream->expect(/*Token::BLOCK_END_TYPE*/ 3);
		$this->parser->pushLocalScope();
		$body = $this->parser->subparse([$this, 'decideBlockEnd'], true);

		if ($token = $stream->nextIf(/*Token::NAME_TYPE*/ 5))
		{
			$value = $token->getValue();

			if ($value != $name)
				throw new SyntaxError(sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
		}

		$this->parser->popLocalScope();
		$stream->expect(/*Token::BLOCK_END_TYPE*/ 3);

		$this->parser->setMacro($name, new MacroNode($name, new BodyNode([$body]), $arguments, $lineno, $this->getTag()));

		return null;
	}

	/**
	 * Decide if the block should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideBlockEnd (Token $token)
	{
		return $token->test('endmacro');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'macro';
	}

}
