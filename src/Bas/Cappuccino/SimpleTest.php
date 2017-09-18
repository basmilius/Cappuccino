<?php
declare(strict_types=1);

namespace Bas\Cappuccino;

use Bas\Cappuccino\Node\Expression\Test\TestExpression;

class SimpleTest
{

	private $name;
	private $callable;
	private $options;

	/**
	 * Creates a template test.
	 *
	 * @param string        $name Name of this test
	 * @param callable|null $callable A callable implementing the test. If null, you need to overwrite the "node_class" option to customize compilation.
	 * @param array         $options Options array
	 */
	public function __construct (string $name, $callable = null, array $options = [])
	{
		if (__CLASS__ !== get_class($this))
		{
			@trigger_error('Overriding ' . __CLASS__ . ' is deprecated since version 2.4.0 and the class will be final in 3.0.', E_USER_DEPRECATED);
		}

		$this->name = $name;
		$this->callable = $callable;
		$this->options = array_merge([
			'is_variadic' => false,
			'node_class' => TestExpression::class,
			'deprecated' => false,
			'alternative' => null,
		], $options);
	}

	public function getName ()
	{
		return $this->name;
	}

	/**
	 * Returns the callable to execute for this test.
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
