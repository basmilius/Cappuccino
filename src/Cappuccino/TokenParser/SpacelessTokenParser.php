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

use Cappuccino\Node\Node;
use Cappuccino\Node\SpacelessNode;
use Cappuccino\Token;

/**
 * Class SpacelessTokenParser
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\TokenParser
 * @since 1.0.0
 */
final class SpacelessTokenParser extends AbstractTokenParser
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function parse (Token $token): Node
	{
		$lineno = $token->getLine();

		$this->parser->getStream()->expect(/*Token::BLOCK_END_TYPE*/ 3);
		$body = $this->parser->subparse([$this, 'decideSpacelessEnd'], true);
		$this->parser->getStream()->expect(/*Token::BLOCK_END_TYPE*/ 3);

		return new SpacelessNode($body, $lineno, $this->getTag());
	}

	/**
	 * Decide if spaceless should end.
	 *
	 * @param Token $token
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function decideSpacelessEnd (Token $token): bool
	{
		return $token->test('endspaceless');
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTag (): string
	{
		return 'spaceless';
	}

}
