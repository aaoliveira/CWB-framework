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
use CWB\Lib\Image\Filter\FilterInterface;

/**
 * A strip filter
 */
class Strip implements FilterInterface
{

	/**
	 * {@inheritdoc}
	 */
	public function apply(ImageInterface $image)
	{
		return $image->strip();
	}

}
