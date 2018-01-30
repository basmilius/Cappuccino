<?php
/**
 * This file is part of the Bas\Cappuccino package.
 *
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino;

use LogicException;

/**
 * Class Token
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino
 * @since 1.0.0
 */
final class Token
{

	public const EOF_TYPE = -1;
	public const TEXT_TYPE = 0;
	public const BLOCK_START_TYPE = 1;
	public const VAR_START_TYPE = 2;
	public const BLOCK_END_TYPE = 3;
	public const VAR_END_TYPE = 4;
	public const NAME_TYPE = 5;
	public const NUMBER_TYPE = 6;
	public const STRING_TYPE = 7;
	public const OPERATOR_TYPE = 8;
	public const PUNCTUATION_TYPE = 9;
	public const INTERPOLATION_START_TYPE = 10;
	public const INTERPOLATION_END_TYPE = 11;

	/**
	 * @var string
	 */
	private $value;

	/**
	 * @var int
	 */
	private $type;

	/**
	 * @var int
	 */
	private $lineno;

	/**
	 * Token constructor.
	 *
	 * @param int    $type
	 * @param string $value
	 * @param int    $lineno
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (int $type, string $value, int $lineno)
	{
		$this->type = $type;
		$this->value = $value;
		$this->lineno = $lineno;
	}

	/**
	 * Tests the current token for a type and/or a value.
	 *
	 * @param int|int[]            $type
	 * @param string|string[]|null $values
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function test ($type, $values = null): bool
	{
		if ($values === null && !is_int($type))
		{
			$values = $type;
			$type = self::NAME_TYPE;
		}

		return ($this->type === $type) && (null === $values || (is_array($values) && in_array($this->value, $values)) || $this->value == $values);
	}

	/**
	 * Gets the line number.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getLine (): int
	{
		return $this->lineno;
	}

	/**
	 * Gets the type.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getType (): int
	{
		return $this->type;
	}

	/**
	 * Gets the value.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getValue (): string
	{
		return $this->value;
	}

	/**
	 * Gets the constant representation of a given type.
	 *
	 * @param int  $type
	 * @param bool $short
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function typeToString (int $type, bool $short = false): string
	{
		switch ($type)
		{
			case self::EOF_TYPE:
				$name = 'EOF_TYPE';
				break;
			case self::TEXT_TYPE:
				$name = 'TEXT_TYPE';
				break;
			case self::BLOCK_START_TYPE:
				$name = 'BLOCK_START_TYPE';
				break;
			case self::VAR_START_TYPE:
				$name = 'VAR_START_TYPE';
				break;
			case self::BLOCK_END_TYPE:
				$name = 'BLOCK_END_TYPE';
				break;
			case self::VAR_END_TYPE:
				$name = 'VAR_END_TYPE';
				break;
			case self::NAME_TYPE:
				$name = 'NAME_TYPE';
				break;
			case self::NUMBER_TYPE:
				$name = 'NUMBER_TYPE';
				break;
			case self::STRING_TYPE:
				$name = 'STRING_TYPE';
				break;
			case self::OPERATOR_TYPE:
				$name = 'OPERATOR_TYPE';
				break;
			case self::PUNCTUATION_TYPE:
				$name = 'PUNCTUATION_TYPE';
				break;
			case self::INTERPOLATION_START_TYPE:
				$name = 'INTERPOLATION_START_TYPE';
				break;
			case self::INTERPOLATION_END_TYPE:
				$name = 'INTERPOLATION_END_TYPE';
				break;
			default:
				throw new LogicException(sprintf('Token of type "%s" does not exist.', $type));
		}

		return $short ? $name : Token::class . '::' . $name;
	}

	/**
	 * Gets the English representation of a given type.
	 *
	 * @param string|int $type
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public static function typeToEnglish ($type): string
	{
		switch ($type)
		{
			case self::EOF_TYPE:
				return 'end of template';
			case self::TEXT_TYPE:
				return 'text';
			case self::BLOCK_START_TYPE:
				return 'begin of statement block';
			case self::VAR_START_TYPE:
				return 'begin of print statement';
			case self::BLOCK_END_TYPE:
				return 'end of statement block';
			case self::VAR_END_TYPE:
				return 'end of print statement';
			case self::NAME_TYPE:
				return 'name';
			case self::NUMBER_TYPE:
				return 'number';
			case self::STRING_TYPE:
				return 'string';
			case self::OPERATOR_TYPE:
				return 'operator';
			case self::PUNCTUATION_TYPE:
				return 'punctuation';
			case self::INTERPOLATION_START_TYPE:
				return 'begin of string interpolation';
			case self::INTERPOLATION_END_TYPE:
				return 'end of string interpolation';
			default:
				throw new LogicException(sprintf('Token of type "%s" does not exist.', $type));
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __toString (): string
	{
		return sprintf('%s(%s)', self::typeToString($this->type, true), $this->value);
	}

}
