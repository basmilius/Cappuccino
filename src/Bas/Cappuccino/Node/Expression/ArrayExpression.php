<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;

/**
 * Class ArrayExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @version 2.3.0
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
	 * @param int   $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function __construct (array $elements, int $lineno)
	{
		parent::__construct($elements, [], $lineno);

		$this->index = -1;

		foreach ($this->getKeyValuePairs() as $pair)
			if ($pair['key'] instanceof ConstantExpression && ctype_digit((string)$pair['key']->getAttribute('value')) && $pair['key']->getAttribute('value') > $this->index)
				$this->index = $pair['key']->getAttribute('value');
	}

	/**
	 * Gets key-value pairs.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function getKeyValuePairs () : array
	{
		$pairs = [];

		foreach (array_chunk($this->nodes, 2) as $pair)
		{
			$pairs[] = [
				'key' => $pair[0],
				'value' => $pair[1],
			];
		}

		return $pairs;
	}

	/**
	 * Checks if we have an element.
	 *
	 * @param AbstractExpression $key
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function hasElement (AbstractExpression $key) : bool
	{
		foreach ($this->getKeyValuePairs() as $pair)
			if ((string)$key === (string)$pair['key'])
				return true;

		return false;
	}

	/**
	 * Adds an element.
	 *
	 * @param AbstractExpression      $value
	 * @param AbstractExpression|null $key
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function addElement (AbstractExpression $value, ?AbstractExpression $key = null)
	{
		if ($key === null)
			$key = new ConstantExpression(++$this->index, $value->getTemplateLine());

		array_push($this->nodes, $key, $value);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
	{
		$compiler->raw('array(');
		$first = true;

		foreach ($this->getKeyValuePairs() as $pair)
		{
			if (!$first)
				$compiler->raw(', ');

			$first = false;

			$compiler->subcompile($pair['key'])->raw(' => ')->subcompile($pair['value']);
		}

		$compiler->raw(')');
	}

}
