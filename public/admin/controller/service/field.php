<?php
class ControllerServiceField extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('service/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('service/field');
		$this->getList();
	}

	public function add() {
		$this->load->language('service/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('service/field');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_service_field->addField($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('service/field', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('service/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('service/field');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_service_field->editField($this->request->get['service_field_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('service/field', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('service/field');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('service/field');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $service_field_id) {
				$this->model_service_field->deleteField($service_field_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('service/field', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	protected function getList() {
		$sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'sort_order';
		$order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['add'] = $this->url->link('service/field/add', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete'] = $this->url->link('service/field/delete', 'user_token=' . $this->session->data['user_token'], true);
		$data['fields'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$field_total = $this->model_service_field->getTotalFields();
		$results = $this->model_service_field->getFields($filter_data);

		foreach ($results as $result) {
			$data['fields'][] = array(
				'service_field_id' => $result['service_field_id'],
				'name'             => $result['name'],
				'code'             => $result['code'],
				'type'             => $this->language->get('text_type_' . $result['type']),
				'sort_order'       => $result['sort_order'],
				'status'           => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'             => $this->url->link('service/field/edit', 'user_token=' . $this->session->data['user_token'] . '&service_field_id=' . $result['service_field_id'], true)
			);
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$url = ($order == 'ASC') ? '&order=DESC' : '&order=ASC';
		$data['sort_name'] = $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_code'] = $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'] . '&sort=code' . $url, true);
		$data['sort_type'] = $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'] . '&sort=type' . $url, true);
		$data['sort_order'] = $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);
		$data['sort_status'] = $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

		$pagination = new Pagination();
		$pagination->total = $field_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($field_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($field_total - $this->config->get('config_limit_admin'))) ? $field_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $field_total, ceil($field_total / $this->config->get('config_limit_admin')));
		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('service/field_list', $data));
	}

	protected function getForm() {
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
		$data['error_code'] = isset($this->error['code']) ? $this->error['code'] : '';
		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['action'] = !isset($this->request->get['service_field_id'])
			? $this->url->link('service/field/add', 'user_token=' . $this->session->data['user_token'], true)
			: $this->url->link('service/field/edit', 'user_token=' . $this->session->data['user_token'] . '&service_field_id=' . $this->request->get['service_field_id'], true);
		$data['cancel'] = $this->url->link('service/field', 'user_token=' . $this->session->data['user_token'], true);

		$field_info = array();
		if (isset($this->request->get['service_field_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$field_info = $this->model_service_field->getField($this->request->get['service_field_id']);
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

		$this->response->setOutput($this->load->view('service/field_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'service/field')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 2) || (utf8_strlen($this->request->post['name']) > 255)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		$code = $this->model_service_field->normalizeCode($this->request->post['code']);

		if ((utf8_strlen($code) < 1) || (utf8_strlen($code) > 64)) {
			$this->error['code'] = $this->language->get('error_code');
		}

		$field_info = $this->model_service_field->getFieldByCode($code);

		if ($field_info && (!isset($this->request->get['service_field_id']) || $field_info['service_field_id'] != $this->request->get['service_field_id'])) {
			$this->error['code'] = $this->language->get('error_code_unique');
		}

		if (!in_array($this->request->post['type'], array('string', 'text', 'number', 'date', 'time'))) {
			$this->error['warning'] = $this->language->get('error_type');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'service/field')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
