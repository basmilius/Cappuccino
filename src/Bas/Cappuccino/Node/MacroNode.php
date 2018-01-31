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

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Error\SyntaxError;
use Bas\Cappuccino\Markup;

/**
 * Class MacroNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @since 1.0.0
 */
class MacroNode extends Node
{

	public const VARARGS_NAME = 'varargs';

	/**
	 * MacroNode constructor.
	 *
	 * @param string      $name
	 * @param Node        $body
	 * @param Node        $arguments
	 * @param int         $lineno
	 * @param null|string $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 * @throws SyntaxError
	 */
	public function __construct (string $name, Node $body, Node $arguments, int $lineno, ?string $tag = null)
	{
		foreach ($arguments as $argumentName => $argument)
			if (self::VARARGS_NAME === $argumentName)
				throw new SyntaxError(sprintf('The argument "%s" in macro "%s" cannot be defined because the variable "%s" is reserved for arbitrary arguments.', self::VARARGS_NAME, $name, self::VARARGS_NAME), $argument->getTemplateLine());

		parent::__construct(['body' => $body, 'arguments' => $arguments], ['name' => $name], $lineno, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$markupClass = Markup::class;

		$compiler
			->addDebugInfo($this)
			->write(sprintf('public function macro_%s(', $this->getAttribute('name')));

		$count = count($this->getNode('arguments'));
		$pos = 0;

		foreach ($this->getNode('arguments') as $name => $default)
		{
			$compiler
				->raw('$__' . $name . '__ = ')
				->subcompile($default);

			if (++$pos < $count)
				$compiler->raw(', ');
		}

		if ($count)
			$compiler->raw(', ');

		$compiler
			->raw('...$__varargs__')
			->raw(")\n")
			->write("{\n")
			->indent();

		$compiler
			->write("\$context = \$this->cappuccino->mergeGlobals(array(\n")
			->indent();

		foreach ($this->getNode('arguments') as $name => $default)
			$compiler
				->write('')
				->string($name)
				->raw(' => $__' . $name . '__')
				->raw(",\n");

		$compiler
			->write('')
			->string(self::VARARGS_NAME)
			->raw(' => ');

		$compiler
			->raw("\$__varargs__,\n")
			->outdent()
			->write("));\n\n")
			->write("\$blocks = [];\n\n")
			->write("ob_start();\n")
			->write("try {\n")
			->indent()
			->subcompile($this->getNode('body'))
			->raw("\n")
			->write("return ('' === \$tmp = ob_get_contents()) ? '' : new {$markupClass}(\$tmp, \$this->cappuccino->getCharset());\n")
			->outdent()
			->write("} finally {\n")
			->indent()
			->write("ob_end_clean();\n")
			->outdent()
			->write("}\n")
			->outdent()
			->write("}\n\n");
	}

}
