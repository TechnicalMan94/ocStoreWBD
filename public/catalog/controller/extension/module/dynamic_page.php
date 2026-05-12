<?php
class ControllerExtensionModuleDynamicPage extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/dynamic_page');
		$this->load->model('dynamic/page');
		$this->load->model('tool/image');

		$data['heading_title'] = $setting['name'];
		$data['pages'] = array();

		$limit = !empty($setting['limit']) ? (int)$setting['limit'] : 4;
		$width = !empty($setting['width']) ? (int)$setting['width'] : 200;
		$height = !empty($setting['height']) ? (int)$setting['height'] : 200;
		$section_id = isset($setting['section_id']) ? (int)$setting['section_id'] : 0;
		$source_page_id = !empty($setting['source_page_id']) ? (int)$setting['source_page_id'] : 0;

		if ($source_page_id) {
			$results = array_slice($this->model_dynamic_page->getPageRelated($source_page_id), 0, $limit);
		} else {
			if (!$section_id) {
				return;
			}

			$results = array();
			$page_ids = $this->model_dynamic_page->getPages(array(
				'filter_section_id' => $section_id,
				'sort'              => 'p.sort_order',
				'order'             => 'ASC',
				'start'             => 0,
				'limit'             => $limit
			));

			foreach ($page_ids as $page_id) {
				$page_info = $this->model_dynamic_page->getPage($page_id['page_id']);

				if ($page_info) {
					$results[] = $page_info;
				}
			}
		}

		foreach ($results as $result) {
			if (!empty($result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], $width, $height);
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', $width, $height);
			}

			$data['pages'][] = array(
				'page_id'      => $result['page_id'],
				'thumb'        => $image,
				'name'         => $result['name'],
				'description'  => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_default_product_description_length')) . '..',
				'date_added'   => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'viewed'       => $result['viewed'],
				'href'         => $this->url->link('dynamic/page', 'dpage_id=' . $result['page_id'])
			);
		}

		if ($data['pages']) {
			return $this->load->view('extension/module/dynamic_page', $data);
		}
	}
}
