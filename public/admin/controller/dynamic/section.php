<?php
class ControllerDynamicSection extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('dynamic/section');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/section');
		$this->getList();
	}

	public function add() {
		$this->load->language('dynamic/section');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/section');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_dynamic_section->addSection($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('dynamic/section', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('dynamic/section');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/section');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_dynamic_section->editSection($this->request->get['section_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('dynamic/section', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('dynamic/section');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/section');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $section_id) {
				$this->model_dynamic_section->deleteSection($section_id);
			}
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('dynamic/section', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	protected function getList() {
		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/section', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['add'] = $this->url->link('dynamic/section/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete'] = $this->url->link('dynamic/section/delete', 'user_token=' . $this->session->data['user_token'], true);

		$results = $this->model_dynamic_section->getSections();
		$data['sections'] = array();

		foreach ($results as $result) {
			$data['sections'][] = array(
				'section_id' => $result['section_id'],
				'name'       => $result['name'],
				'code'       => $result['code'],
				'sort_order' => $result['sort_order'],
				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'       => $this->url->link('dynamic/section/edit', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $result['section_id'], true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/section_list', $data));
	}

	protected function getForm() {
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
		$data['error_code'] = isset($this->error['code']) ? $this->error['code'] : '';

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/section', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['action'] = !isset($this->request->get['section_id'])
			? $this->url->link('dynamic/section/add', 'user_token=' . $this->session->data['user_token'], true)
			: $this->url->link('dynamic/section/edit', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'], true);
		$data['cancel'] = $this->url->link('dynamic/section', 'user_token=' . $this->session->data['user_token'], true);

		$section_info = array();
		if (isset($this->request->get['section_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$section_info = $this->model_dynamic_section->getSection($this->request->get['section_id']);
		}

		foreach (array('name', 'code', 'sort_order', 'status') as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (isset($section_info[$key])) {
				$data[$key] = $section_info[$key];
			} else {
				$data[$key] = $key == 'status' ? 1 : ($key == 'sort_order' ? 0 : '');
			}
		}

		// Template selection via glob()
		$data['category_templates'] = $this->scanTemplates('category_');
		$data['page_templates'] = $this->scanTemplates('page_');

		$settings = array();
		if (isset($this->request->post['settings'])) {
			$settings = is_array($this->request->post['settings']) ? $this->request->post['settings'] : array();
		} elseif (!empty($section_info['settings'])) {
			$settings = $section_info['settings'];
		}

		$data['setting_category_template'] = isset($settings['category_template']) ? $settings['category_template'] : '';
		$data['setting_page_template'] = isset($settings['page_template']) ? $settings['page_template'] : '';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/section_form', $data));
	}

	private function scanTemplates($prefix) {
		$templates = array();
		$pattern = DIR_TEMPLATE . '*/template/dynamic_sections/' . $prefix . '*.twig';

		foreach (glob($pattern) as $file) {
			$filename = basename($file, '.twig');
			$theme = basename(dirname(dirname(dirname($file))));
			$label = substr($filename, strlen($prefix));
			$templates[$filename] = $theme . ': ' . $label;
		}

		return $templates;
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'dynamic/section')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		$code = preg_replace('/[^a-z0-9_]/', '', utf8_strtolower(trim($this->request->post['code'])));
		if ((utf8_strlen($code) < 1) || (utf8_strlen($code) > 64)) {
			$this->error['code'] = $this->language->get('error_code');
		}

		$section_info = $this->model_dynamic_section->getSectionByCode($code);
		if ($section_info && (!isset($this->request->get['section_id']) || $section_info['section_id'] != $this->request->get['section_id'])) {
			$this->error['code'] = $this->language->get('error_code_exists');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'dynamic/section')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}
}
