<?php
class ControllerCommonHome extends Controller {
	public function index() {
		$this->document->setTitle($this->getMetaValue('meta_title', 'config_meta_title'));
		$this->document->setDescription($this->getMetaValue('meta_description', 'config_meta_description'));
		$this->document->setKeywords($this->getMetaValue('meta_keyword', 'config_meta_keyword'));

		if (isset($this->request->get['route'])) {
			$this->document->addLink($this->url->link('common/home'), 'canonical');
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}

	protected function getMetaValue($field, $fallback_key) {
		$config_meta = $this->config->get('config_meta');
		$language_id = (int)$this->config->get('config_language_id');

		if (is_array($config_meta) && !empty($config_meta[$language_id][$field])) {
			return $config_meta[$language_id][$field];
		}

		return $this->config->get($fallback_key);
	}
}
