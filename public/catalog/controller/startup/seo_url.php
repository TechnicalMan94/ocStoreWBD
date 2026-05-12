<?php
class ControllerStartupSeoUrl extends Controller {
	public function index() {
		// Add rewrite to url class
		if ($this->config->get('config_seo_url')) {
			$this->url->addRewrite($this);
		}

		// Decode URL
		if (!isset($this->request->get['_route_'])) {
			$this->request->get['_route_'] = $this->getRoute();
		}

		if (isset($this->request->get['_route_'])) {
			$parts = explode('/', $this->request->get['_route_']);

			// remove any empty arrays from trailing
			if (utf8_strlen(end($parts)) == 0) {
				array_pop($parts);
			}

			foreach ($parts as $part) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE keyword = '" . $this->db->escape($part) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

				if ($query->num_rows) {
					$url = explode('=', $query->row['query']);

					if ($url[0] == 'product_id') {
						$this->request->get['product_id'] = $url[1];
					}

					if ($url[0] == 'category_id') {
						if (!isset($this->request->get['path'])) {
							$this->request->get['path'] = $url[1];
						} else {
							$this->request->get['path'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'manufacturer_id') {
						$this->request->get['manufacturer_id'] = $url[1];
					}

					if ($url[0] == 'information_id') {
						$this->request->get['information_id'] = $url[1];
					}

					if ($url[0] == 'service_category_id') {
						if (!isset($this->request->get['service_category_id'])) {
							$this->request->get['service_category_id'] = $url[1];
						} else {
							$this->request->get['service_category_id'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'service_id') {
						$this->request->get['service_id'] = $url[1];
					}

					if ($url[0] == 'blog_category_id') {
						if (!isset($this->request->get['blog_category_id'])) {
							$this->request->get['blog_category_id'] = $url[1];
						} else {
							$this->request->get['blog_category_id'] .= '_' . $url[1];
						}
					}

					if ($url[0] == 'article_id') {
						$this->request->get['article_id'] = $url[1];
					}

					if ($url[0] == 'dpage_id') {
						$this->request->get['dpage_id'] = $url[1];
					}

					if ($url[0] == 'dcategory_id') {
						if (!isset($this->request->get['dcategory_id'])) {
							$this->request->get['dcategory_id'] = $url[1];
						} else {
							$this->request->get['dcategory_id'] .= '_' . $url[1];
						}
					}

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id' && $url[0] != 'service_category_id' && $url[0] != 'service_id' && $url[0] != 'blog_category_id' && $url[0] != 'article_id' && $url[0] != 'dpage_id' && $url[0] != 'dcategory_id') {
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					$variant_route = $this->getProductVariantRoute($part);

					if ($variant_route) {
						$this->request->get['product_id'] = $variant_route['product_id'];
						$this->request->get['variant_key'] = $variant_route['variant_key'];
					} else {
						$this->request->get['route'] = 'error/not_found';

						break;
					}
				}
			}

			if (!isset($this->request->get['route'])) {
				if (isset($this->request->get['product_id'])) {
					$this->request->get['route'] = 'product/product';
				} elseif (isset($this->request->get['path'])) {
					$this->request->get['route'] = 'product/category';
				} elseif (isset($this->request->get['manufacturer_id'])) {
					$this->request->get['route'] = 'product/manufacturer/info';
				} elseif (isset($this->request->get['information_id'])) {
					$this->request->get['route'] = 'information/information';
				} elseif (isset($this->request->get['service_id'])) {
					$this->request->get['route'] = 'service/service';
				} elseif (isset($this->request->get['service_category_id'])) {
					$this->request->get['route'] = 'service/category';
				} elseif (isset($this->request->get['article_id'])) {
					$this->request->get['route'] = 'blog/article';
				} elseif (isset($this->request->get['blog_category_id'])) {
					$this->request->get['route'] = 'blog/category';
				} elseif (isset($this->request->get['dpage_id'])) {
					$this->request->get['route'] = 'dynamic/page';
				} elseif (isset($this->request->get['dcategory_id'])) {
					$this->request->get['route'] = 'dynamic/category';
				} elseif ($this->request->get['_route_'] === '') {
					$this->request->get['route'] = 'common/home';
				}
			}
		}

		if ($this->config->get('config_seo_url')) {
			$this->redirectToCanonicalSeoUrl();
		}
	}

	public function rewrite($link) {
		$url_info = parse_url(str_replace('&amp;', '&', $link));

		$url = '';

		$data = array();

		if (isset($url_info['query'])) {
			parse_str($url_info['query'], $data);
		}

		$seo = false;

		if (isset($data['route'])) {
			if ($data['route'] == 'product/product' && isset($data['product_id'])) {
				$canonical_path = $this->getProductPath($data['product_id']);

				if ($canonical_path) {
					$data['path'] = $canonical_path;
					$data = $this->moveKeyAfterRoute($data, 'path');
				}
			} elseif ($data['route'] == 'product/category' && isset($data['path'])) {
				$canonical_path = $this->getCategoryPath($this->getLastPathId($data['path']));

				if ($canonical_path) {
					$data['path'] = $canonical_path;
				}
			} elseif ($data['route'] == 'service/service' && isset($data['service_id'])) {
				$canonical_path = $this->getServicePath($data['service_id']);

				if ($canonical_path) {
					$data['service_category_id'] = $canonical_path;
					$data = $this->moveKeyAfterRoute($data, 'service_category_id');
				}
			} elseif ($data['route'] == 'service/category' && isset($data['service_category_id'])) {
				$canonical_path = $this->getServiceCategoryPath($this->getLastPathId($data['service_category_id']));

				if ($canonical_path) {
					$data['service_category_id'] = $canonical_path;
				}
			} elseif ($data['route'] == 'blog/article' && isset($data['article_id'])) {
				$canonical_path = $this->getArticlePath($data['article_id']);

				if ($canonical_path) {
					$data['blog_category_id'] = $canonical_path;
					$data = $this->moveKeyAfterRoute($data, 'blog_category_id');
				}
			} elseif ($data['route'] == 'blog/category' && isset($data['blog_category_id'])) {
				$canonical_path = $this->getBlogCategoryPath($this->getLastPathId($data['blog_category_id']));

				if ($canonical_path) {
					$data['blog_category_id'] = $canonical_path;
				}
			} elseif ($data['route'] == 'dynamic/page' && isset($data['dpage_id'])) {
				$canonical_path = $this->getDynamicPagePath($data['dpage_id']);

				if ($canonical_path) {
					$data['dcategory_id'] = $canonical_path;
					$data = $this->moveKeyAfterRoute($data, 'dcategory_id');
				}
			} elseif ($data['route'] == 'dynamic/category' && isset($data['dcategory_id'])) {
				$canonical_path = $this->getDynamicCategoryPath($this->getLastPathId($data['dcategory_id']));

				if ($canonical_path) {
					$data['dcategory_id'] = $canonical_path;
				}
			}
		}

		foreach ($data as $key => $value) {
			if (isset($data['route'])) {
				if ($key == 'route') {
					if (!in_array($value, array('product/product', 'product/category', 'product/manufacturer/info', 'information/information'))) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows) {
							if ($query->row['keyword']) {
								$url .= '/' . $query->row['keyword'];
							}

							$seo = true;

							unset($data[$key]);
						}
					}
				}

				if (isset($data['route']) && $data['route'] == 'service/service' && $key == 'service_id') {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'service_id=" . (int)$value . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];
						$seo = true;
						unset($data[$key]);
					}
				} elseif (isset($data['route']) && $data['route'] == 'blog/article' && $key == 'article_id') {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'article_id=" . (int)$value . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];
						$seo = true;
						unset($data[$key]);
					}
				} elseif (isset($data['route']) && $data['route'] == 'dynamic/page' && $key == 'dpage_id') {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'dpage_id=" . (int)$value . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];
						$seo = true;
						unset($data[$key]);
					}
				} elseif (isset($data['route']) && (($data['route'] == 'product/product' && $key == 'product_id') || ($data['route'] == 'product/manufacturer/info' && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id'))) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'] . (($data['route'] == 'product/product' && !empty($data['variant_key'])) ? '-' . $data['variant_key'] : '');
						$seo = true;

						unset($data[$key]);
						unset($data['variant_key']);
					}
				} elseif ($key == 'path') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
							$seo = true;
						} else {
							$url = '';
							$seo = false;

							break;
						}
					}

					unset($data[$key]);
				} elseif ($key == 'service_category_id') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'service_category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
							$seo = true;
						} else {
							$url = '';
							$seo = false;
							break;
						}
					}

					unset($data[$key]);
				} elseif ($key == 'blog_category_id') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'blog_category_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
							$seo = true;
						} else {
							$url = '';
							$seo = false;
							break;
						}
					}

					unset($data[$key]);
				} elseif ($key == 'dcategory_id') {
					$categories = explode('_', $value);

					foreach ($categories as $category) {
						$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = 'dcategory_id=" . (int)$category . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

						if ($query->num_rows && $query->row['keyword']) {
							$url .= '/' . $query->row['keyword'];
							$seo = true;
						} else {
							$url = '';
							$seo = false;
							break;
						}
					}

					unset($data[$key]);
				}
			}
		}

		unset($data['route']);

		$query = '';

		if ($data) {
			foreach ($data as $key => $value) {
				$query .= '&' . rawurlencode((string)$key) . '=' . rawurlencode((is_array($value) ? http_build_query($value) : (string)$value));
			}

			if ($query) {
				$query = '?' . str_replace('&', '&amp;', trim($query, '&'));
			}
		}

		if ($url || $seo) {
			return $url_info['scheme'] . '://' . $url_info['host'] . (isset($url_info['port']) ? ':' . $url_info['port'] : '') . str_replace('/index.php', '', $url_info['path']) . $url . $query;
		} else {
			return $link;
		}
	}

	private function redirectToCanonicalSeoUrl() {
		if (!isset($this->request->server['REQUEST_METHOD']) || $this->request->server['REQUEST_METHOD'] != 'GET') {
			return;
		}

		if (empty($this->request->get['route']) || $this->request->get['route'] == 'error/not_found') {
			return;
		}

		$route = $this->request->get['route'];
		$args = $this->request->get;

		unset($args['route'], $args['_route_']);

		$canonical = html_entity_decode($this->url->link($route, $args), ENT_QUOTES, 'UTF-8');
		$current = $this->getCurrentUrl();

		if ($this->normalizeUrl($current) != $this->normalizeUrl($canonical)) {
			$this->response->redirect($canonical, 301);
		}
	}

	private function getProductVariantRoute($keyword) {
		$query = $this->db->query("SELECT su.query, su.keyword FROM " . DB_PREFIX . "seo_url su LEFT JOIN " . DB_PREFIX . "product p ON (su.query = CONCAT('product_id=', p.product_id)) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE su.store_id = '" . (int)$this->config->get('config_store_id') . "' AND su.language_id = '" . (int)$this->config->get('config_language_id') . "' AND su.query LIKE 'product_id=%' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY LENGTH(su.keyword) DESC");

		foreach ($query->rows as $row) {
			if (strpos($keyword, $row['keyword'] . '-') !== 0) {
				continue;
			}

			$product_id = (int)str_replace('product_id=', '', $row['query']);
			$variant_key = substr($keyword, strlen($row['keyword']) + 1);

			if ($this->isProductVariantKey($product_id, $variant_key)) {
				return array(
					'product_id'  => $product_id,
					'variant_key' => $variant_key
				);
			}
		}

		return false;
	}

	private function isProductVariantKey($product_id, $variant_key) {
		$query = $this->db->query("SELECT vg.variant_group_id, vg.sort_order AS group_sort_order, v.variant_id, v.keyword, v.sort_order FROM " . DB_PREFIX . "product_variant pv LEFT JOIN `" . DB_PREFIX . "variant` v ON (pv.variant_id = v.variant_id) LEFT JOIN `" . DB_PREFIX . "variant_group` vg ON (v.variant_group_id = vg.variant_group_id) WHERE pv.product_id = '" . (int)$product_id . "' AND v.status = '1' AND vg.status = '1' ORDER BY vg.sort_order ASC, vg.name ASC, v.sort_order ASC, v.name ASC");

		$groups = array();

		foreach ($query->rows as $row) {
			$groups[$row['variant_group_id']][] = $row['keyword'];
		}

		if (!$groups) {
			return false;
		}

		$keys = array('');

		foreach ($groups as $keywords) {
			$new_keys = array();

			foreach ($keys as $key) {
				foreach ($keywords as $keyword) {
					$new_keys[] = trim($key . '-' . $keyword, '-');
				}
			}

			$keys = $new_keys;
		}

		return in_array($variant_key, $keys);
	}

	private function getProductPath($product_id) {
		$product_id = (int)$product_id;

		$query = $this->db->query("SELECT p2c.category_id, MAX(cp.level) AS path_depth FROM " . DB_PREFIX . "product_to_category p2c LEFT JOIN " . DB_PREFIX . "category c ON (p2c.category_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_path cp ON (p2c.category_id = cp.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (p2c.category_id = c2s.category_id) WHERE p2c.product_id = '" . $product_id . "' AND c.status = '1' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' GROUP BY p2c.category_id, p2c.main_category, c.sort_order ORDER BY p2c.main_category DESC, path_depth DESC, c.sort_order ASC, p2c.category_id ASC LIMIT 1");

		if ($query->num_rows) {
			return $this->getCategoryPath($query->row['category_id']);
		}

		return '';
	}

	private function moveKeyAfterRoute($data, $key) {
		if (!isset($data[$key])) {
			return $data;
		}

		$value = $data[$key];
		unset($data[$key]);

		$result = array();

		foreach ($data as $data_key => $data_value) {
			$result[$data_key] = $data_value;

			if ($data_key == 'route') {
				$result[$key] = $value;
			}
		}

		return $result;
	}

	private function getLastPathId($path) {
		$parts = explode('_', (string)$path);

		return (int)array_pop($parts);
	}

	private function getCategoryPath($category_id) {
		$query = $this->db->query("SELECT GROUP_CONCAT(cp.path_id ORDER BY cp.level SEPARATOR '_') AS path FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c ON (cp.path_id = c.category_id) LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (cp.path_id = c2s.category_id) WHERE cp.category_id = '" . (int)$category_id . "' AND c.status = '1' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['path'] ?? '';
	}

	private function getServicePath($service_id) {
		$service_id = (int)$service_id;

		$query = $this->db->query("SELECT s2sc.service_category_id FROM " . DB_PREFIX . "service_to_service_category s2sc LEFT JOIN " . DB_PREFIX . "service_category sc ON (s2sc.service_category_id = sc.service_category_id) LEFT JOIN " . DB_PREFIX . "service_category_to_store sc2s ON (s2sc.service_category_id = sc2s.service_category_id) WHERE s2sc.service_id = '" . $service_id . "' AND sc.status = '1' AND sc2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY s2sc.main_service_category DESC, sc.sort_order ASC LIMIT 1");

		if ($query->num_rows) {
			return $this->getServiceCategoryPath($query->row['service_category_id']);
		}

		return '';
	}

	private function getServiceCategoryPath($service_category_id) {
		$query = $this->db->query("SELECT GROUP_CONCAT(scp.path_id ORDER BY scp.level SEPARATOR '_') AS path FROM " . DB_PREFIX . "service_category_path scp LEFT JOIN " . DB_PREFIX . "service_category sc ON (scp.path_id = sc.service_category_id) LEFT JOIN " . DB_PREFIX . "service_category_to_store sc2s ON (scp.path_id = sc2s.service_category_id) WHERE scp.service_category_id = '" . (int)$service_category_id . "' AND sc.status = '1' AND sc2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['path'] ?? '';
	}

	private function getArticlePath($article_id) {
		$article_id = (int)$article_id;

		$query = $this->db->query("SELECT a2bc.blog_category_id FROM " . DB_PREFIX . "article_to_blog_category a2bc LEFT JOIN " . DB_PREFIX . "blog_category bc ON (a2bc.blog_category_id = bc.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category_to_store bc2s ON (a2bc.blog_category_id = bc2s.blog_category_id) WHERE a2bc.article_id = '" . $article_id . "' AND bc.status = '1' AND bc2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY a2bc.main_blog_category DESC LIMIT 1");

		if ($query->num_rows) {
			return $this->getBlogCategoryPath($query->row['blog_category_id']);
		}

		return '';
	}

	private function getBlogCategoryPath($blog_category_id) {
		$query = $this->db->query("SELECT GROUP_CONCAT(bcp.path_id ORDER BY bcp.level SEPARATOR '_') AS path FROM " . DB_PREFIX . "blog_category_path bcp LEFT JOIN " . DB_PREFIX . "blog_category bc ON (bcp.path_id = bc.blog_category_id) LEFT JOIN " . DB_PREFIX . "blog_category_to_store bc2s ON (bcp.path_id = bc2s.blog_category_id) WHERE bcp.blog_category_id = '" . (int)$blog_category_id . "' AND bc.status = '1' AND bc2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['path'] ?? '';
	}

	private function getDynamicPagePath($page_id) {
		$page_id = (int)$page_id;

		$query = $this->db->query("SELECT dptc.category_id FROM " . DB_PREFIX . "dynamic_page_to_category dptc LEFT JOIN " . DB_PREFIX . "dynamic_category dc ON (dptc.category_id = dc.category_id) LEFT JOIN " . DB_PREFIX . "dynamic_category_to_store dc2s ON (dptc.category_id = dc2s.category_id) WHERE dptc.page_id = '" . $page_id . "' AND dc.status = '1' AND dc2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY dptc.main_category DESC, dc.sort_order ASC LIMIT 1");

		if ($query->num_rows) {
			return $this->getDynamicCategoryPath($query->row['category_id']);
		}

		return '';
	}

	private function getDynamicCategoryPath($category_id) {
		$query = $this->db->query("SELECT GROUP_CONCAT(dcp.path_id ORDER BY dcp.level SEPARATOR '_') AS path FROM " . DB_PREFIX . "dynamic_category_path dcp LEFT JOIN " . DB_PREFIX . "dynamic_category dc ON (dcp.path_id = dc.category_id) LEFT JOIN " . DB_PREFIX . "dynamic_category_to_store dc2s ON (dcp.path_id = dc2s.category_id) WHERE dcp.category_id = '" . (int)$category_id . "' AND dc.status = '1' AND dc2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row['path'] ?? '';
	}

	private function getCurrentUrl() {
		$scheme = (!empty($this->request->server['HTTPS']) && ($this->request->server['HTTPS'] == 'on' || $this->request->server['HTTPS'] == '1')) ? 'https' : 'http';
		$host = $this->request->server['HTTP_HOST'] ?? ($this->request->server['SERVER_NAME'] ?? '');

		return $scheme . '://' . $host . ($this->request->server['REQUEST_URI'] ?? '/');
	}

	private function normalizeUrl($url) {
		$parts = parse_url(str_replace('&amp;', '&', $url));

		if (!$parts) {
			return $url;
		}

		$path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
		$query = array();

		if (isset($parts['query'])) {
			parse_str($parts['query'], $query);
			ksort($query);
		}

		return strtolower($parts['scheme'] ?? '') . '://' . strtolower($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($path ?: '/') . ($query ? '?' . http_build_query($query) : '');
	}

	private function getRoute() {
		if (isset($this->request->get['_route_'])) {
			return $this->request->get['_route_'];
		}

		$request_uri = $_SERVER['REQUEST_URI'] ?? '';

		// Strip query string
		if (($pos = strpos($request_uri, '?')) !== false) {
			$request_uri = substr($request_uri, 0, $pos);
		}

		// Strip script name (e.g. /index.php)
		$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
		$base = rtrim(dirname($script_name), '/');
		if ($base !== '/' && $base !== '') {
			if (strpos($request_uri, $base) === 0) {
				$request_uri = substr($request_uri, strlen($base));
			}
		}

		$path = trim($request_uri, '/');

		if ($path === '' || $path === 'index.php') {
			return '';
		}

		return $path;
	}
}
