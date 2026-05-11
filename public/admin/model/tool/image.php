<?php
class ModelToolImage extends Model
{
	public function resize($filename, $width, $height, $mode = 'resize')
	{
		$image_new = resize_image($filename, $width, $height, $mode);

		if (!$image_new) {
			return;
		}

		if (substr($image_new, 0, strlen(DIR_IMAGE)) == DIR_IMAGE) {
			return $image_new;
		}

		if ($this->request->server['HTTPS']) {
			return HTTPS_CATALOG . 'image/' . $image_new;
		} else {
			return HTTP_CATALOG . 'image/' . $image_new;
		}
	}
}
