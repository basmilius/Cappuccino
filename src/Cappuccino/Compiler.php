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

namespace Cappuccino;

use Cappuccino\Node\Node;
use LogicException;

/**
 * Class Compiler
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
class Compiler
{

	/**
	 * @var Cappuccino
	 */
	private $cappuccino;

	/**
	 * @var int
	 */
	private $lastLine;

	/**
	 * @var string
	 */
	private $source;

	/**
	 * @var int
	 */
	private $indentation;

	/**
	 * @var array
	 */
	private $debugInfo = [];

	/**
	 * @var int
	 */
	private $sourceOffset;

	/**
	 * @var int
	 */
	private $sourceLine;

	/**
	 * Compiler constructor.
	 *
	 * @param Cappuccino $cappuccino
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (Cappuccino $cappuccino)
	{
		$this->cappuccino = $cappuccino;
	}

	/**
	 * Gets the Cappuccino instance.
	 *
	 * @return Cappuccino
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getCappuccino (): Cappuccino
	{
		return $this->cappuccino;
	}

	/**
	 * Gets the PHP code after compilation.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSource (): string
	{
		return $this->source;
	}

	/**
	 * Compiles a Node.
	 *
	 * @param Node $node
	 * @param int  $indentation
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Node $node, int $indentation = 0): Compiler
	{
		$this->lastLine = null;
		$this->source = '';
		$this->debugInfo = [];
		$this->sourceOffset = 0;

		$this->sourceLine = 1;
		$this->indentation = $indentation;

		$node->compile($this);

		return $this;
	}

	/**
	 * Sub Compiles a Node.
	 *
	 * @param Node $node
	 * @param bool $raw
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function subcompile (Node $node, bool $raw = true): Compiler
	{
		if (!$raw)
			$this->source .= str_repeat(' ', $this->indentation * 4);

		$node->compile($this);

		return $this;
	}

	/**
	 * Adds a raw string to the compiled code.
	 *
	 * @param string $string
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function raw (string $string): Compiler
	{
		$this->source .= $string;

		return $this;
	}

	/**
	 * Writes a string to the compiled code by adding indentation.
	 *
	 * @param string[] ...$strings
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function write (string ...$strings): Compiler
	{
		foreach ($strings as $string)
			$this->source .= str_repeat(' ', $this->indentation * 4) . $string;

		return $this;
	}

	/**
	 * Adds a quoted string to the compiled code.
	 *
	 * @param string $value
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function string (string $value): Compiler
	{
		$this->source .= sprintf('"%s"', addcslashes($value, "\0\t\"\$\\"));

		return $this;
	}

	/**
	 * Returns a PHP representation of a given value.
	 *
	 * @param mixed $value
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function repr ($value): Compiler
	{
		if (is_int($value) || is_float($value))
		{
			if ($locale = setlocale(LC_NUMERIC, '0'))
				setlocale(LC_NUMERIC, 'C');

			$this->raw(strval($value));

			if ($locale)
				setlocale(LC_NUMERIC, $locale);
		}
		else if (null === $value)
		{
			$this->raw('null');
		}
		else if (is_bool($value))
		{
			$this->raw($value ? 'true' : 'false');
		}
		else if (is_array($value))
		{
			$this->raw('array(');
			$first = true;
			foreach ($value as $key => $v)
			{
				if (!$first)
				{
					$this->raw(', ');
				}
				$first = false;
				$this->repr($key);
				$this->raw(' => ');
				$this->repr($v);
			}
			$this->raw(')');
		}
		else
		{
			$this->string($value);
		}

		return $this;
	}

	/**
	 * Adds debugging information.
	 *
	 * @param Node $node
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function addDebugInfo (Node $node): Compiler
	{
		if ($node->getTemplateLine() != $this->lastLine)
		{
			$this->write(sprintf("// line %d\n", $node->getTemplateLine()));

			$this->sourceLine += substr_count($this->source, "\n", $this->sourceOffset);
			$this->sourceOffset = strlen($this->source);
			$this->debugInfo[$this->sourceLine] = $node->getTemplateLine();

			$this->lastLine = $node->getTemplateLine();
		}

		return $this;
	}

	/**
	 * Gets debugging information.
	 *
	 * @return array
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getDebugInfo (): array
	{
		ksort($this->debugInfo);

		return $this->debugInfo;
	}

	/**
	 * Indents the generated code.
	 *
	 * @param int $step
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function indent (int $step = 1): Compiler
	{
		$this->indentation += $step;

		return $this;
	}

	/**
	 * Outdents the generated code.
	 *
	 * @param int $step
	 *
	 * @return Compiler
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function outdent (int $step = 1): Compiler
	{
		if ($this->indentation < $step)
			throw new LogicException('Unable to call outdent() as the indentation would become negative.');

		$this->indentation -= $step;

		return $this;
	}

	/**
	 * Gets the var name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getVarName (): string
	{
		return sprintf('__internal_%s', hash('sha256', uniqid(mt_rand(), true), false));
	}

}
