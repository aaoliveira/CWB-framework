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

use CWB\Lib\Image\Filter\FilterInterface;
use CWB\Lib\Image\Image\ImageInterface;
use CWB\Lib\Image\Image\BoxInterface;

/**
 * A resize filter
 */
class Resize implements FilterInterface
{

	/**
	 * @var BoxInterface
	 */
	private $size;

	/**
	 * Constructs Resize filter with given width and height
	 *
	 * @param BoxInterface $size
	 */
	public function __construct(BoxInterface $size)
	{
		$this->size = $size;
	}

	/**
	 * {@inheritdoc}
	 */
	public function apply(ImageInterface $image)
	{
		return $image->resize($this->size);
	}

}
