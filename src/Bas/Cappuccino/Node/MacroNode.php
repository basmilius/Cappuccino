<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Error\SyntaxError;

/**
 * Class MacroNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
 * @version 2.3.0
 */
class MacroNode extends Node
{

	const VARARGS_NAME = 'varargs';

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
	 * @since 2.3.0
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
	 * @since 2.3.0
	 */
	public function compile (Compiler $compiler) : void
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
			->indent();

		$compiler
			->write("\$context = \$this->environment->mergeGlobals(array(\n")
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
			->write("\$blocks = array();\n\n")
			->write("ob_start();\n")
			->write("try {\n")
			->indent()
			->subcompile($this->getNode('body'))
			->raw("\n")
			->write("return ('' === \$tmp = ob_get_contents()) ? '' : new Markup(\$tmp, \$this->environment->getCharset());\n")
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
