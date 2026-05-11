<?php
class ControllerCatalogVariant extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('catalog/variant');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/variant');
		$this->getList();
	}

	public function add() {
		$this->load->language('catalog/variant');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/variant');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_variant->addVariantGroup($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('catalog/variant');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/variant');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_catalog_variant->editVariantGroup($this->request->get['variant_group_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('catalog/variant');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('catalog/variant');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $variant_group_id) {
				$this->model_catalog_variant->deleteVariantGroup($variant_group_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	protected function getList() {
		$sort = $this->request->get['sort'] ?? 'sort_order';
		$order = $this->request->get['order'] ?? 'ASC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url, true)
			)
		);

		$data['add'] = $this->url->link('catalog/variant/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('catalog/variant/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$variant_group_total = $this->model_catalog_variant->getTotalVariantGroups();
		$results = $this->model_catalog_variant->getVariantGroups($filter_data);

		$data['variant_groups'] = array();

		foreach ($results as $result) {
			$data['variant_groups'][] = array(
				'variant_group_id' => $result['variant_group_id'],
				'name'             => $result['name'],
				'keyword'          => $result['keyword'],
				'sort_order'       => $result['sort_order'],
				'status'           => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'edit'             => $this->url->link('catalog/variant/edit', 'user_token=' . $this->session->data['user_token'] . '&variant_group_id=' . $result['variant_group_id'] . $url, true)
			);
		}

		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['success'] = $this->session->data['success'] ?? '';
		unset($this->session->data['success']);

		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$url = ($order == 'ASC') ? '&order=DESC' : '&order=ASC';

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_keyword'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . '&sort=keyword' . $url, true);
		$data['sort_sort_order'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);
		$data['sort_status'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

		$pagination = new Pagination();
		$pagination->total = $variant_group_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($variant_group_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($variant_group_total - $this->config->get('config_limit_admin'))) ? $variant_group_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $variant_group_total, ceil($variant_group_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/variant_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['variant_group_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['error_name'] = $this->error['name'] ?? '';

		$url = '';

		$data['breadcrumbs'] = array(
			array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
			),
			array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'], true)
			)
		);

		if (!isset($this->request->get['variant_group_id'])) {
			$data['action'] = $this->url->link('catalog/variant/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('catalog/variant/edit', 'user_token=' . $this->session->data['user_token'] . '&variant_group_id=' . $this->request->get['variant_group_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('catalog/variant', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['variant_group_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$variant_group_info = $this->model_catalog_variant->getVariantGroup($this->request->get['variant_group_id']);
		} else {
			$variant_group_info = array();
		}

		$data['name'] = $this->request->post['name'] ?? ($variant_group_info['name'] ?? '');
		$data['keyword'] = $this->request->post['keyword'] ?? ($variant_group_info['keyword'] ?? '');
		$data['sort_order'] = $this->request->post['sort_order'] ?? ($variant_group_info['sort_order'] ?? 0);
		$data['status'] = $this->request->post['status'] ?? ($variant_group_info['status'] ?? 1);

		if (isset($this->request->post['variant_value'])) {
			$data['variant_values'] = $this->request->post['variant_value'];
		} elseif (isset($this->request->get['variant_group_id'])) {
			$data['variant_values'] = $this->model_catalog_variant->getVariantValues($this->request->get['variant_group_id']);
		} else {
			$data['variant_values'] = array();
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/variant_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'catalog/variant')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen(trim($this->request->post['name'])) < 1) || (utf8_strlen($this->request->post['name']) > 128)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'catalog/variant')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
