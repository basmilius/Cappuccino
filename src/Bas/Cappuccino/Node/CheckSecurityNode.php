<?php
/**
 * Copyright (c) 2018 - Bas Milius <bas@mili.us>.
 *
 * This file is part of the Bas\Cappuccino package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Extension\SandboxExtension;
use Bas\Cappuccino\Sandbox\SecurityError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedFilterError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedFunctionError;
use Bas\Cappuccino\Sandbox\SecurityNotAllowedTagError;

/**
 * Class CheckSecurityNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
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

		foreach (['tags', 'filters', 'functions'] as $type)
		{
			foreach ($this->{'used' . ucfirst($type)} as $name => $node)
			{
				if ($node instanceof Node)
				{
					/** @noinspection PhpVariableVariableInspection */
					${$type}[$name] = $node->getTemplateLine();
				}
				else
				{
					/** @noinspection PhpVariableVariableInspection */
					${$type}[$node] = null;
				}
			}
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
			->write("\$this->cappuccino->getExtension('" . $classSandboxExtension . "')->checkSecurity(\n")
			->indent()
			->write(!$tags ? "array(),\n" : "array('" . implode("', '", array_keys($tags)) . "'),\n")
			->write(!$filters ? "array(),\n" : "array('" . implode("', '", array_keys($filters)) . "'),\n")
			->write(!$functions ? "array()\n" : "array('" . implode("', '", array_keys($functions)) . "')\n")
			->outdent()
			->write(");\n")
			->outdent()
			->write("} catch (" . $classSandboxSecurityError . " \$e) {\n")
			->indent()
			->write("\$e->setSourceContext(\$this->getSourceContext());\n\n")
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
