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

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;
use function array_chunk;
use function array_push;
use function ctype_digit;

/**
 * Class ArrayExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
 * @since 1.0.0
 */
class ArrayExpression extends AbstractExpression
{

	/**
	 * @var int
	 */
	private $index;

	/**
	 * ArrayExpression constructor.
	 *
	 * @param array $elements
	 * @param int   $lineNumber
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(array $elements, int $lineNumber)
	{
		parent::__construct($elements, [], $lineNumber);

		$this->index = -1;

		foreach ($this->getKeyValuePairs() as $pair)
		{
			if ($pair['key'] instanceof ConstantExpression && ctype_digit((string)$pair['key']->getAttribute('value')) && $pair['key']->getAttribute('value') > $this->index)
			{
				$this->index = $pair['key']->getAttribute('value');
			}
		}
	}

	/**
	 * Gets key value pairs from the array.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getKeyValuePairs(): array
	{
		$pairs = [];

		foreach (array_chunk($this->nodes, 2) as $pair)
			$pairs[] = ['key' => $pair[0], 'value' => $pair[1]];

		return $pairs;
	}

	/**
	 * Returns TRUE when an element is found.
	 *
	 * @param AbstractExpression $key
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasElement(AbstractExpression $key): bool
	{
		foreach ($this->getKeyValuePairs() as $pair)
			if ((string)$key === (string)$pair['key'])
				return true;

		return false;
	}

	/**
	 * Adds an element to the array.
	 *
	 * @param AbstractExpression      $value
	 * @param AbstractExpression|null $key
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addElement(AbstractExpression $value, AbstractExpression $key = null): void
	{
		if ($key === null)
			$key = new ConstantExpression(++$this->index, $value->getTemplateLine());

		array_push($this->nodes, $key, $value);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->raw('[');
		$first = true;

		foreach ($this->getKeyValuePairs() as $pair)
		{
			if (!$first)
			{
				$compiler->raw(', ');
			}
			$first = false;

			$compiler
				->subcompile($pair['key'])
				->raw(' => ')
				->subcompile($pair['value']);
		}

		$compiler->raw(']');
	}

}
