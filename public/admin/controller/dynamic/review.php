<?php
class ControllerDynamicReview extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('dynamic/review');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/review');

		$this->getList();
	}

	public function add() {
		$this->load->language('dynamic/review');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/review');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_dynamic_review->addReview($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();
			$this->response->redirect($this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('dynamic/review');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/review');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_dynamic_review->editReview($this->request->get['review_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();
			$this->response->redirect($this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('dynamic/review');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/review');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $review_id) {
				$this->model_dynamic_review->deleteReview($review_id);
			}
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildUrl();
			$this->response->redirect($this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		$section_id = isset($this->request->get['section_id']) ? (int)$this->request->get['section_id'] : 0;
		$sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'r.date_added';
		$order = isset($this->request->get['order']) ? $this->request->get['order'] : 'DESC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'], true))
		);

		$data['add'] = $this->url->link('dynamic/review/add', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);
		$data['delete'] = $this->url->link('dynamic/review/delete', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);

		$filter_data = array(
			'filter_section_id' => $section_id,
			'filter_page'       => isset($this->request->get['filter_page']) ? $this->request->get['filter_page'] : '',
			'filter_author'     => isset($this->request->get['filter_author']) ? $this->request->get['filter_author'] : '',
			'filter_status'     => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : null,
			'filter_date_added' => isset($this->request->get['filter_date_added']) ? $this->request->get['filter_date_added'] : '',
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$review_total = $this->model_dynamic_review->getTotalReviews($filter_data);
		$results = $this->model_dynamic_review->getReviews($filter_data);
		$data['reviews'] = array();

		foreach ($results as $result) {
			$data['reviews'][] = array(
				'review_id'  => $result['review_id'],
				'page_name'  => $result['page_name'],
				'author'     => $result['author'],
				'rating'     => $result['rating'],
				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'edit'       => $this->url->link('dynamic/review/edit', 'user_token=' . $this->session->data['user_token'] . '&review_id=' . $result['review_id'] . '&section_id=' . $section_id, true)
			);
		}

		$data['section_id'] = $section_id;
		$data['filter_page'] = $filter_data['filter_page'];
		$data['filter_author'] = $filter_data['filter_author'];
		$data['filter_status'] = $filter_data['filter_status'];
		$data['filter_date_added'] = $filter_data['filter_date_added'];

		$data['user_token'] = $this->session->data['user_token'];
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$url = '&section_id=' . $section_id;
		if (isset($this->request->get['filter_page'])) $url .= '&filter_page=' . urlencode(html_entity_decode($this->request->get['filter_page'], ENT_QUOTES, 'UTF-8'));
		if (isset($this->request->get['filter_author'])) $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
		if (isset($this->request->get['filter_status'])) $url .= '&filter_status=' . $this->request->get['filter_status'];
		if (isset($this->request->get['filter_date_added'])) $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];

		$url .= ($order == 'ASC') ? '&order=DESC' : '&order=ASC';
		$data['sort_page'] = $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
		$data['sort_author'] = $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . '&sort=r.author' . $url, true);
		$data['sort_rating'] = $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . '&sort=r.rating' . $url, true);
		$data['sort_status'] = $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . '&sort=r.status' . $url, true);
		$data['sort_date_added'] = $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . '&sort=r.date_added' . $url, true);

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($review_total - $this->config->get('config_limit_admin'))) ? $review_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $review_total, ceil($review_total / $this->config->get('config_limit_admin')));
		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/review_list', $data));
	}

	protected function getForm() {
		$section_id = isset($this->request->get['section_id']) ? (int)$this->request->get['section_id'] : 0;

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_page'] = isset($this->error['page']) ? $this->error['page'] : '';
		$data['error_author'] = isset($this->error['author']) ? $this->error['author'] : '';
		$data['error_text'] = isset($this->error['text']) ? $this->error['text'] : '';
		$data['error_rating'] = isset($this->error['rating']) ? $this->error['rating'] : '';
		$data['section_id'] = $section_id;

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true))
		);

		$data['action'] = !isset($this->request->get['review_id'])
			? $this->url->link('dynamic/review/add', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true)
			: $this->url->link('dynamic/review/edit', 'user_token=' . $this->session->data['user_token'] . '&review_id=' . $this->request->get['review_id'] . '&section_id=' . $section_id, true);
		$data['cancel'] = $this->url->link('dynamic/review', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);

		$review_info = array();
		if (isset($this->request->get['review_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$review_info = $this->model_dynamic_review->getReview($this->request->get['review_id']);
		}

		foreach (array('page_id', 'author', 'text', 'rating', 'status', 'date_added') as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (isset($review_info[$key])) {
				$data[$key] = $review_info[$key];
			} else {
				$data[$key] = ($key == 'status') ? 1 : (($key == 'rating') ? 5 : '');
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/review_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'dynamic/review')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['page_id']) {
			$this->error['page'] = $this->language->get('error_page');
		}

		if ((utf8_strlen($this->request->post['author']) < 1) || (utf8_strlen($this->request->post['author']) > 64)) {
			$this->error['author'] = $this->language->get('error_author');
		}

		if (utf8_strlen($this->request->post['text']) < 1) {
			$this->error['text'] = $this->language->get('error_text');
		}

		if (!isset($this->request->post['rating']) || $this->request->post['rating'] < 1 || $this->request->post['rating'] > 5) {
			$this->error['rating'] = $this->language->get('error_rating');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'dynamic/review')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	private function buildUrl() {
		$url = '';
		if (isset($this->request->get['section_id'])) $url .= '&section_id=' . $this->request->get['section_id'];
		if (isset($this->request->get['filter_page'])) $url .= '&filter_page=' . urlencode(html_entity_decode($this->request->get['filter_page'], ENT_QUOTES, 'UTF-8'));
		if (isset($this->request->get['filter_author'])) $url .= '&filter_author=' . urlencode(html_entity_decode($this->request->get['filter_author'], ENT_QUOTES, 'UTF-8'));
		if (isset($this->request->get['filter_status'])) $url .= '&filter_status=' . $this->request->get['filter_status'];
		if (isset($this->request->get['filter_date_added'])) $url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		if (isset($this->request->get['sort'])) $url .= '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order'])) $url .= '&order=' . $this->request->get['order'];
		if (isset($this->request->get['page'])) $url .= '&page=' . $this->request->get['page'];
		return $url;
	}
}
