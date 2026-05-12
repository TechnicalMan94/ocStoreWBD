<?php
class ControllerDynamicCategory extends Controller {
	public function index() {
		$this->load->language('dynamic/category');
		$this->load->model('dynamic/category');
		$this->load->model('dynamic/page');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		if (isset($this->request->get['dcategory_id'])) {
			$category_id = (int)$this->request->get['dcategory_id'];
		} else {
			$category_id = 0;
		}

		$category_info = $this->model_dynamic_category->getCategory($category_id);

		if ($category_info) {
			$this->load->model('dynamic/section');
			$section_info = $this->model_dynamic_section->getSection($category_info['section_id']);

			$this->document->setTitle($category_info['meta_title'] ? $category_info['meta_title'] : $category_info['name']);
			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			if ($category_info['noindex']) {
				$this->document->setRobots('noindex,follow');
			}

			$data['heading_title'] = $category_info['meta_h1'] ? $category_info['meta_h1'] : $category_info['name'];
			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');

			// Image
			if ($category_info['image']) {
				$this->load->model('tool/image');
				$data['image'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('theme_default_image_category_width'), $this->config->get('theme_default_image_category_height'));
			} else {
				$data['image'] = '';
			}

			// Build breadcrumb path
			$path_ids = array();
			$current_id = $category_id;

			while ($current_id > 0) {
				$cat = $this->model_dynamic_category->getCategory($current_id);
				if ($cat) {
					array_unshift($path_ids, array(
						'category_id' => $cat['category_id'],
						'name'        => $cat['name']
					));
					$current_id = $cat['parent_id'];
				} else {
					break;
				}
			}

			foreach ($path_ids as $p) {
				if ($p['category_id'] == $category_id) {
					$data['breadcrumbs'][] = array(
						'text' => $p['name'],
						'href' => $this->url->link('dynamic/category', 'dcategory_id=' . $p['category_id'])
					);
				} else {
					$data['breadcrumbs'][] = array(
						'text' => $p['name'],
						'href' => $this->url->link('dynamic/category', 'dcategory_id=' . $p['category_id'])
					);
				}
			}

			// Subcategories
			$data['categories'] = array();
			$categories = $this->model_dynamic_category->getCategories($category_id);

			foreach ($categories as $category) {
				$data['categories'][] = array(
					'name' => $category['name'],
					'href' => $this->url->link('dynamic/category', 'dcategory_id=' . $category['category_id'])
				);
			}

			// Pages in this category
			$child_category_ids = $this->model_dynamic_category->getCategoriesByParentId($category_id);
			$child_category_ids[] = $category_id;

			$filter_data = array(
				'filter_category_id' => $category_id,
				'filter_section_id'  => $category_info['section_id'],
				'sort'               => isset($this->request->get['sort']) ? $this->request->get['sort'] : 'p.sort_order',
				'order'              => isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC',
				'start'              => 0,
				'limit'              => $this->config->get('theme_default_product_limit')
			);

			if (isset($this->request->get['page'])) {
				$page = (int)$this->request->get['page'];
				$filter_data['start'] = ($page - 1) * $filter_data['limit'];
			} else {
				$page = 1;
			}

			$page_total = $this->model_dynamic_page->getTotalPages($filter_data);
			$results = $this->model_dynamic_page->getPages($filter_data);

			$data['pages'] = array();
			foreach ($results as $result) {
				$page_info = $this->model_dynamic_page->getPage($result['page_id']);
				if ($page_info) {
					$data['pages'][] = array(
						'page_id'    => $page_info['page_id'],
						'name'       => $page_info['name'],
						'description'=> utf8_substr(strip_tags(html_entity_decode($page_info['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '...',
						'image'      => $page_info['image'] ? $this->model_tool_image->resize($page_info['image'], 200, 200) : '',
						'date_added' => $page_info['date_added'],
						'viewed'     => $page_info['viewed'],
						'href'       => $this->url->link('dynamic/page', 'dpage_id=' . $page_info['page_id'])
					);
				}
			}

			// Sorting
			$url = '';
			$data['sorts'] = array();
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_sort_name'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('dynamic/category', 'dcategory_id=' . $category_id . '&sort=pd.name&order=ASC')
			);
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_sort_date'),
				'value' => 'p.date_added-DESC',
				'href'  => $this->url->link('dynamic/category', 'dcategory_id=' . $category_id . '&sort=p.date_added&order=DESC')
			);
			$data['sorts'][] = array(
				'text'  => $this->language->get('text_sort_views'),
				'value' => 'p.viewed-DESC',
				'href'  => $this->url->link('dynamic/category', 'dcategory_id=' . $category_id . '&sort=p.viewed&order=DESC')
			);

			// Pagination
			$pagination = new Pagination();
			$pagination->total = $page_total;
			$pagination->page = $page;
			$pagination->limit = $filter_data['limit'];
			$pagination->url = $this->url->link('dynamic/category', 'dcategory_id=' . $category_id . '&page={page}');

			$data['pagination'] = $pagination->render();
			$data['results'] = sprintf($this->language->get('text_pagination'), ($page_total) ? (($page - 1) * $filter_data['limit']) + 1 : 0, ((($page - 1) * $filter_data['limit']) > ($page_total - $filter_data['limit'])) ? $page_total : ((($page - 1) * $filter_data['limit']) + $filter_data['limit']), $page_total, ceil($page_total / $filter_data['limit']));

			$data['sort'] = isset($this->request->get['sort']) ? $this->request->get['sort'] : 'p.sort_order';
			$data['order'] = isset($this->request->get['order']) ? $this->request->get['order'] : 'ASC';

			// Template from section settings
			$settings = !empty($section_info['settings']) ? $section_info['settings'] : array();
			$template = !empty($settings['category_template']) ? $settings['category_template'] : 'category_default';
			$template_path = 'dynamic_sections/' . $template;

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view($template_path, $data));
		} else {
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}
