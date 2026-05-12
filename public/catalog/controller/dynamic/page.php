<?php
class ControllerDynamicPage extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('dynamic/page');

		if (isset($this->request->get['dpage_id'])) {
			$page_id = (int)$this->request->get['dpage_id'];
		} else {
			$page_id = 0;
		}

		$this->load->model('dynamic/page');
		$this->load->model('dynamic/category');

		$page_info = $this->model_dynamic_page->getPage($page_id);

		if ($page_info) {
			$this->load->model('dynamic/section');
			$section_info = $this->model_dynamic_section->getSection($page_info['section_id']);

			$data['breadcrumbs'] = array();
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			// Build breadcrumbs from category path
			$page_categories = $this->model_dynamic_page->getCategories($page_id);
			if ($page_categories) {
				$category_id = $page_categories[0]['category_id'];
				$category_info = $this->model_dynamic_category->getCategory($category_id);
				if ($category_info) {
					$categories = $this->model_dynamic_category->getCategories($category_info['parent_id']);
					// Walk up to build breadcrumbs
					$path = array();
					$current_id = $category_id;
					while ($current_id > 0) {
						$cat = $this->model_dynamic_category->getCategory($current_id);
						if ($cat) {
							array_unshift($path, array(
								'category_id' => $cat['category_id'],
								'name'        => $cat['name']
							));
							$current_id = $cat['parent_id'];
						} else {
							break;
						}
					}
					foreach ($path as $p) {
						$data['breadcrumbs'][] = array(
							'text' => $p['name'],
							'href' => $this->url->link('dynamic/category', 'dcategory_id=' . $p['category_id'])
						);
					}
				}
			}

			$data['breadcrumbs'][] = array(
				'text' => $page_info['name'],
				'href' => $this->url->link('dynamic/page', 'dpage_id=' . $page_id)
			);

			$this->document->setTitle($page_info['meta_title'] ? $page_info['meta_title'] : $page_info['name']);
			$this->document->setDescription($page_info['meta_description']);
			$this->document->setKeywords($page_info['meta_keyword']);

			if ($page_info['noindex']) {
				$this->document->setRobots('noindex,follow');
			}

			$data['heading_title'] = $page_info['meta_h1'] ? $page_info['meta_h1'] : $page_info['name'];
			$data['description'] = html_entity_decode($page_info['description'], ENT_QUOTES, 'UTF-8');
			$data['date_added'] = $page_info['date_added'];
			$data['date_modified'] = $page_info['date_modified'];
			$data['viewed'] = $page_info['viewed'];

			// Image
			if ($page_info['image']) {
				$this->load->model('tool/image');
				$data['image'] = $this->model_tool_image->resize($page_info['image'], $this->config->get('theme_default_image_page_width'), $this->config->get('theme_default_image_page_height'));
			} else {
				$data['image'] = '';
			}

			// Additional images
			$data['images'] = array();
			$results = $this->model_dynamic_page->getPageImages($page_id);
			foreach ($results as $result) {
				if ($result['image']) {
					$this->load->model('tool/image');
					$data['images'][] = array(
						'image' => $this->model_tool_image->resize($result['image'], $this->config->get('theme_default_image_additional_width'), $this->config->get('theme_default_image_additional_height'))
					);
				}
			}

			// Tags
			if ($page_info['tag']) {
				$tags = explode(',', $page_info['tag']);
				$data['tags'] = array();
				foreach ($tags as $tag) {
					$tag = trim($tag);
					if ($tag) {
						$data['tags'][] = array(
							'tag'  => $tag,
							'href' => $this->url->link('dynamic/category', 'tag=' . urlencode($tag))
						);
					}
				}
			}

			// Custom fields
			$data['fields'] = $this->model_dynamic_page->getPageFields($page_id);

			// Related pages
			$data['relateds'] = array();
			$relateds = $this->model_dynamic_page->getPageRelated($page_id);
			foreach ($relateds as $related) {
				if ($related) {
					$data['relateds'][] = array(
						'page_id' => $related['page_id'],
						'name'    => $related['name'],
						'href'    => $this->url->link('dynamic/page', 'dpage_id=' . $related['page_id'])
					);
				}
			}

			// Related products
			$data['related_products'] = array();
			$related_products = $this->model_dynamic_page->getPageRelatedProduct($page_id);
			foreach ($related_products as $product) {
				$data['related_products'][] = array(
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'href'       => $this->url->link('product/product', 'product_id=' . $product['product_id'])
				);
			}

			// Downloads
			$data['downloads'] = array();
			$downloads = $this->model_dynamic_page->getDownloads($page_id);
			$this->load->model('catalog/download');
			foreach ($downloads as $download) {
				$download_info = $this->model_catalog_download->getDownload($download['download_id']);
				if ($download_info) {
					$data['downloads'][] = array(
						'name' => $download_info['name'],
						'href' => $this->url->link('dynamic/page/download', 'download_id=' . $download['download_id'])
					);
				}
			}

			// Reviews
			$data['review_status'] = $this->config->get('config_review_status');
			$data['reviews'] = sprintf($this->language->get('text_reviews'), $page_info['reviews']);

			$data['review'] = $this->url->link('dynamic/page/review', 'dpage_id=' . $page_id);
			$data['write_review'] = $this->url->link('dynamic/page/write', 'dpage_id=' . $page_id);

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}

			$data['login'] = $this->url->link('account/login', '', true);
			$data['register'] = $this->url->link('account/register', '', true);

			$data['error_name'] = isset($this->error['name']) ? $this->error['name'] : '';
			$data['error_text'] = isset($this->error['text']) ? $this->error['text'] : '';
			$data['error_rating'] = isset($this->error['rating']) ? $this->error['rating'] : '';

			$data['success'] = isset($this->session->data['success']) ? $this->session->data['success'] : '';
			unset($this->session->data['success']);

			// Template from section settings
			$settings = !empty($section_info['settings']) ? $section_info['settings'] : array();
			$template = !empty($settings['page_template']) ? $settings['page_template'] : 'page_default';
			$template_path = 'dynamic_sections/' . $template;

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->model_dynamic_page->updateViewed($page_id);

			$this->response->setOutput($this->load->view($template_path, $data));
		} else {
			$data['breadcrumbs'] = array();
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	public function review() {
		$this->load->language('dynamic/page');
		$this->load->model('dynamic/page');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['reviews'] = array();

		$reviews = $this->model_dynamic_page->getReviewsByPageId($this->request->get['dpage_id'], ($page - 1) * 5, 5);
		$review_total = $this->model_dynamic_page->getTotalReviewsByPageId($this->request->get['dpage_id']);

		foreach ($reviews as $result) {
			$data['reviews'][] = array(
				'author'     => $result['author'],
				'text'       => $result['text'],
				'rating'     => (int)$result['rating'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$pagination = new Pagination();
		$pagination->total = $review_total;
		$pagination->page = $page;
		$pagination->limit = 5;
		$pagination->url = $this->url->link('dynamic/page/review', 'dpage_id=' . $this->request->get['dpage_id'] . '&page={page}');

		$data['pagination'] = $pagination->render();
		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * 5) + 1 : 0, ((($page - 1) * 5) > ($review_total - 5)) ? $review_total : ((($page - 1) * 5) + 5), $review_total, ceil($review_total / 5));

		$this->response->setOutput($this->load->view('dynamic_sections/review', $data));
	}

	public function write() {
		$this->load->language('dynamic/page');
		$this->load->model('dynamic/page');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if ((utf8_strlen($this->request->post['name']) < 1) || (utf8_strlen($this->request->post['name']) > 25)) {
				$json['error'] = $this->language->get('error_name');
			}

			if ((utf8_strlen($this->request->post['text']) < 1) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}

			if (!isset($this->request->post['rating']) || $this->request->post['rating'] < 1 || $this->request->post['rating'] > 5) {
				$json['error'] = $this->language->get('error_rating');
			}

			if (!$json) {
				$this->model_dynamic_page->addReview($this->request->get['dpage_id'], $this->request->post);
				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function download() {
		if (isset($this->request->get['download_id'])) {
			$download_id = (int)$this->request->get['download_id'];
		} else {
			$download_id = 0;
		}

		$this->load->model('catalog/download');
		$download_info = $this->model_catalog_download->getDownload($download_id);

		if ($download_info) {
			$file = DIR_DOWNLOAD . $download_info['filename'];

			if (file_exists($file)) {
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . ($download_info['mask'] ? $download_info['mask'] : basename($file)) . '"');
				header('Content-Length: ' . filesize($file));
				readfile($file);
				exit;
			}
		}
	}
}
