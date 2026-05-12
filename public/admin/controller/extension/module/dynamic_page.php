<?php
class ControllerExtensionModuleDynamicPage extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/dynamic_page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('dynamic_page', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
		$data['error_section'] = isset($this->error['section']) ? $this->error['section'] : '';
		$data['error_width'] = isset($this->error['width']) ? $this->error['width'] : '';
		$data['error_height'] = isset($this->error['height']) ? $this->error['height'] : '';

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/module/dynamic_page', 'user_token=' . $this->session->data['user_token'] . (isset($this->request->get['module_id']) ? '&module_id=' . $this->request->get['module_id'] : ''), true))
		);

		$data['action'] = $this->url->link('extension/module/dynamic_page', 'user_token=' . $this->session->data['user_token'] . (isset($this->request->get['module_id']) ? '&module_id=' . $this->request->get['module_id'] : ''), true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		$module_info = array();
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}

		foreach (array('name' => '', 'section_id' => 0, 'source_page_id' => 0, 'limit' => 4, 'width' => 200, 'height' => 200, 'status' => 0) as $key => $default) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (isset($module_info[$key])) {
				$data[$key] = $module_info[$key];
			} else {
				$data[$key] = $default;
			}
		}

		$data['source_page_name'] = '';
		if ($data['source_page_id']) {
			$this->load->model('dynamic/page');
			$page_info = $this->model_dynamic_page->getPage($data['source_page_id']);
			if ($page_info) {
				$data['source_page_name'] = $page_info['name'];
			}
		}

		$this->load->model('dynamic/section');
		$data['sections'] = $this->model_dynamic_section->getSections(array('filter_status' => 1));
		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/dynamic_page', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/dynamic_page')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (empty($this->request->post['section_id'])) {
			$this->error['section'] = $this->language->get('error_section');
		}

		if (empty($this->request->post['width'])) {
			$this->error['width'] = $this->language->get('error_width');
		}

		if (empty($this->request->post['height'])) {
			$this->error['height'] = $this->language->get('error_height');
		}

		return !$this->error;
	}
}
