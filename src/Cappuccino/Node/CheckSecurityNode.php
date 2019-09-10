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

use Cappuccino\Compiler;

/**
 * Class CheckSecurityNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class CheckSecurityNode extends Node
{

	/**
	 * @var array
	 */
	private $usedFilters;

	/**
	 * @var array
	 */
	private $usedTags;

	/**
	 * @var array
	 */
	private $usedFunctions;

	/**
	 * CheckSecurityNode constructor.
	 *
	 * @param array $usedFilters
	 * @param array $usedTags
	 * @param array $usedFunctions
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(array $usedFilters, array $usedTags, array $usedFunctions)
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
	public function compile(Compiler $compiler): void
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
				$filters[$name] = $node->getTemplateLine();
			else
				$filters[$node] = null;
		}

		foreach ($this->usedFunctions as $name => $node)
		{
			if ($node instanceof Node)
				$functions[$name] = $node->getTemplateLine();
			else
				$functions[$node] = null;
		}

		$compiler
			->write("\$this->sandbox = \$this->cappuccino->getExtension('\Cappuccino\Extension\SandboxExtension');\n")
			->write('$tags = ')->repr(array_filter($tags))->raw(";\n")
			->write('$filters = ')->repr(array_filter($filters))->raw(";\n")
			->write('$functions = ')->repr(array_filter($functions))->raw(";\n\n")
			->write("try {\n")
			->indent()
			->write("\$this->sandbox->checkSecurity(\n")
			->indent()
			->write(!$tags ? "[],\n" : "['" . implode("', '", array_keys($tags)) . "'],\n")
			->write(!$filters ? "[],\n" : "['" . implode("', '", array_keys($filters)) . "'],\n")
			->write(!$functions ? "[]\n" : "['" . implode("', '", array_keys($functions)) . "']\n")
			->outdent()
			->write(");\n")
			->outdent()
			->write("} catch (SecurityError \$e) {\n")
			->indent()
			->write("\$e->setSourceContext(\$this->source);\n\n")
			->write("if (\$e instanceof SecurityNotAllowedTagError && isset(\$tags[\$e->getTagName()])) {\n")
			->indent()
			->write("\$e->setTemplateLine(\$tags[\$e->getTagName()]);\n")
			->outdent()
			->write("} elseif (\$e instanceof SecurityNotAllowedFilterError && isset(\$filters[\$e->getFilterName()])) {\n")
			->indent()
			->write("\$e->setTemplateLine(\$filters[\$e->getFilterName()]);\n")
			->outdent()
			->write("} elseif (\$e instanceof SecurityNotAllowedFunctionError && isset(\$functions[\$e->getFunctionName()])) {\n")
			->indent()
			->write("\$e->setTemplateLine(\$functions[\$e->getFunctionName()]);\n")
			->outdent()
			->write("}\n\n")
			->write("throw \$e;\n")
			->outdent()
			->write("}\n\n");
	}

}
