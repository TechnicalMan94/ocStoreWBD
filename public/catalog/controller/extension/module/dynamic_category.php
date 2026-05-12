<?php
class ControllerExtensionModuleDynamicCategory extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/dynamic_category');
		$this->load->model('dynamic/category');
		$this->load->model('tool/image');

		$data['heading_title'] = $setting['name'];
		$data['categories'] = array();

		$limit = !empty($setting['limit']) ? (int)$setting['limit'] : 4;
		$width = !empty($setting['width']) ? (int)$setting['width'] : 200;
		$height = !empty($setting['height']) ? (int)$setting['height'] : 200;
		$section_id = isset($setting['section_id']) ? (int)$setting['section_id'] : 0;

		if (!$section_id) {
			return;
		}

		$results = $this->model_dynamic_category->getCategoriesBySection(array(
			'filter_section_id' => $section_id,
			'start'             => 0,
			'limit'             => $limit
		));

		foreach ($results as $result) {
			if (!empty($result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], $width, $height);
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $width, $height);
			}

			$data['categories'][] = array(
				'category_id'  => $result['category_id'],
				'thumb'        => $image,
				'name'         => $result['name'],
				'description'  => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 120) . '..',
				'href'         => $this->url->link('dynamic/category', 'dcategory_id=' . $result['category_id'])
			);
		}

		if ($data['categories']) {
			return $this->load->view('extension/module/dynamic_category', $data);
		}
	}
}
