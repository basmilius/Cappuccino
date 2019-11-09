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

namespace Cappuccino\Extension;

use Cappuccino\Cappuccino;
use Cappuccino\CappuccinoFilter;
use Cappuccino\Error\Error;
use Cappuccino\Error\RuntimeError;
use Cappuccino\FileExtensionEscapingStrategy;
use Cappuccino\Markup;
use Cappuccino\Node\Expression\ConstantExpression;
use Cappuccino\Node\Node;
use Cappuccino\NodeVisitor\EscaperNodeVisitor;
use Cappuccino\TokenParser\AutoEscapeTokenParser;
use Cappuccino\Util\StaticMethods;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use function array_keys;
use function array_merge;
use function array_unique;
use function bin2hex;
use function call_user_func;
use function class_implements;
use function class_parents;
use function get_class;
use function htmlspecialchars;
use function iconv;
use function implode;
use function in_array;
use function is_object;
use function is_string;
use function ltrim;
use function mb_ord;
use function method_exists;
use function ord;
use function preg_match;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;
use function strlen;
use function strtoupper;
use function substr;

final class EscaperExtension extends AbstractExtension
{

	/**
	 * @var string|false
	 */
	private $defaultStrategy;

	/**
	 * @var callable[][]
	 */
	private $escapers = [];

	/**
	 * @var array
	 */
	public $safeClasses = [];

	/**
	 * @var array
	 */
	public $safeLookup = [];

	/**
	 * EscaperExtension constructor.
	 *
	 * @param string|false $defaultStrategy
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct($defaultStrategy = 'html')
	{
		$this->setDefaultStrategy($defaultStrategy);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers(): array
	{
		return [new AutoEscapeTokenParser()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors(): array
	{
		return [new EscaperNodeVisitor()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters(): array
	{
		return [
			new CappuccinoFilter('escape', [$this, 'onFilterEscape'], ['needs_cappuccino' => true, 'is_safe_callback' => [$this, 'onFilterIsSafe']]),
			new CappuccinoFilter('e', [$this, 'onFilterEscape'], ['needs_cappuccino' => true, 'is_safe_callback' => [$this, 'onFilterIsSafe']]),
			new CappuccinoFilter('raw', [$this, 'onFilterRaw'], ['is_safe' => ['all']]),
		];
	}

	/**
	 * Sets the default escaping strategy.
	 *
	 * @param $defaultStrategy
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setDefaultStrategy($defaultStrategy): void
	{
		if ($defaultStrategy === 'name')
			$defaultStrategy = [FileExtensionEscapingStrategy::class, 'guess'];

		$this->defaultStrategy = $defaultStrategy;
	}

	/**
	 * Gets the default escaping strategy.
	 *
	 * @param string $name
	 *
	 * @return string|false
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getDefaultStrategy(string $name)
	{
		if (!is_string($this->defaultStrategy) && $this->defaultStrategy !== false)
			return call_user_func($this->defaultStrategy, $name);

		return $this->defaultStrategy;
	}

	/**
	 * Sets an escaper.
	 *
	 * @param string   $strategy
	 * @param callable $callable
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setEscaper(string $strategy, callable $callable)
	{
		$this->escapers[$strategy] = $callable;
	}

	/**
	 * Gets an escaper.
	 *
	 * @return callable[]
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getEscapers(): array
	{
		return $this->escapers;
	}

	/**
	 * Sets safe classes.
	 *
	 * @param array $safeClasses
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	public function setSafeClasses(array $safeClasses = [])
	{
		$this->safeClasses = [];
		$this->safeLookup = [];

		foreach ($safeClasses as $class => $strategies)
			$this->addSafeClass($class, $strategies);
	}

	/**
	 * Adds a safe class.
	 *
	 * @param string $class
	 * @param array  $strategies
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.0.0
	 */
	public function addSafeClass(string $class, array $strategies)
	{
		$class = ltrim($class, '\\');

		if (!isset($this->safeClasses[$class]))
			$this->safeClasses[$class] = [];

		$this->safeClasses[$class] = array_merge($this->safeClasses[$class], $strategies);

		foreach ($strategies as $strategy)
			$this->safeLookup[$strategy][$class] = true;
	}

