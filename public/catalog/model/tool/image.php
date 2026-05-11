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
			return $this->config->get('config_ssl') . 'image/' . $image_new;
		} else {
			return $this->config->get('config_url') . 'image/' . $image_new;
		}
	}
}
