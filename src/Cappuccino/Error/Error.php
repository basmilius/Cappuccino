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

	/**
	 * @var int
	 */
	private $lineNumber;

	/**
	 * @var string|null
	 */
	private $name;

	/**
	 * @var string
	 */
	private $rawMessage;

	/**
	 * @var string
	 */
	private $sourcePath;

	/**
	 * @var string
	 */
	private $sourceCode;

	/**
	 * Error constructor.
	 *
	 * @param string         $message
	 * @param int            $lineNumber
	 * @param Source|null    $source
	 * @param Exception|null $previous
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function __construct(string $message, int $lineNumber = -1, ?Source $source = null, ?Exception $previous = null)
	{
		parent::__construct('', 0, $previous);

		if ($source === null)
		{
			$name = null;
		}
		else
		{
			$name = $source->getName();
			$this->sourceCode = $source->getCode();
			$this->sourcePath = $source->getPath();
		}

		$this->lineNumber = $lineNumber;
		$this->name = $name;
		$this->rawMessage = $message;
		$this->updateRepr();
	}

	/**
	 * Gets the raw error message.
	 *
	 * @return string
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getRawMessage(): string
	{
		return $this->rawMessage;
	}

	/**
	 * Gets the line number in the cappy-file.
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
	 * Sets the line number in the cappy-file where the error occurred.
	 *
	 * @param int $lineNumber
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setTemplateLine(int $lineNumber): void
	{
		$this->lineNumber = $lineNumber;
		$this->updateRepr();
	}

	/**
	 * Gets the source context of the cappy-file where the error occurred.
	 *
	 * @return Source|null
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getSourceContext(): ?Source
	{
		return $this->name ? new Source($this->sourceCode, $this->name, $this->sourcePath) : null;
	}

	/**
	 * Sets the source context of the cappy-file where the error occurred.
	 *
	 * @param Source|null $source
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function setSourceContext(Source $source = null): void
	{
		if ($source === null)
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

	/**
	 * Guess the template info.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function guess(): void
	{
		$this->guessTemplateInfo();
		$this->updateRepr();
	}

	/**
	 * Append a message to the raw message.
	 *
	 * @param string $rawMessage
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function appendMessage(string $rawMessage): void
	{
		$this->rawMessage .= $rawMessage;
		$this->updateRepr();
	}

	/**
	 * Updates the error representation.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function updateRepr(): void
	{
		$this->message = $this->rawMessage;

		if ($this->sourcePath && $this->lineNumber > 0)
		{
			$this->file = $this->sourcePath;
			$this->line = $this->lineNumber;

			return;
		}

		$dot = false;
		$questionMark = false;

		if (substr($this->message, -1) === '.')
		{
			$this->message = substr($this->message, 0, -1);
			$dot = true;
		}

		if (substr($this->message, -1) === '?')
		{
			$this->message = substr($this->message, 0, -1);
			$questionMark = true;
		}

		if ($this->name)
		{
			if (is_string($this->name) || (is_object($this->name) && method_exists($this->name, '__toString')))
				$name = sprintf('"%s"', $this->name);
			else
				$name = json_encode($this->name);

			$this->message .= sprintf(' in %s', $name);
		}

		if ($this->lineNumber && $this->lineNumber >= 0)
			$this->message .= sprintf(' at line %d', $this->lineNumber);

		if ($dot)
			$this->message .= '.';

		if ($questionMark)
			$this->message .= '?';
	}

	/**
	 * Guesses template info.
	 *
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	private function guessTemplateInfo(): void
	{
		$template = null;
		$templateClass = null;
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);

		foreach ($backtrace as $trace)
		{
			/** @var Template $traceObject */
			$traceObject = $trace['object'];

			if (isset($traceObject) && $traceObject instanceof Template)
			{
				$currentClass = get_class($traceObject);
				$isEmbedContainer = 0 === strpos($templateClass, $currentClass);

				if ($this->name === null || ($this->name === $traceObject->getTemplateName() && !$isEmbedContainer))
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

		if ($template === null || $this->lineNumber > -1)
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
					if ($codeLine > $trace['line'])
						continue;

					$this->lineNumber = $templateLine;
					return;
				}
			}
		}
	}

}
