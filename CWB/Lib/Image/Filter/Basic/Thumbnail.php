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
use CWB\Lib\Image\Image\BoxInterface;
use CWB\Lib\Image\Filter\FilterInterface;

/**
 * A thumbnail filter
 */
class Thumbnail implements FilterInterface
{
    /**
     * @var BoxInterface
     */
    private $size;

    /**
     * @var string
     */
    private $mode;

    /**
     * Constructs the Thumbnail filter with given width, height and mode
     *
     * @param BoxInterface $size
     * @param string       $mode
     */
    public function __construct(BoxInterface $size, $mode = ImageInterface::THUMBNAIL_INSET)
    {
        $this->size = $size;
        $this->mode = $mode;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        return $image->thumbnail($this->size, $this->mode);
    }
}