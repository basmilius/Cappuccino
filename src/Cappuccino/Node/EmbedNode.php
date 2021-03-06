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

namespace Cappuccino\Node;

use Cappuccino\Compiler;
use Cappuccino\Node\Expression\AbstractExpression;
use Cappuccino\Node\Expression\ConstantExpression;

/**
 * Class EmbedNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class EmbedNode extends IncludeNode
{

	/**
	 * EmbedNode constructor.
	 *
	 * @param string                  $name
	 * @param int                     $index
	 * @param AbstractExpression|null $variables
	 * @param bool                    $only
	 * @param bool                    $ignoreMissing
	 * @param int                     $lineNumber
	 * @param string|null             $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $name, int $index, AbstractExpression $variables = null, bool $only = false, bool $ignoreMissing = false, int $lineNumber = 0, ?string $tag = null)
	{
		parent::__construct(new ConstantExpression('not_used', $lineNumber), $variables, $only, $ignoreMissing, $lineNumber, $tag);

		$this->setAttribute('name', $name);
		$this->setAttribute('index', $index);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	protected function addGetTemplate(Compiler $compiler): void
	{
		$compiler
			->write('$this->loadTemplate(')
			->string($this->getAttribute('name'))
			->raw(', ')
			->repr($this->getTemplateName())
			->raw(', ')
			->repr($this->getTemplateLine())
			->raw(', ')
			->string(strval($this->getAttribute('index')))
			->raw(')');
	}

}
