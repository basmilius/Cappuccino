<?php
declare(strict_types=1);

namespace Bas\Cappuccino\NodeVisitor;

use Bas\Cappuccino\Environment;
use Bas\Cappuccino\Extension\EscaperExtension;
use Bas\Cappuccino\Node\AutoEscapeNode;
use Bas\Cappuccino\Node\BlockNode;
use Bas\Cappuccino\Node\BlockReferenceNode;
use Bas\Cappuccino\Node\Expression\ConstantExpression;
use Bas\Cappuccino\Node\Expression\FilterExpression;
use Bas\Cappuccino\Node\ImportNode;
use Bas\Cappuccino\Node\ModuleNode;
use Bas\Cappuccino\Node\Node;
use Bas\Cappuccino\Node\PrintNode;
use Bas\Cappuccino\NodeTraverser;

/**
 * Class EscaperNodeVisitor
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\NodeVisitor
 * @version 2.3.0
 */
final class EscaperNodeVisitor extends AbstractNodeVisitor
{

	private $statusStack = [];
	private $blocks = [];
	private $safeAnalysis;
	private $traverser;
	private $defaultStrategy = false;
	private $safeVars = [];

	/**
	 * EscaperNodeVisitor constructor.
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct ()
	{
		$this->safeAnalysis = new SafeAnalysisNodeVisitor();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	protected function doEnterNode (Node $node, Environment $environment) : Node
	{
		if ($node instanceof ModuleNode)
		{
			if ($environment->hasExtension(EscaperExtension::class))
			{
				/** @var EscaperExtension $ext */
				$ext = $environment->getExtension(EscaperExtension::class);

				$this->defaultStrategy = $ext->getDefaultStrategy($node->getTemplateName());
			}

			$this->safeVars = [];
			$this->blocks = [];
		}
		else if ($node instanceof AutoEscapeNode)
		{
			$this->statusStack[] = $node->getAttribute('value');
		}
		else if ($node instanceof BlockNode)
		{
			$this->statusStack[] = isset($this->blocks[$node->getAttribute('name')]) ? $this->blocks[$node->getAttribute('name')] : $this->needEscaping();
		}
		else if ($node instanceof ImportNode)
		{
			$this->safeVars[] = $node->getNode('var')->getAttribute('name');
		}

		return $node;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	protected function doLeaveNode (Node $node, Environment $env) : Node
	{
		if ($node instanceof ModuleNode)
		{
			$this->defaultStrategy = false;
			$this->safeVars = [];
			$this->blocks = [];
		}
		else if ($node instanceof FilterExpression)
		{
			return $this->preEscapeFilterNode($node, $env);
		}
		else if ($node instanceof PrintNode)
		{
			return $this->escapePrintNode($node, $env, $this->needEscaping());
		}

		if ($node instanceof AutoEscapeNode || $node instanceof BlockNode)
			array_pop($this->statusStack);
		else if ($node instanceof BlockReferenceNode)
			$this->blocks[$node->getAttribute('name')] = $this->needEscaping();

		return $node;
	}

	/**
	 * Escape Print Node.
	 *
	 * @param PrintNode   $node
	 * @param Environment $env
	 * @param string      $type
	 *
	 * @return Node
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	private function escapePrintNode (PrintNode $node, Environment $env, string $type) : Node
	{
		if (false === $type)
			return $node;

		$expression = $node->getNode('expr');

		if ($this->isSafeFor($type, $expression, $env))
			return $node;

		$class = get_class($node);

		return new $class(
			$this->getEscaperFilter($type, $expression),
			$node->getTemplateLine()
		);
	}

	/**
	 * Pre-escapes the filter node.
	 *
	 * @param FilterExpression $filter
	 * @param Environment      $env
	 *
	 * @return FilterExpression
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	private function preEscapeFilterNode (FilterExpression $filter, Environment $env) : FilterExpression
	{
		$name = $filter->getNode('filter')->getAttribute('value');
		$type = $env->getFilter($name)->getPreEscape();

		if (null === $type)
			return $filter;

		$node = $filter->getNode('node');

		if ($this->isSafeFor($type, $node, $env))
			return $filter;

		$filter->setNode('node', $this->getEscaperFilter($type, $node));

		return $filter;
	}

	/**
	 * Returns TRUE if it's save.
	 *
	 * @param string      $type
	 * @param Node        $expression
	 * @param Environment $env
	 *
	 * @return bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	private function isSafeFor (string $type, Node $expression, Environment $env) : bool
	{
		$safe = $this->safeAnalysis->getSafe($expression);

		if ($safe === null)
		{
			if ($this->traverser === null)
				$this->traverser = new NodeTraverser($env, [$this->safeAnalysis]);

			$this->safeAnalysis->setSafeVars($this->safeVars);

			$this->traverser->traverse($expression);
			$safe = $this->safeAnalysis->getSafe($expression);
		}

		return in_array($type, $safe) || in_array('all', $safe);
	}

	/**
	 * Returns TRUE if escaping is needed.
	 *
	 * @return string|bool
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	private function needEscaping ()
	{
		if (count($this->statusStack))
			return $this->statusStack[count($this->statusStack) - 1];

		return $this->defaultStrategy ? $this->defaultStrategy : false;
	}

	/**
	 * Gets escaper filter.
	 *
	 * @param string $type
	 * @param Node   $node
	 *
	 * @return FilterExpression
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	private function getEscaperFilter (string $type, Node $node)
	{
		$line = $node->getTemplateLine();
		$name = new ConstantExpression('escape', $line);
		$args = new Node([new ConstantExpression((string)$type, $line), new ConstantExpression(null, $line), new ConstantExpression(true, $line)]);

		return new FilterExpression($node, $name, $args, $line);
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getPriority () : int
	{
		return 0;
	}

}
