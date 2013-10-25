<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CWB\Lib\Image\Gd;

use CWB\Lib\Image\Effects\EffectsInterface;
use CWB\Lib\Image\Exception\RuntimeException;
use CWB\Lib\Image\Image\Color;

/**
 * Effects implementation using the GD library
 */
class Effects implements EffectsInterface
{
    private $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function gamma($correction)
    {
        if (false === imagegammacorrect($this->resource, 1.0, $correction)) {
            throw new RuntimeException('Failed to apply gamma correction to the image');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function negative()
    {
        if (false === imagefilter($this->resource, IMG_FILTER_NEGATE)) {
           throw new RuntimeException('Failed to negate the image');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function grayscale()
    {
        if (false === imagefilter($this->resource, IMG_FILTER_GRAYSCALE)) {
           throw new RuntimeException('Failed to grayscale the image');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function colorize(Color $color)
    {
        if (false === imagefilter($this->resource, IMG_FILTER_COLORIZE, $color->getRed(), $color->getGreen(), $color->getBlue())) {
            throw new RuntimeException('Failed to colorize the image');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sharpen()
    {
        $sharpenMatrix = array(array(-1,-1,-1), array(-1,16,-1), array(-1,-1,-1));
        $divisor = array_sum(array_map('array_sum', $sharpenMatrix));

        if (false === imageconvolution($this->resource, $sharpenMatrix, $divisor, 0)) {
            throw new RuntimeException('Failed to sharpen the image');
        }

        return $this;
    }
}
