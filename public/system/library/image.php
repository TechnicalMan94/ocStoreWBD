<?php

/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
 */

use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface as InterventionImageInterface;

/**
 * Image class
 */
class Image
{
	private $file;
	private $image;
	private $width;
	private $height;
	private $bits;
	private $mime;

	/**
	 * Constructor
	 *
	 * @param	string	$file
	 */
	public function __construct($file)
	{
		if (is_file($file)) {
			$this->file = $file;

			$info = getimagesize($file);

			$this->width = $info[0];
			$this->height = $info[1];
			$this->bits = isset($info['bits']) ? $info['bits'] : '';
			$this->mime = isset($info['mime']) ? $info['mime'] : '';
			$this->image = $this->manager()->read($file);
		} else {
			error_log('Error: Could not load image ' . $file . '!');
		}
	}

	/**
	 * @return	string
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * @return	InterventionImageInterface
	 */
	public function getImage()
	{
		return $this->image;
	}

	/**
	 * @return	int
	 */
	public function getWidth()
	{
		return $this->width;
	}

	/**
	 * @return	int
	 */
	public function getHeight()
	{
		return $this->height;
	}

	/**
	 * @return	string
	 */
	public function getBits()
	{
		return $this->bits;
	}

	/**
	 * @return	string
	 */
	public function getMime()
	{
		return $this->mime;
	}

	/**
	 * @param	string	$file
	 * @param	int		$quality
	 */
	public function save($file, int $quality = 90)
	{
		if (!$this->image instanceof InterventionImageInterface) {
			return;
		}

		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION) ?: 'webp');

		if ($extension == 'jpeg' || $extension == 'jpg') {
			$this->image->toJpeg($quality)->save($file);
		} elseif ($extension == 'png') {
			$this->image->toPng()->save($file);
		} elseif ($extension == 'gif') {
			$this->image->toGif()->save($file);
		} else {
			$this->image->toWebp($quality)->save($file);
		}
	}

	/**
	 * @param	int	$width
	 * @param	int	$height
	 * @param	string	$default
	 */
	public function resize(int $width = 0, int $height = 0, $default = '')
	{
		if (!$this->image instanceof InterventionImageInterface || !$this->width || !$this->height || !$width || !$height) {
			return;
		}

		if ($default == 'w') {
			$this->image->scale($width, null);
		} elseif ($default == 'h') {
			$this->image->scale(null, $height);
		} else {
			$this->image->contain($width, $height, $this->background());
		}

		$this->syncDimensions();
	}

	/**
	 * @param	string	$watermark
	 * @param	string	$position
	 */
	public function watermark($watermark, $position = 'bottomright')
	{
		if (!$this->image instanceof InterventionImageInterface || !$watermark instanceof Image) {
			return;
		}

		$positions = [
			'topleft' => 'top-left',
			'topcenter' => 'top',
			'topright' => 'top-right',
			'middleleft' => 'left',
			'middlecenter' => 'center',
			'middleright' => 'right',
			'bottomleft' => 'bottom-left',
			'bottomcenter' => 'bottom',
			'bottomright' => 'bottom-right'
		];

		$this->image->place($watermark->getImage(), $positions[$position] ?? 'bottom-right');
	}

	/**
	 * @param	int		$width
	 * @param	int		$height
	 */
	public function crop(int $width, int $height)
	{
		if (!$this->image instanceof InterventionImageInterface || !$this->width || !$this->height || !$width || !$height) {
			return;
		}

		$this->image->cover($width, $height);
		$this->syncDimensions();
	}

	/**
	 * @param	int		$degree
	 * @param	string	$color
	 */
	public function rotate($degree, $color = 'FFFFFF')
	{
		if (!$this->image instanceof InterventionImageInterface) {
			return;
		}

		$this->image->rotate((float)$degree, $this->normaliseColor($color));
		$this->syncDimensions();
	}

	/**
	 * @return	ImageManager
	 */
	private function manager()
	{
		static $manager;

		if (!$manager) {
			$manager = ImageManager::gd();
		}

		return $manager;
	}

	/**
	 * @return	string
	 */
	private function background()
	{
		return in_array($this->mime, ['image/png', 'image/webp']) ? 'transparent' : 'ffffff';
	}

	private function syncDimensions()
	{
		$this->width = $this->image->width();
		$this->height = $this->image->height();
	}

	/**
	 * @param	string	$color
	 *
	 * @return	string
	 */
	private function normaliseColor($color)
	{
		if (isset($color[0]) && $color[0] == '#') {
			return substr($color, 1);
		}

		return $color;
	}
}
