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

use ArrayIterator;
use Cappuccino\Compiler;
use Cappuccino\Source;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use LogicException;
use Traversable;

/**
 * Class Node
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class Node implements Countable, IteratorAggregate
{

	/**
	 * @var Node[]
	 */
	protected $nodes;

	/**
	 * @var array
	 */
	protected $attributes;

	/**
	 * @var int
	 */
	protected $lineNumber;

	/**
	 * @var string|null
	 */
	protected $tag;

	/**
	 * @var Source
	 */
	private $sourceContext;

	/**
	 * Node constructor.
	 *
	 * @param array       $nodes
	 * @param array       $attributes
	 * @param int         $lineNumber
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(array $nodes = [], array $attributes = [], int $lineNumber = 0, ?string $tag = null)
	{
		foreach ($nodes as $name => $node)
			if (!$node instanceof self)
				throw new InvalidArgumentException(sprintf('Using "%s" for the value of node "%s" of "%s" is not supported. You must pass a \Cappuccino\Node\Node instance.', is_object($node) ? get_class($node) : (null === $node ? 'null' : gettype($node)), $name, get_class($this)));

		$this->nodes = $nodes;
		$this->attributes = $attributes;
		$this->lineNumber = $lineNumber;
		$this->tag = $tag;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		foreach ($this->nodes as $node)
			$node->compile($compiler);
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
		return $this->lineNumber;
	}

	/**
	 * Gets a node tag.
	 *
	 * @return string|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeTag(): ?string
	{
		return $this->tag;
	}

	/**
	 * Checks if an attribute exists.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function hasAttribute(string $name): bool
	{
		return array_key_exists($name, $this->attributes);
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
		if (!array_key_exists($name, $this->attributes))
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
	public function removeAttribute(string $name): void
	{
		unset($this->attributes[$name]);
	}

	/**
	 * Checks if a node exists.
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function hasNode($name): bool // NOTE(Bas) $name was string
	{
		return isset($this->nodes[$name]);
	}

	/**
	 * Gets a node by name.
	 *
	 * @param string $name
	 *
	 * @return self
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNode($name): self
	{
		if (!isset($this->nodes[$name]))
			throw new LogicException(sprintf('Node "%s" does not exist for Node "%s".', $name, get_class($this)));

		return $this->nodes[$name];
	}

	/**
	 * Sets a node by name.
	 *
	 * @param string $name
	 * @param Node   $node
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setNode(string $name, self $node): void
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
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->nodes);
	}

	/**
	 * Gets the template name.
	 *
	 * @return string|null
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getTemplateName(): ?string
	{
		return $this->sourceContext ? $this->sourceContext->getName() : null;
	}

	/**
	 * Sets the {@see Source}.
	 *
	 * @param Source $source
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function setSourceContext(Source $source): void
	{
		$this->sourceContext = $source;

		foreach ($this->nodes as $node)
			$node->setSourceContext($source);
	}

	/**
	 * Gets the {@see Source}.
	 *
	 * @return Source|null
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function getSourceContext(): ?Source
	{
		return $this->sourceContext;
	}

	/**
	 * {@inheritdoc}
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
