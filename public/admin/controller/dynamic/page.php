<?php
class ControllerDynamicPage extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('dynamic/page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/page');
		$this->getList();
	}

	public function add() {
		$this->load->language('dynamic/page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/page');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->request->post['section_id'] = (int)$this->request->get['section_id'];
			$this->model_dynamic_page->addPage($this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildListUrl();
			$this->response->redirect($this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('dynamic/page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/page');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->request->post['section_id'] = (int)$this->request->get['section_id'];
			$this->model_dynamic_page->editPage($this->request->get['page_id'], $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildListUrl();
			$this->response->redirect($this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('dynamic/page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/page');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $page_id) {
				$this->model_dynamic_page->deletePage($page_id);
			}
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildListUrl();
			$this->response->redirect($this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'] . $url, true));
		}

		$this->getList();
	}

	public function copy() {
		$this->load->language('dynamic/page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/page');

		if (isset($this->request->post['selected']) && $this->validateCopy()) {
			foreach ($this->request->post['selected'] as $page_id) {
				$this->model_dynamic_page->copyPage($page_id);
			}
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildListUrl();
			$this->response->redirect($this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'] . $url, true));
		}

		$this->getList();
	}

	public function enable() {
		$this->load->language('dynamic/page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/page');

		if (isset($this->request->post['selected'])) {
			foreach ($this->request->post['selected'] as $page_id) {
				$this->model_dynamic_page->editPageStatus($page_id, 1);
			}
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildListUrl();
			$this->response->redirect($this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'] . $url, true));
		}

		$this->getList();
	}

	public function disable() {
		$this->load->language('dynamic/page');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('dynamic/page');

		if (isset($this->request->post['selected'])) {
			foreach ($this->request->post['selected'] as $page_id) {
				$this->model_dynamic_page->editPageStatus($page_id, 0);
			}
			$this->session->data['success'] = $this->language->get('text_success');

			$url = $this->buildListUrl();
			$this->response->redirect($this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $this->request->get['section_id'] . $url, true));
		}

		$this->getList();
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$section_id = isset($this->request->get['section_id']) ? (int)$this->request->get['section_id'] : 0;

			$this->load->model('dynamic/page');
			$filter_data = array(
				'filter_section_id' => $section_id,
				'filter_name'       => $this->request->get['filter_name'],
				'sort'              => 'pd.name',
				'order'             => 'ASC',
				'start'             => 0,
				'limit'             => $this->config->get('config_limit_autocomplete')
			);

			$results = $this->model_dynamic_page->getPages($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'page_id' => $result['page_id'],
					'name'    => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();
		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}
		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	protected function getList() {
		$section_id = (int)$this->request->get['section_id'];

		$this->load->model('dynamic/section');
		$section_info = $this->model_dynamic_section->getSection($section_id);

		$sort = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'pd.name';
		$order = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';
		$page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true))
		);

		$data['add'] = $this->url->link('dynamic/page/add', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);
		$data['copy'] = $this->url->link('dynamic/page/copy', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);
		$data['delete'] = $this->url->link('dynamic/page/delete', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);
		$data['enabled'] = $this->url->link('dynamic/page/enable', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);
		$data['disabled'] = $this->url->link('dynamic/page/disable', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);

		$filter_data = array(
			'filter_section_id' => $section_id,
			'filter_name'       => isset($this->request->get['filter_name']) ? $this->request->get['filter_name'] : '',
			'filter_status'     => isset($this->request->get['filter_status']) ? $this->request->get['filter_status'] : null,
			'filter_noindex'    => isset($this->request->get['filter_noindex']) ? $this->request->get['filter_noindex'] : null,
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$page_total = $this->model_dynamic_page->getTotalPages($filter_data);
		$results = $this->model_dynamic_page->getPages($filter_data);
		$data['pages'] = array();

		$this->load->model('tool/image');

		foreach ($results as $result) {
			$image = $result['image'] && is_file(DIR_IMAGE . $result['image']) ? $this->model_tool_image->resize($result['image'], 40, 40) : $this->model_tool_image->resize('no_image.png', 40, 40);

			$data['pages'][] = array(
				'page_id'    => $result['page_id'],
				'image'      => $image,
				'name'       => $result['name'],
				'status'     => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'noindex'    => $result['noindex'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'sort_order' => $result['sort_order'],
				'edit'       => $this->url->link('dynamic/page/edit', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id . '&page_id=' . $result['page_id'], true)
			);
		}

		$data['section_id'] = $section_id;
		$data['section_name'] = $section_info ? $section_info['name'] : '';
		$data['filter_name'] = $filter_data['filter_name'];
		$data['filter_status'] = $filter_data['filter_status'];
		$data['filter_noindex'] = $filter_data['filter_noindex'];
		$data['user_token'] = $this->session->data['user_token'];
		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
		unset($this->session->data['success']);
		$data['selected'] = isset($this->request->post['selected']) ? (array)$this->request->post['selected'] : array();

		$url = '&section_id=' . $section_id;
		if (isset($this->request->get['filter_name'])) $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		if (isset($this->request->get['filter_status'])) $url .= '&filter_status=' . $this->request->get['filter_status'];
		if (isset($this->request->get['filter_noindex'])) $url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
		$url .= ($order == 'ASC') ? '&order=DESC' : '&order=ASC';

		$data['sort_name'] = $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
		$data['sort_status'] = $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
		$data['sort_noindex'] = $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&sort=p.noindex' . $url, true);
		$data['sort_sort_order'] = $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, true);

		$pagination = new Pagination();
		$pagination->total = $page_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id . str_replace('&order=DESC', '', str_replace('&order=ASC', '', $url)) . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($page_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($page_total - $this->config->get('config_limit_admin'))) ? $page_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $page_total, ceil($page_total / $this->config->get('config_limit_admin')));
		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/page_list', $data));
	}

	protected function getForm() {
		$section_id = (int)$this->request->get['section_id'];

		$this->load->model('dynamic/section');
		$section_info = $this->model_dynamic_section->getSection($section_id);

		$data['error_warning'] = isset($this->error['warning']) ? $this->error['warning'] : '';
		$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : array();
		$data['error_meta_title'] = isset($this->error['meta_title']) ? $this->error['meta_title'] : array();
		$data['error_meta_h1'] = isset($this->error['meta_h1']) ? $this->error['meta_h1'] : array();
		$data['error_keyword'] = isset($this->error['keyword']) ? $this->error['keyword'] : '';

		$data['breadcrumbs'] = array(
			array('text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)),
			array('text' => $this->language->get('heading_title'), 'href' => $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true))
		);

		$data['action'] = !isset($this->request->get['page_id'])
			? $this->url->link('dynamic/page/add', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true)
			: $this->url->link('dynamic/page/edit', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id . '&page_id=' . $this->request->get['page_id'], true);
		$data['cancel'] = $this->url->link('dynamic/page', 'user_token=' . $this->session->data['user_token'] . '&section_id=' . $section_id, true);

		$data['user_token'] = $this->session->data['user_token'];
		$data['section_id'] = $section_id;
		$data['section_name'] = $section_info ? $section_info['name'] : '';

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		// Page descriptions
		if (isset($this->request->post['page_description'])) {
			$data['page_description'] = $this->request->post['page_description'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['page_description'] = $this->model_dynamic_page->getPageDescriptions($this->request->get['page_id']);
		} else {
			$data['page_description'] = array();
		}

		// Simple fields
		$page_info = array();
		if (isset($this->request->get['page_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$page_info = $this->model_dynamic_page->getPage($this->request->get['page_id']);
		}

		foreach (array('sort_order', 'status', 'noindex') as $key) {
			if (isset($this->request->post[$key])) {
				$data[$key] = $this->request->post[$key];
			} elseif (isset($page_info[$key])) {
				$data[$key] = $page_info[$key];
			} else {
				$data[$key] = ($key == 'status' || $key == 'noindex') ? 1 : 0;
			}
		}

		// Stores
		$this->load->model('setting/store');
		$data['stores'] = array();
		$data['stores'][] = array('store_id' => 0, 'name' => $this->language->get('text_default'));
		foreach ($this->model_setting_store->getStores() as $store) {
			$data['stores'][] = array('store_id' => $store['store_id'], 'name' => $store['name']);
		}

		if (isset($this->request->post['page_store'])) {
			$data['page_store'] = $this->request->post['page_store'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['page_store'] = $this->model_dynamic_page->getPageStores($this->request->get['page_id']);
		} else {
			$data['page_store'] = array(0);
		}

		// Image
		$this->load->model('tool/image');
		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($page_info['image'])) {
			$data['image'] = $page_info['image'];
		} else {
			$data['image'] = '';
		}

		$data['thumb'] = (!empty($data['image']) && is_file(DIR_IMAGE . $data['image'])) ? $this->model_tool_image->resize($data['image'], 100, 100) : $this->model_tool_image->resize('no_image.png', 100, 100);
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		// Additional images
		if (isset($this->request->post['page_image'])) {
			$page_images = $this->request->post['page_image'];
		} elseif (isset($this->request->get['page_id'])) {
			$page_images = $this->model_dynamic_page->getPageImages($this->request->get['page_id']);
		} else {
			$page_images = array();
		}

		$data['page_images'] = array();
		foreach ($page_images as $page_image) {
			$image = $page_image['image'] ?? $page_image;
			if (is_array($page_image) && isset($page_image['image'])) {
				$image = $page_image['image'];
			}
			if (is_file(DIR_IMAGE . $image)) {
				$data['page_images'][] = array(
					'image'      => $image,
					'thumb'      => $this->model_tool_image->resize($image, 100, 100),
					'sort_order' => is_array($page_image) && isset($page_image['sort_order']) ? $page_image['sort_order'] : 0
				);
			}
		}

		// Categories (from this section only)
		$this->load->model('dynamic/category');
		if (isset($this->request->post['page_category'])) {
			$data['page_category'] = $this->request->post['page_category'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['page_category'] = $this->model_dynamic_page->getPageCategories($this->request->get['page_id']);
		} else {
			$data['page_category'] = array();
		}

		$data['main_category_id'] = isset($this->request->post['main_category_id'])
			? $this->request->post['main_category_id']
			: (isset($this->request->get['page_id']) ? $this->model_dynamic_page->getPageMainCategoryId($this->request->get['page_id']) : 0);

		// Related pages (from this section)
		if (isset($this->request->post['page_related'])) {
			$data['page_related'] = $this->request->post['page_related'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['page_related'] = $this->model_dynamic_page->getPageRelated($this->request->get['page_id']);
		} else {
			$data['page_related'] = array();
		}

		// Related products
		if (isset($this->request->post['product_related'])) {
			$data['product_related'] = $this->request->post['product_related'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['product_related'] = $this->model_dynamic_page->getProductRelated($this->request->get['page_id']);
		} else {
			$data['product_related'] = array();
		}

		$this->load->model('catalog/product');
		$data['products'] = array();
		foreach ($data['product_related'] as $product_id) {
			$product_info = $this->model_catalog_product->getProduct($product_id);
			if ($product_info) {
				$data['products'][] = array('product_id' => $product_id, 'name' => $product_info['name']);
			}
		}

		// Downloads
		if (isset($this->request->post['page_download'])) {
			$data['page_download'] = $this->request->post['page_download'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['page_download'] = $this->model_dynamic_page->getPageDownloads($this->request->get['page_id']);
		} else {
			$data['page_download'] = array();
		}

		$this->load->model('catalog/download');
		$data['downloads'] = array();
		foreach ($data['page_download'] as $download_id) {
			$download_info = $this->model_catalog_download->getDownload($download_id);
			if ($download_info) {
				$data['downloads'][] = array('download_id' => $download_id, 'name' => $download_info['name']);
			}
		}

		// SEO URLs
		if (isset($this->request->post['page_seo_url'])) {
			$data['page_seo_url'] = $this->request->post['page_seo_url'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['page_seo_url'] = $this->model_dynamic_page->getPageSeoUrls($this->request->get['page_id']);
		} else {
			$data['page_seo_url'] = array();
		}

		// Layouts
		if (isset($this->request->post['page_layout'])) {
			$data['page_layout'] = $this->request->post['page_layout'];
		} elseif (isset($this->request->get['page_id'])) {
			$data['page_layout'] = $this->model_dynamic_page->getPageLayouts($this->request->get['page_id']);
		} else {
			$data['page_layout'] = array();
		}

		$this->load->model('design/layout');
		$data['layouts'] = $this->model_design_layout->getLayouts();

		// Fields
		$data['page_fields'] = $this->model_dynamic_page->getPageFields($section_id);
		$data['page_field_values'] = isset($this->request->post['page_field'])
			? $this->request->post['page_field']
			: (isset($this->request->get['page_id']) ? $this->model_dynamic_page->getPageFieldValues($this->request->get['page_id']) : array());

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/page_form', $data));
	}

	protected function validateForm() {
		$section_id = (int)$this->request->get['section_id'];
		$this->load->model('dynamic/section');
		$section = $this->model_dynamic_section->getSection($section_id);
		$section_code = $section ? $section['code'] : '';

		if (!$this->user->hasPermission('modify', 'dynamic/page_' . $section_code) && !$this->user->hasPermission('modify', 'dynamic/page')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['page_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 1) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}

		if (!empty($this->request->post['page_seo_url'])) {
			$this->load->model('design/seo_url');
			foreach ($this->request->post['page_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (empty($keyword)) continue;
					$seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);
					foreach ($seo_urls as $seo_url) {
						if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['page_id']) || ($seo_url['query'] != 'dpage_id=' . $this->request->get['page_id']))) {
							$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword_exists');
							break;
						}
					}
				}
			}
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

		if (!$this->user->hasPermission('modify', 'dynamic/page_' . $section_code) && !$this->user->hasPermission('modify', 'dynamic/page')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	protected function validateCopy() {
		$section_id = (int)$this->request->get['section_id'];
		$this->load->model('dynamic/section');
		$section = $this->model_dynamic_section->getSection($section_id);
		$section_code = $section ? $section['code'] : '';

		if (!$this->user->hasPermission('modify', 'dynamic/page_' . $section_code) && !$this->user->hasPermission('modify', 'dynamic/page')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	private function buildListUrl() {
		$url = '';
		if (isset($this->request->get['filter_name'])) $url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		if (isset($this->request->get['filter_status'])) $url .= '&filter_status=' . $this->request->get['filter_status'];
		if (isset($this->request->get['filter_noindex'])) $url .= '&filter_noindex=' . $this->request->get['filter_noindex'];
		if (isset($this->request->get['sort'])) $url .= '&sort=' . $this->request->get['sort'];
		if (isset($this->request->get['order'])) $url .= '&order=' . $this->request->get['order'];
		if (isset($this->request->get['page'])) $url .= '&page=' . $this->request->get['page'];
		return $url;
	}
}
