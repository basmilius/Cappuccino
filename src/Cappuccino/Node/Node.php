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

namespace Cappuccino\Node;

use ArrayIterator;
use Cappuccino\Compiler;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;

/**
 * Class Node
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino
 * @since 1.0.0
 */
class Node implements Countable, IteratorAggregate
{

	protected $nodes;
	protected $attributes;
	protected $lineno;
	protected $tag;

	private $name;

	/**
	 * Node constructor.
	 *
	 * @param Node[]      $nodes
	 * @param array       $attributes
	 * @param int         $lineno
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(array $nodes = [], array $attributes = [], int $lineno = 0, ?string $tag = null)
	{
		foreach ($nodes as $name => $node)
			if (!$node instanceof self)
				throw new InvalidArgumentException(sprintf('Using "%s" for the value of node "%s" of "%s" is not supported. You must pass a Node instance.', is_object($node) ? get_class($node) : null === $node ? 'null' : gettype($node), $name, get_class($this)));

		$this->nodes = $nodes;
		$this->attributes = $attributes;
		$this->lineno = $lineno;
		$this->tag = $tag;
	}

	/**
	 * Compiles the node.
	 *
	 * @param Compiler $compiler
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		foreach ($this->nodes as $node)
		{
			$node->compile($compiler);
		}
	}

	/**
	 * Gets the line number.
	 *
	 * @return int
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTemplateLine(): int
	{
		return $this->lineno;
	}

	/**
	 * Gets a node tag.
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeTag()
	{
		return $this->tag;
	}

	/**
	 * Checks if an attribute is present.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasAttribute(string $name)
	{
		return isset($this->attributes[$name]);
	}

	/**
	 * Gets an attribute.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getAttribute(string $name)
	{
		if (!$this->hasAttribute($name))
			throw new LogicException(sprintf('Attribute "%s" does not exist for Node "%s".', $name, get_class($this)));

		return $this->attributes[$name];
	}

	/**
	 * Sets an attribute.
	 *
	 * @param string $name
	 * @param mixed  $value
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setAttribute(string $name, $value): void
	{
		$this->attributes[$name] = $value;
	}

	/**
	 * Removes an attribute.
	 *
	 * @param string $name
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function removeAttribute($name): void
	{
		unset($this->attributes[$name]);
	}

	/**
	 * Checks if a node is present.
	 *
	 * @param string|int $name
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasNode($name): bool
	{
		return isset($this->nodes[$name]);
	}

	/**
	 * Gets a node.
	 *
	 * @param string $name
	 *
	 * @return Node|Node[]
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNode($name)
	{
		if (!isset($this->nodes[$name]))
			throw new LogicException(sprintf('Node "%s" does not exist for Node "%s".', $name, get_class($this)));

		return $this->nodes[$name];
	}

	/**
	 * Sets a node.
	 *
	 * @param string|int $name
	 * @param Node       $node
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setNode($name, Node $node): void
	{
		$this->nodes[$name] = $node;
	}

	/**
	 * Removes a node by name.
	 *
	 * @param string $name
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function removeNode(string $name): void
	{
		unset($this->nodes[$name]);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function count(): int
	{
		return count($this->nodes);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->nodes);
	}

	/**
	 * Gets the template name.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTemplateName(): string
	{
		return $this->name;
	}

	/**
	 * Sets the template name.
	 *
	 * @param string $name
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setTemplateName(string $name): void
	{
		$this->name = $name;

		foreach ($this->nodes as $node)
			$node->setTemplateName($name);
	}

	/**
	 * toString magic method.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __toString(): string
	{
		$attributes = [];

		foreach ($this->attributes as $name => $value)
			$attributes[] = sprintf('%s: %s', $name, str_replace("\n", '', var_export($value, true)));

		$repr = [get_class($this) . '(' . implode(', ', $attributes)];

		if (count($this->nodes))
		{
			foreach ($this->nodes as $name => $node)
			{
				$len = strlen($name) + 4;
				$noderepr = [];

				foreach (explode("\n", (string)$node) as $line)
					$noderepr[] = str_repeat(' ', $len) . $line;

				$repr[] = sprintf('  %s: %s', $name, ltrim(implode("\n", $noderepr)));
			}

			$repr[] = ')';
		}
		else
		{
			$repr[0] .= ')';
		}

		return implode("\n", $repr);
	}

}
