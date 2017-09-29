<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

/**
 * Class AbstractExtension
 *
 * @author Bas Milius <bas@mili.us>
 * @package Bas\Cappuccino\Extension
 * @version 1.0.0
 */
abstract class AbstractExtension implements ExtensionInterface
{

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTokenParsers (): array
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getNodeVisitors (): array
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFilters (): array
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getTests (): array
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getFunctions (): array
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@mili.us>
	 * @since 1.0.0
	 */
	public function getOperators (): array
	{
		return [];
	}

}
