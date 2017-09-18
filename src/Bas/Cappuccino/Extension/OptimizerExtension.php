<?php
declare(strict_types=1);

namespace Bas\Cappuccino\Extension;

use Bas\Cappuccino\NodeVisitor\OptimizerNodeVisitor;

/**
 * Class OptimizerExtension
 *
 * @author Bas Milius <bas@ideemedia.nl>
 * @package Bas\Cappuccino\Extension
 * @version 2.3.0
 */
final class OptimizerExtension extends AbstractExtension
{

	/**
	 * @var int
	 */
	private $optimizers;

	/**
	 * OptimizerExtension constructor.
	 *
	 * @param int $optimizers
	 *
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function __construct (int $optimizers = -1)
	{
		$this->optimizers = $optimizers;
	}

	/**
	 * {@inheritdoc}
	 * @author Bas Milius <bas@ideemedia.nl>
	 * @since 2.3.0
	 */
	public function getNodeVisitors () : array
	{
		return [
			new OptimizerNodeVisitor($this->optimizers)
		];
	}

}
