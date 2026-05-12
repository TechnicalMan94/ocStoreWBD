<?php
class ControllerDynamicField extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('dynamic/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/field');

		$this->getList();
	}

	public function add() {
		$this->load->language('dynamic/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/field');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->request->post['section_id'] = (int)$this->request->get['section_id'];
			$this->model_dynamic_field->addField($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('dynamic/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/field');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->request->post['section_id'] = (int)$this->request->get['section_id'];
			$this->model_dynamic_field->editField($this->request->get['field_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'], true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('dynamic/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/field');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $field_id) {
				$this->model_dynamic_field->deleteField($field_id);
			}
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'], true));
		}

		$this->getList();
	}

	protected function getList() {
		$section_id = (int)$this->request->get['section_id'];
		$sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'sort_order';
		$order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true))
		);

		$data['add'] = $this->url->link('dynamic/field/add', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);
		$data['delete'] = $this->url->link('dynamic/field/delete', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);

		$filter_data = array(
			'filter_section_id' => $section_id,
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$field_total = $this->model_dynamic_field->getTotalFields(array('filter_section_id' => $section_id));
		$results = $this->model_dynamic_field->getFields($filter_data);
		$data['fields'] = array();

		foreach ($results as $result) {
			$data['fields'][] = array(
				'field_id'   => $result['field_id'],
				'name'       => $result['name'],
				'code'       => $result['code'],
				'type'       => $this->language->get('text_type_' . $result['type']),
				'sort_order' => $result['sort_order'],
				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'       => $this->url->link('dynamic/field/edit', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id . '&field_id=' . $result['field_id'], true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['section_id'] = $section_id;
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$url = '&section_id=' . $section_id . ($order == 'ASC' ? '&order=DESC' : '&order=ASC');
		$data['sort_name'] = $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_code'] = $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&sort=code' . $url, true);
		$data['sort_type'] = $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&sort=type' . $url, true);
		$data['sort_sort_order'] = $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);
		$data['sort_status'] = $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

		$pagination = new Pagination();
		$pagination->total = $field_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($field_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($field_total - $this->config->get('config_limit_admin'))) ? $field_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $field_total, ceil($field_total / $this->config->get('config_limit_admin')));
		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/field_list', $data));
	}

	protected function getForm() {
		$section_id = (int)$this->request->get['section_id'];

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
		$data['error_code'] = isset($this->error['code']) ? $this->error['code'] : '';
		$data['section_id'] = $section_id;

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true))
		);

		$data['action'] = !isset($this->request->get['field_id'])
			? $this->url->link('dynamic/field/add', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true)
			: $this->url->link('dynamic/field/edit', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id . '&field_id=' . $this->request->get['field_id'], true);
		$data['cancel'] = $this->url->link('dynamic/field', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);

		$field_info = array();
		if (isset($this->request->get['field_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$field_info = $this->model_dynamic_field->getField($this->request->get['field_id']);
		}

		foreach (array('name', 'code', 'type', 'sort_order', 'status') as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (isset($field_info[$key])) {
				$data[$key] = $field_info[$key];
			} else {
				$data[$key] = ($key == 'type') ? 'string' : (($key == 'status') ? 1 : (($key == 'sort_order') ? 0 : ''));
			}
		}

		$data['types'] = array('string', 'text', 'number', 'date', 'time');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/field_form', $data));
	}

	protected function validateForm() {
		$section_id = (int)$this->request->get['section_id'];

		$this->load->model('dynamic/section');
		$section = $this->model_dynamic_section->getSection($section_id);
		$section_code = $section ? $section['code'] : '';

		if (!$this->user->hasPermission('modify', 'dynamic/field_' . $section_code) && !$this->user->hasPermission('modify', 'dynamic/field')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 2) || (utf8_strlen($this->request->post['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		$code = $this->model_dynamic_field->normalizeCode($this->request->post['code']);
		if ((utf8_strlen($code) < 1) || (utf8_strlen($code) > 64)) {
			$this->error['code'] = $this->language->get('error_code');
		}

		$field_info = $this->model_dynamic_field->getFieldByCode($code, $section_id);
		if ($field_info && (!isset($this->request->get['field_id']) || $field_info['field_id'] != $this->request->get['field_id'])) {
			$this->error['code'] = $this->language->get('error_code_exists');
		}

		if (!in_array($this->request->post['type'], array('string', 'text', 'number', 'date', 'time'))) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		$section_id = (int)$this->request->get['section_id'];
		$this->load->model('dynamic/section');
		$section = $this->model_dynamic_section->getSection($section_id);
		$section_code = $section ? $section['code'] : '';

		if (!$this->user->hasPermission('modify', 'dynamic/field_' . $section_code) && !$this->user->hasPermission('modify', 'dynamic/field')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
