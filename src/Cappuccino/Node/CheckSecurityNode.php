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

use Cappuccino\Compiler;
use Cappuccino\Extension\SandboxExtension;
use Cappuccino\Sandbox\SecurityError;
use Cappuccino\Sandbox\SecurityNotAllowedFilterError;
use Cappuccino\Sandbox\SecurityNotAllowedFunctionError;
use Cappuccino\Sandbox\SecurityNotAllowedTagError;

/**
 * Class CheckSecurityNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class CheckSecurityNode extends Node
{

	private $usedFilters;
	private $usedTags;
	private $usedFunctions;

	/**
	 * CheckSecurityNode constructor.
	 *
	 * @param array $usedFilters
	 * @param array $usedTags
	 * @param array $usedFunctions
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (array $usedFilters, array $usedTags, array $usedFunctions)
	{
		$this->usedFilters = $usedFilters;
		$this->usedTags = $usedTags;
		$this->usedFunctions = $usedFunctions;

		parent::__construct();
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile (Compiler $compiler): void
	{
		$tags = $filters = $functions = [];

		foreach ($this->usedTags as $name => $node)
		{
			if ($node instanceof Node)
				$tags[$name] = $node->getTemplateLine();
			else
				$tags[$node] = null;
		}

		foreach ($this->usedFilters as $name => $node)
		{
			if ($node instanceof Node)
				$tags[$name] = $node->getTemplateLine();
			else
				$tags[$node] = null;
		}

		foreach ($this->usedFunctions as $name => $node)
		{
			if ($node instanceof Node)
				$tags[$name] = $node->getTemplateLine();
			else
				$tags[$node] = null;
		}

		$classSandboxExtension = SandboxExtension::class;
		$classSandboxSecurityError = SecurityError::class;
		$classSandboxSecurityNotAllowedTagError = SecurityNotAllowedTagError::class;
		$classSandboxSecurityNotAllowedFilterError = SecurityNotAllowedFilterError::class;
		$classSandboxSecurityNotAllowedFunctionError = SecurityNotAllowedFunctionError::class;

		$compiler
			->write('$tags = ')->repr(array_filter($tags))->raw(";\n")
			->write('$filters = ')->repr(array_filter($filters))->raw(";\n")
			->write('$functions = ')->repr(array_filter($functions))->raw(";\n\n")
			->write("try {\n")
			->indent()
			->write("\$this->extensions['" . $classSandboxExtension . "']->checkSecurity(\n")
			->indent()
			->write(!$tags ? "[],\n" : "['" . implode("', '", array_keys($tags)) . "'],\n")
			->write(!$filters ? "[],\n" : "['" . implode("', '", array_keys($filters)) . "'],\n")
			->write(!$functions ? "[]\n" : "['" . implode("', '", array_keys($functions)) . "']\n")
			->outdent()
			->write(");\n")
			->outdent()
			->write("} catch (" . $classSandboxSecurityError . " \$e) {\n")
			->indent()
			->write("\$e->setSourceContext(\$this->source);\n\n")
			->write("if (\$e instanceof " . $classSandboxSecurityNotAllowedTagError . " && isset(\$tags[\$e->getTagName()])) {\n")
			->indent()
			->write("\$e->setTemplateLine(\$tags[\$e->getTagName()]);\n")
			->outdent()
			->write("} elseif (\$e instanceof " . $classSandboxSecurityNotAllowedFilterError . " && isset(\$filters[\$e->getFilterName()])) {\n")
			->indent()
			->write("\$e->setTemplateLine(\$filters[\$e->getFilterName()]);\n")
			->outdent()
			->write("} elseif (\$e instanceof " . $classSandboxSecurityNotAllowedFunctionError . " && isset(\$functions[\$e->getFunctionName()])) {\n")
			->indent()
			->write("\$e->setTemplateLine(\$functions[\$e->getFunctionName()]);\n")
			->outdent()
			->write("}\n\n")
			->write("throw \$e;\n")
			->outdent()
			->write("}\n\n");
	}

}
