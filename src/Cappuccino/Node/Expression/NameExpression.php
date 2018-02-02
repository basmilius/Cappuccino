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

namespace Cappuccino\Node\Expression;

use Cappuccino\Compiler;
use Cappuccino\Error\RuntimeError;

class NameExpression extends AbstractExpression
{

	private $specialVars = [
		'_self' => '$this->getTemplateName()',
		'_context' => '$context',
		'_charset' => '$this->cappuccino->getCharset()',
	];

	/**
	 * NameExpression constructor.
	 *
	 * @param string $name
	 * @param int    $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $name, int $lineno)
	{
		parent::__construct([], ['name' => $name, 'is_defined_test' => false, 'ignore_strict_check' => false, 'always_defined' => false], $lineno);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$name = $this->getAttribute('name');

		$compiler
			->addDebugInfo($this);

		if ($this->getAttribute('is_defined_test'))
		{
			if ($this->isSpecial())
			{
				$compiler
					->repr(true);
			}
			else
			{
				$compiler
					->raw('array_key_exists(')
					->repr($name)
					->raw(', $context)');
			}
		}
		else if ($this->isSpecial())
		{
			$compiler
				->raw($this->specialVars[$name]);
		}
		else if ($this->getAttribute('always_defined'))
		{
			$compiler
				->raw('$context[')
				->string($name)
				->raw(']');
		}
		else
		{
			if ($this->getAttribute('ignore_strict_check') || !$compiler->getCappuccino()->isStrictVariables())
			{
				$compiler
					->raw('($context[')
					->string($name)
					->raw('] ?? null)');
			}
			else
			{
				$classRuntimeError = RuntimeError::class;
				$compiler
					->raw('(isset($context[')
					->string($name)
					->raw(']) || array_key_exists(')
					->string($name)
					->raw(', $context) ? $context[')
					->string($name)
					->raw('] : (function () { throw new ' . $classRuntimeError . '(\'Variable ')
					->string($name)
					->raw(' does not exist.\', ')
					->repr($this->lineno)
					->raw(', $this->source); })()')
					->raw(')');
			}
		}
	}

	/**
	 * Checks if the name expression is special.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isSpecial (): bool
	{
		return isset($this->specialVars[$this->getAttribute('name')]);
	}

	/**
	 * Checks if the name expression is simple.
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function isSimple (): bool
	{
		return !$this->isSpecial() && !$this->getAttribute('is_defined_test');
	}

}
