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

namespace Cappuccino\Error;

use Cappuccino\Source;
use Cappuccino\Template;
use Exception;
use ReflectionObject;

/**
 * Class Error
 *
 * @author Bas Milius <bas@mili.us>
 * @package Cappuccino\Error
 * @since 1.0.0
 */
class Error extends Exception
{

	private $lineno;
	private $name;
	private $rawMessage;
	private $sourcePath;
	private $sourceCode;

	/**
	 * Error constructor.
	 *
	 * @param string         $message
	 * @param int            $lineno
	 * @param Source|null    $source
	 * @param Exception|null $previous
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct (string $message, int $lineno = -1, ?Source $source = null, ?Exception $previous = null)
	{
		parent::__construct('', 0, $previous);

		if (null === $source)
		{
			$name = null;
		}
		else if (!$source instanceof Source)
		{
			$name = $source;
		}
		else
		{
			$name = $source->getName();
			$this->sourceCode = $source->getCode();
			$this->sourcePath = $source->getPath();
		}

		$this->lineno = $lineno;
		$this->name = $name;

		if (-1 === $lineno || null === $name || null === $this->sourcePath)
		{
			$this->guessTemplateInfo();
		}

		$this->rawMessage = $message;

		$this->updateRepr();
	}

	/**
	 * Gets the raw message.
	 *
	 * @return string The raw message
	 */
	public function getRawMessage ()
	{
		return $this->rawMessage;
	}

	/**
	 * Gets the template line where the error occurred.
	 *
	 * @return int The template line
	 */
	public function getTemplateLine ()
	{
		return $this->lineno;
	}

	/**
	 * Sets the template line where the error occurred.
	 *
	 * @param int $lineno The template line
	 */
	public function setTemplateLine ($lineno)
	{
		$this->lineno = $lineno;

		$this->updateRepr();
	}

	/**
	 * Gets the source context of the Cappuccino template where the error occurred.
	 *
	 * @return Source|null
	 */
	public function getSourceContext ()
	{
		return $this->name ? new Source($this->sourceCode, $this->name, $this->sourcePath) : null;
	}

	/**
	 * Sets the source context of the Cappuccino template where the error occurred.
	 *
	 * @param Source|null $source
	 */
	public function setSourceContext (Source $source = null)
	{
		if (null === $source)
		{
			$this->sourceCode = $this->name = $this->sourcePath = null;
		}
		else
		{
			$this->sourceCode = $source->getCode();
			$this->name = $source->getName();
			$this->sourcePath = $source->getPath();
		}

		$this->updateRepr();
	}

	public function guess ()
	{
		$this->guessTemplateInfo();
		$this->updateRepr();
	}

	public function appendMessage ($rawMessage)
	{
		$this->rawMessage .= $rawMessage;
		$this->updateRepr();
	}

	private function updateRepr ()
	{
		$this->message = $this->rawMessage;

		if ($this->sourcePath && $this->lineno > 0)
		{
			$this->file = $this->sourcePath;
			$this->line = $this->lineno;

			return;
		}

		$dot = false;
		if ('.' === substr($this->message, -1))
		{
			$this->message = substr($this->message, 0, -1);
			$dot = true;
		}

		$questionMark = false;
		if ('?' === substr($this->message, -1))
		{
			$this->message = substr($this->message, 0, -1);
			$questionMark = true;
		}

		if ($this->name)
		{
			if (is_string($this->name) || (is_object($this->name) && method_exists($this->name, '__toString')))
			{
				$name = sprintf('"%s"', $this->name);
			}
			else
			{
				$name = json_encode($this->name);
			}
			$this->message .= sprintf(' in %s', $name);
		}

		if ($this->lineno && $this->lineno >= 0)
		{
			$this->message .= sprintf(' at line %d', $this->lineno);
		}

		if ($dot)
		{
			$this->message .= '.';
		}

		if ($questionMark)
		{
			$this->message .= '?';
		}
	}

	private function guessTemplateInfo ()
	{
		$template = null;
		$templateClass = null;
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);

		foreach ($backtrace as $trace)
		{
			/** @var Template $traceObject */
			$traceObject = $trace['object'] ?? [];

			if (isset($traceObject) && $traceObject instanceof Template && 'Template' !== get_class($traceObject))
			{
				$currentClass = get_class($traceObject);
				$isEmbedContainer = 0 === strpos($templateClass ?? '', $currentClass);

				if ($this->name === null || ($this->name == $traceObject->getTemplateName() && !$isEmbedContainer))
				{
					$template = $traceObject;
					$templateClass = get_class($traceObject);
				}
			}
		}

		if ($template !== null && $this->name === null)
			$this->name = $template->getTemplateName();

		if ($template !== null && $this->sourcePath === null)
		{
			$src = $template->getSourceContext();
			$this->sourceCode = $src->getCode();
			$this->sourcePath = $src->getPath();
		}

		if ($template === null || $this->lineno > -1)
			return;

		$r = new ReflectionObject($template);
		$file = $r->getFileName();
		$exceptions = [$e = $this];

		while ($e = $e->getPrevious())
			$exceptions[] = $e;

		while ($e = array_pop($exceptions))
		{
			$traces = $e->getTrace();
			array_unshift($traces, ['file' => $e->getFile(), 'line' => $e->getLine()]);

			while ($trace = array_shift($traces))
			{
				if (!isset($trace['file']) || !isset($trace['line']) || $file != $trace['file'])
					continue;

				foreach ($template->getDebugInfo() as $codeLine => $templateLine)
				{
					if ($codeLine <= $trace['line'])
					{
						$this->lineno = $templateLine;

						return;
					}
				}
			}
		}
	}

}