<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Node;

use Bas\Cappuccino\Compiler;
use Bas\Cappuccino\Markup;
use Bas\Cappuccino\Node\Expression\ConstantExpression;

/**
 * Class SetNode
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Node
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
	 * @param int         $lineno
	 * @param null|string $tag
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (bool $capture, Node $names, Node $values, int $lineno, ?string $tag = null)
	{
		parent::__construct(['names' => $names, 'values' => $values], ['capture' => $capture, 'safe' => false], $lineno, $tag);

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
	public function compile (Compiler $compiler): void
	{
		$classMarkup = Markup::class;

		$compiler->addDebugInfo($this);

		if (count($this->getNode('names')) > 1)
		{
			$compiler->write('list(');
			foreach ($this->getNode('names') as $idx => $node)
			{
				if ($idx)
				{
					$compiler->raw(', ');
				}

				$compiler->subcompile($node);
			}
			$compiler->raw(')');
		}
		else
		{
			if ($this->getAttribute('capture'))
			{
				$compiler
					->write("ob_start();\n")
					->subcompile($this->getNode('values'));
			}

			$compiler->subcompile($this->getNode('names'), false);

			if ($this->getAttribute('capture'))
			{
				$compiler->raw(" = ('' === \$tmp = ob_get_clean()) ? '' : new " . $classMarkup . "(\$tmp, \$this->cappuccino->getCharset())");
			}
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
					{
						$compiler->raw(', ');
					}

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
						->raw(") ? '' : new " . $classMarkup . "(\$tmp, \$this->cappuccino->getCharset())");
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
