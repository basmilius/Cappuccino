<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\FileExtensionEscapingStrategy;
use Bas\Cappuccino\NodeVisitor\EscaperNodeVisitor;
use Bas\Cappuccino\SimpleFilter;
use Bas\Cappuccino\TokenParser\AutoEscapeTokenParser;

/**
 * Class EscaperExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Extension
 * @version 2.3.0
 */
final class EscaperExtension extends AbstractExtension
{

	/**
	 * @var mixed
	 */
	private $defaultStrategy;

	/**
	 * EscaperExtension constructor.
	 *
	 * @param mixed $defaultStrategy
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct ($defaultStrategy = 'html')
	{
		$this->setDefaultStrategy($defaultStrategy);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getTokenParsers () : array
	{
		return [new AutoEscapeTokenParser()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getNodeVisitors () : array
	{
		return [new EscaperNodeVisitor()];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getFilters () : array
	{
		return [
			new SimpleFilter('raw', [$this, 'onSimpleFilterRaw'], ['is_safe' => ['all']]),
		];
	}

	/**
	 * Sets the default strategy to use when not defined by the user. The strategy can be a valid PHP callback that takes
	 * the template name as an argument and returns the strategy to use.
	 *
	 * @param mixed $defaultStrategy
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function setDefaultStrategy ($defaultStrategy) : void
	{
		if ($defaultStrategy === 'name')
			$defaultStrategy = [FileExtensionEscapingStrategy::class, 'guess'];

		$this->defaultStrategy = $defaultStrategy;
	}

	/**
	 * Gets the default strategy to use when not defined by the user.
	 *
	 * @param string $name
	 *
	 * @return string|false
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getDefaultStrategy (string $name)
	{
		if (!is_string($this->defaultStrategy) && $this->defaultStrategy)
			return call_user_func($this->defaultStrategy, $name);

		return $this->defaultStrategy;
	}

	/**
	 * Marks a variable as being safe.
	 *
	 * @param string $str
	 *
	 * @return string
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 * @internal
	 */
	public final function onSimpleFilterRaw (string $str) : string
	{
		return $str;
	}

}
