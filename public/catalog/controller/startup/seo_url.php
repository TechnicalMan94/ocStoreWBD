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

					if ($query->row['query'] && $url[0] != 'information_id' && $url[0] != 'manufacturer_id' && $url[0] != 'category_id' && $url[0] != 'product_id') {
						$this->request->get['route'] = $query->row['query'];
					}
				} else {
					$this->request->get['route'] = 'error/not_found';

					break;
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

				if (isset($data['route']) && (($data['route'] == 'product/product' && $key == 'product_id') || ($data['route'] == 'product/manufacturer/info' && $key == 'manufacturer_id') || ($data['route'] == 'information/information' && $key == 'information_id'))) {
					$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE `query` = '" . $this->db->escape($key . '=' . (int)$value) . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");

					if ($query->num_rows && $query->row['keyword']) {
						$url .= '/' . $query->row['keyword'];
						$seo = true;

						unset($data[$key]);
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
