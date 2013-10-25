<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CWB\Lib\Image\Filter\Basic;

use CWB\Lib\Image\Image\ImageInterface;
use CWB\Lib\Image\Image\PointInterface;
use CWB\Lib\Image\Filter\FilterInterface;

/**
 * A paste filter
 */
class Paste implements FilterInterface
{

	/**
	 * @var ImageInterface
	 */
	private $image;

	/**
	 * @var PointInterface
	 */
	private $start;

	/**
	 * Constructs a Paste filter with given ImageInterface to paste and x, y
	 * coordinates of target position
	 *
	 * @param ImageInterface $image
	 * @param PointInterface $start
	 */
	public function __construct(ImageInterface $image, PointInterface $start)
	{
		$this->image = $image;
		$this->start = $start;
	}

	/**
	 * {@inheritdoc}
	 */
	public function apply(ImageInterface $image)
	{
		return $image->paste($this->image, $this->start);
	}

}