	/**
	 * Invoked on the escape filter.
	 *
	 * @param Cappuccino    $cappuccino
	 * @param string|Markup $string
	 * @param string|false  $strategy
	 * @param string|null   $charset
	 * @param bool          $autoescape
	 *
	 * @return false|string|string[]|null
	 * @throws Error
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since
	 */
	public final function onFilterEscape(Cappuccino $cappuccino, $string, $strategy = 'html', ?string $charset = null, bool $autoescape = false)
	{
		if ($autoescape && $string instanceof Markup)
			return $string;

		if (!is_string($string))
		{
			if (is_object($string) && method_exists($string, '__toString'))
			{
				if ($autoescape)
				{
					$c = get_class($string);
					$ext = $cappuccino->getExtension(EscaperExtension::class);

					if (!isset($ext->safeClasses[$c]))
					{
						$ext->safeClasses[$c] = [];

						foreach (class_parents($string) + class_implements($string) as $class)
						{
							if (!isset($ext->safeClasses[$class]))
								continue;

							$ext->safeClasses[$c] = array_unique(array_merge($ext->safeClasses[$c], $ext->safeClasses[$class]));

							foreach ($ext->safeClasses[$class] as $s)
								$ext->safeLookup[$s][$c] = true;
						}
					}

					if (isset($ext->safeLookup[$strategy][$c]) || isset($ext->safeLookup['all'][$c]))
						return (string)$string;
				}

				$string = (string)$string;
			}
			else if (in_array($strategy, ['html', 'js', 'css', 'html_attr', 'url']))
			{
				return $string;
			}
		}

		if ($string === '')
			return '';

		if ($charset === null)
			$charset = $cappuccino->getCharset();

		switch ($strategy)
		{
			case 'html':
				static $htmlspecialcharsCharsets = [
					'ISO-8859-1' => true, 'ISO8859-1' => true,
					'ISO-8859-15' => true, 'ISO8859-15' => true,
					'utf-8' => true, 'UTF-8' => true,
					'CP866' => true, 'IBM866' => true, '866' => true,
					'CP1251' => true, 'WINDOWS-1251' => true, 'WIN-1251' => true,
					'1251' => true,
					'CP1252' => true, 'WINDOWS-1252' => true, '1252' => true,
					'KOI8-R' => true, 'KOI8-RU' => true, 'KOI8R' => true,
					'BIG5' => true, '950' => true,
					'GB2312' => true, '936' => true,
					'BIG5-HKSCS' => true,
					'SHIFT_JIS' => true, 'SJIS' => true, '932' => true,
					'EUC-JP' => true, 'EUCJP' => true,
					'ISO8859-5' => true, 'ISO-8859-5' => true, 'MACROMAN' => true,
				];

				if (isset($htmlspecialcharsCharsets[$charset]))
					return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);

				if (isset($htmlspecialcharsCharsets[strtoupper($charset)]))
				{
					$htmlspecialcharsCharsets[$charset] = true;

					return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
				}

				$string = iconv($charset, 'UTF-8', $string);
				$string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

				return iconv('UTF-8', $charset, $string);

			case 'js':
				if ($charset !== 'UTF-8')
					$string = iconv($charset, 'UTF-8', $string);

				if (!preg_match('//u', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9,._]#Su', function ($matches)
				{
					$char = $matches[0];

					static $shortMap = [
						'\\' => '\\\\',
						'/' => '\\/',
						"\x08" => '\b',
						"\x0C" => '\f',
						"\x0A" => '\n',
						"\x0D" => '\r',
						"\x09" => '\t',
					];

					if (isset($shortMap[$char]))
						return $shortMap[$char];

					$char = StaticMethods::convertEncoding($char, 'UTF-16BE', 'UTF-8');
					$char = strtoupper(bin2hex($char));

					if (strlen($char) <= 4)
						return sprintf('\u%04s', $char);

					return sprintf('\u%04s\u%04s', substr($char, 0, -4), substr($char, -4));
				}, $string);

				if ($charset !== 'UTF-8')
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'css':
				if ($charset !== 'UTF-8')
					$string = iconv($charset, 'UTF-8', $string);

				if (!preg_match('//u', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9]#Su', function ($matches)
				{
					$char = $matches[0];

					return sprintf('\\%X ', strlen($char) === 1 ? ord($char) : mb_ord($char, 'UTF-8'));
				}, $string);

				if ($charset !== 'UTF-8')
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'html_attr':
				if ($charset !== 'UTF-8')
					$string = iconv($charset, 'UTF-8', $string);

				if (!preg_match('//u', $string))
					throw new RuntimeError('The string to escape is not a valid UTF-8 string.');

				$string = preg_replace_callback('#[^a-zA-Z0-9,.\-_]#Su', function ($matches)
				{
					$chr = $matches[0];
					$ord = ord($chr);

					if (($ord <= 0x1f && "\t" != $chr && "\n" != $chr && "\r" != $chr) || ($ord >= 0x7f && $ord <= 0x9f))
						return '&#xFFFD;';

					if (strlen($chr) === 1)
					{
						static $entityMap = [
							34 => '&quot;',
							38 => '&amp;',
							60 => '&lt;',
							62 => '&gt;',
						];

						if (isset($entityMap[$ord]))
							return $entityMap[$ord];

						return sprintf('&#x%02X;', $ord);
					}

					return sprintf('&#x%04X;', mb_ord($chr, 'UTF-8'));
				}, $string);

				if ($charset !== 'UTF-8')
					$string = iconv('UTF-8', $charset, $string);

				return $string;

			case 'url':
				return rawurlencode($string);

			default:
				static $escapers;

				if ($escapers === null)
					$escapers = $cappuccino->getExtension(EscaperExtension::class)->getEscapers();

				if (isset($escapers[$strategy]))
					return $escapers[$strategy]($cappuccino, $string, $charset);

				$validStrategies = implode(', ', array_merge(['html', 'js', 'url', 'css', 'html_attr'], array_keys($escapers)));

				throw new RuntimeError(sprintf('Invalid escaping strategy "%s" (valid ones: %s).', $strategy, $validStrategies));
		}
	}

	/**
	 * Marks a variable as being safe.
	 *
	 * @param mixed $var
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterRaw($var)
	{
		return $var;
	}

	/**
	 * Marks a filter as safe.
	 *
	 * @param Node $filterArgs
	 *
	 * @return array
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 * @internal
	 */
	public final function onFilterIsSafe(Node $filterArgs): array
	{
		foreach ($filterArgs as $arg)
		{
			if ($arg instanceof ConstantExpression)
				return [$arg->getAttribute('value')];

			return [];
		}

		return ['html'];
	}

}
