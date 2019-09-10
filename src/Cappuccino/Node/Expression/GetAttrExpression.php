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
use Cappuccino\Extension\SandboxExtension;
use Cappuccino\Template;

/**
 * Class GetAttrExpression
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node\Expression
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
	 * @param string                  $type
	 * @param int                     $lineNumber
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(AbstractExpression $node, AbstractExpression $attribute, AbstractExpression $arguments = null, string $type = '', int $lineNumber = 0)
	{
		$nodes = ['node' => $node, 'attribute' => $attribute];

		if ($arguments !== null)
			$nodes['arguments'] = $arguments;

		parent::__construct($nodes, ['type' => $type, 'is_defined_test' => false, 'ignore_strict_check' => false, 'optimizable' => true], $lineNumber);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$cappuccino = $compiler->getCappuccino();

		if ($this->getAttribute('optimizable') && (!$cappuccino->isStrictVariables() || $this->getAttribute('ignore_strict_check')) && !$this->getAttribute('is_defined_test') && Template::ARRAY_CALL === $this->getAttribute('type'))
		{
			$var = '$' . $compiler->getVarName();
			$compiler
				->raw('((' . $var . ' = ')
				->subcompile($this->getNode('node'))
				->raw(') && is_array(')
				->raw($var)
				->raw(') || ')
				->raw($var)
				->raw(' instanceof ArrayAccess ? (')
				->raw($var)
				->raw('[')
				->subcompile($this->getNode('attribute'))
				->raw('] ?? null) : null)');

			return;
		}

		$compiler->raw('StaticMethods::getAttribute($this->cappuccino, $this->source, ');

		if ($this->getAttribute('ignore_strict_check'))
			$this->getNode('node')->setAttribute('ignore_strict_check', true);

		$compiler
			->subcompile($this->getNode('node'))
			->raw(', ')
			->subcompile($this->getNode('attribute'));

		if ($this->hasNode('arguments'))
			$compiler->raw(', ')->subcompile($this->getNode('arguments'));
		else
			$compiler->raw(', []');

		$compiler->raw(', ')
			->repr($this->getAttribute('type'))
			->raw(', ')->repr($this->getAttribute('is_defined_test'))
			->raw(', ')->repr($this->getAttribute('ignore_strict_check'))
			->raw(', ')->repr($cappuccino->hasExtension(SandboxExtension::class))
			->raw(', ')->repr($this->getNode('node')->getTemplateLine())
			->raw(')');
	}

}
