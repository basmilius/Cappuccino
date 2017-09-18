<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Node\Expression\FunctionExpression;
use Bas\Cappuccino\Node\Node;

/**
 * Class SimpleFunction
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino
 * @since 2.3.0
 */
class SimpleFunction
{

	private $name;
	private $callable;
	private $options;
	private $arguments = [];

	/**
	 * SimpleFunction constructor.
	 *
	 * @param string        $name
	 * @param callable|null $callable
	 * @param array         $options
	 */
	public function __construct (string $name, ?callable $callable = null, array $options = [])
	{
		if (__CLASS__ !== get_class($this))
			@trigger_error('Overriding ' . __CLASS__ . ' is deprecated since version 2.4.0 and the class will be final in 3.0.', E_USER_DEPRECATED);

		$this->name = $name;
		$this->callable = $callable;
		$this->options = array_merge([
			'needs_environment' => false,
			'needs_context' => false,
			'is_variadic' => false,
			'is_safe' => null,
			'is_safe_callback' => null,
			'node_class' => FunctionExpression::class,
			'deprecated' => false,
			'alternative' => null,
		], $options);
	}

	public function getName ()
	{
		return $this->name;
	}

	/**
	 * Returns the callable to execute for this function.
	 *
	 * @return callable|null
	 */
	public function getCallable ()
	{
		return $this->callable;
	}

	public function getNodeClass ()
	{
		return $this->options['node_class'];
	}

	public function setArguments ($arguments)
	{
		$this->arguments = $arguments;
	}

	public function getArguments ()
	{
		return $this->arguments;
	}

	public function needsEnvironment ()
	{
		return $this->options['needs_environment'];
	}

	public function needsContext ()
	{
		return $this->options['needs_context'];
	}

	public function getSafe (Node $functionArgs)
	{
		if (null !== $this->options['is_safe'])
		{
			return $this->options['is_safe'];
		}

		if (null !== $this->options['is_safe_callback'])
		{
			return $this->options['is_safe_callback']($functionArgs);
		}

		return [];
	}

	public function isVariadic ()
	{
		return $this->options['is_variadic'];
	}

	public function isDeprecated ()
	{
		return (bool)$this->options['deprecated'];
	}

	public function getDeprecatedVersion ()
	{
		return $this->options['deprecated'];
	}

	public function getAlternative ()
	{
		return $this->options['alternative'];
	}

}
