<?php
class ControllerStartupStartup extends Controller {
	public function index() {
		// Settings
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0'");
		
		foreach ($query->rows as $setting) {
			if (!$setting['serialized']) {
				$this->config->set($setting['key'], $setting['value']);
			} else {
				$this->config->set($setting['key'], json_decode($setting['value'], true));
			}
		}

		// Set time zone
		if ($this->config->get('config_timezone')) {
			date_default_timezone_set($this->config->get('config_timezone'));

			// Sync PHP and DB time zones.
			$this->db->query("SET time_zone = '" . $this->db->escape(date('P')) . "'");
		}

		// Theme
		$this->config->set('template_cache', $this->config->get('developer_theme'));
				
		// Language
		$code = '';

		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();

		if (isset($this->session->data['admin_language'])) {
			$code = $this->session->data['admin_language'];
		}

		if (isset($this->request->cookie['admin_language']) && (!isset($languages[$code]) || !$languages[$code]['status'])) {
			$code = $this->request->cookie['admin_language'];
		}

		if (!isset($languages[$code]) || !$languages[$code]['status']) {
			$code = $this->config->get('config_admin_language');
		}

		if (!isset($languages[$code])) {
			$code = $this->config->get('config_language');
		}

		if (!isset($languages[$code])) {
			$code = key($languages);
		}

		if ($code && isset($languages[$code])) {
			if (!isset($this->session->data['admin_language']) || $this->session->data['admin_language'] != $code) {
				$this->session->data['admin_language'] = $code;
			}

			if (!isset($this->request->cookie['admin_language']) || $this->request->cookie['admin_language'] != $code) {
				setcookie('admin_language', $code, time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
			}

			$this->config->set('config_admin_language', $code);
			$this->config->set('config_language_id', $languages[$code]['language_id']);
		}

		// Language
		$language = new Language($this->config->get('config_admin_language'));
		$language->load($this->config->get('config_admin_language'));
		$this->registry->set('language', $language);
		
		// Customer
		$this->registry->set('customer', new Cart\Customer($this->registry));

		// Currency
		$this->registry->set('currency', new Cart\Currency($this->registry));
	
		// Tax
		$this->registry->set('tax', new Cart\Tax($this->registry));
		
		if ($this->config->get('config_tax_default') == 'shipping') {
			$this->tax->setShippingAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		if ($this->config->get('config_tax_default') == 'payment') {
			$this->tax->setPaymentAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
		}

		$this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));

		// Weight
		$this->registry->set('weight', new Cart\Weight($this->registry));
		
		// Length
		$this->registry->set('length', new Cart\Length($this->registry));
		
		// Cart
		$this->registry->set('cart', new Cart\Cart($this->registry));
		
		// Encryption
		$this->registry->set('encryption', new Encryption($this->config->get('config_encryption')));
	}
}
