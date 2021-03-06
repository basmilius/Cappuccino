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
use Cappuccino\Node\Expression\ConstantExpression;
use function count;

/**
 * Class SetNode
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Cappuccino\Node
 * @since 1.0.0
 */
class SetNode extends Node implements NodeCaptureInterface
{

	/**
	 * SetNode constructor.
	 *
	 * @param bool        $capture
	 * @param Node        $names
	 * @param Node        $values
	 * @param int         $lineNumber
	 * @param string|null $tag
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 1.0.0
	 */
	public function __construct(bool $capture, Node $names, Node $values, int $lineNumber, ?string $tag = null)
	{
		parent::__construct(['names' => $names, 'values' => $values], ['capture' => $capture, 'safe' => false], $lineNumber, $tag);

		if ($this->getAttribute('capture'))
		{
			$this->setAttribute('safe', true);

			$values = $this->getNode('values');

			if ($values instanceof TextNode)
			{
				$this->setNode('values', new ConstantExpression($values->getAttribute('data'), $values->getTemplateLine()));
				$this->setAttribute('capture', false);
			}
		}
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function compile(Compiler $compiler): void
	{
		$compiler->addDebugInfo($this);

		if (count($this->getNode('names')) > 1)
		{
			$compiler->write('list(');

			foreach ($this->getNode('names') as $idx => $node)
			{
				if ($idx)
					$compiler->raw(', ');

				$compiler->subcompile($node);
			}

			$compiler->raw(')');
		}
		else
		{
			if ($this->getAttribute('capture'))
			{
				if ($compiler->getCappuccino()->isDebug())
					$compiler->write("ob_start();\n");
				else
					$compiler->write("ob_start(function () { return ''; });\n");

				$compiler
					->subcompile($this->getNode('values'));
			}

			$compiler->subcompile($this->getNode('names'), false);

			if ($this->getAttribute('capture'))
				$compiler->raw(" = ('' === \$tmp = ob_get_clean()) ? '' : new Markup(\$tmp, \$this->cappuccino->getCharset())");
		}

		if (!$this->getAttribute('capture'))
		{
			$compiler->raw(' = ');

			if (count($this->getNode('names')) > 1)
			{
				$compiler->write('[');

				foreach ($this->getNode('values') as $idx => $value)
				{
					if ($idx)
						$compiler->raw(', ');

					$compiler->subcompile($value);
				}

				$compiler->raw(']');
			}
			else
			{
				if ($this->getAttribute('safe'))
				{
					$compiler
						->raw("('' === \$tmp = ")
						->subcompile($this->getNode('values'))
						->raw(") ? '' : new Markup(\$tmp, \$this->cappuccino->getCharset())");
				}
				else
				{
					$compiler->subcompile($this->getNode('values'));
				}
			}
		}

		$compiler->raw(";\n");
	}

}
