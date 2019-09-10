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
use Cappuccino\Error\SyntaxError;

/**
 * Class MacroNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
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
	 * @param int         $lineNumber
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(string $name, Node $body, Node $arguments, int $lineNumber, ?string $tag = null)
	{
		foreach ($arguments as $argumentName => $argument)
			if ($argumentName === self::VARARGS_NAME)
				throw new SyntaxError(sprintf('The argument "%s" in macro "%s" cannot be defined because the variable "%s" is reserved for arbitrary arguments.', self::VARARGS_NAME, $name, self::VARARGS_NAME), $argument->getTemplateLine(), $argument->getSourceContext());

		parent::__construct(['body' => $body, 'arguments' => $arguments], ['name' => $name], $lineNumber, $tag);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
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
			->indent()
			->write("\$macros = \$this->macros;\n")
			->write("\$context = \$this->cappuccino->mergeGlobals([\n")
			->indent();

		foreach ($this->getNode('arguments') as $name => $default)
		{
			$compiler
				->write('')
				->string($name)
				->raw(' => $__' . $name . '__')
				->raw(",\n");
		}

		$compiler
			->write('')
			->string(self::VARARGS_NAME)
			->raw(' => ');

		$compiler
			->raw("\$__varargs__,\n")
			->outdent()
			->write("]);\n\n")
			->write("\$blocks = [];\n\n");

		if ($compiler->getCappuccino()->isDebug())
			$compiler->write("ob_start();\n");
		else
			$compiler->write("ob_start(function () { return ''; });\n");

		$compiler
			->write("try {\n")
			->indent()
			->subcompile($this->getNode('body'))
			->raw("\n")
			->write("return ('' === \$tmp = ob_get_contents()) ? '' : new Markup(\$tmp, \$this->cappuccino->getCharset());\n")
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
