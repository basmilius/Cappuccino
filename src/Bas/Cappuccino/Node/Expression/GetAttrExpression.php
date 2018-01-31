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

namespace Bas\Cappuccino\Node\Expression;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Template;
use Bas\Cappuccino\Util\StaticMethods;

/**
 * Class GetAttrExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node\Expression
 * @since 1.0.0
 */
class GetAttrExpression extends AbstractExpression
{

	/**
	 * GetAttrExpression constructor.
	 *
	 * @param AbstractExpression      $node
	 * @param AbstractExpression      $attribute
	 * @param AbstractExpression|null $arguments
	 * @param mixed|null              $type
	 * @param int                     $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (AbstractExpression $node, AbstractExpression $attribute, ?AbstractExpression $arguments = null, $type, int $lineno)
	{
		$nodes = ['node' => $node, 'attribute' => $attribute];

		if ($arguments !== null)
			$nodes['arguments'] = $arguments;

		parent::__construct($nodes, ['type' => $type, 'is_defined_test' => false, 'ignore_strict_check' => false], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$compiler->raw(StaticMethods::class . '::getAttribute($this->cappuccino, $this->getSourceContext(), ');

		if ($this->getAttribute('ignore_strict_check'))
			$this->getNode('node')->setAttribute('ignore_strict_check', true);

		$compiler->subcompile($this->getNode('node'));
		$compiler->raw(', ')->subcompile($this->getNode('attribute'));

		$needFourth = $this->getAttribute('ignore_strict_check');
		$needThird = $needFourth || $this->getAttribute('is_defined_test');
		$needSecond = $needThird || Template::ANY_CALL !== $this->getAttribute('type');
		$needFirst = $needSecond || $this->hasNode('arguments');

		if ($needFirst)
		{
			if ($this->hasNode('arguments'))
				$compiler->raw(', ')->subcompile($this->getNode('arguments'));
			else
				$compiler->raw(', []');
		}

		if ($needSecond)
			$compiler->raw(', ')->repr($this->getAttribute('type'));

		if ($needThird)
			$compiler->raw(', ')->repr($this->getAttribute('is_defined_test'));

		if ($needFourth)
			$compiler->raw(', ')->repr($this->getAttribute('ignore_strict_check'));

		$compiler->raw(')');
	}

}
