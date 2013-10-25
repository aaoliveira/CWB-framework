<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CWB\Lib\Image\Image\Fill\Gradient;

use CWB\Lib\Image\Image\PointInterface;

/**
 * Vertical gradient fill
 */
final class Vertical extends Linear
{
    /**
     * {@inheritdoc}
     */
    public function getDistance(PointInterface $position)
    {
        return $position->getY();
    }
}
