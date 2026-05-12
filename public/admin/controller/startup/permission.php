<?php
class ControllerStartupPermission extends Controller {
	public function index() {
		if (isset($this->request->get['route'])) {
			$route = '';

			$part = explode('/', $this->request->get['route']);

			if (isset($part[0])) {
				$route .= $part[0];
			}

			if (isset($part[1])) {
				$route .= '/' . $part[1];
			}

			// If a 3rd part is found we need to check if its under one of the extension folders.
			$extension = array(
				'extension/advertise',
				'extension/dashboard',
				'extension/analytics',
				'extension/captcha',
                'extension/currency',
				'extension/extension',
				'extension/feed',
				'extension/fraud',
				'extension/module',
				'extension/payment',
				'extension/shipping',
				'extension/theme',
				'extension/total',
				'extension/report'
			);

			if (isset($part[2]) && in_array($route, $extension)) {
				$route .= '/' . $part[2];
			}

			// We want to ingore some pages from having its permission checked.
			$ignore = array(
				'common/dashboard',
				'common/language',
				'common/login',
				'common/logout',
				'common/forgotten',
				'common/reset',
				'error/not_found',
				'error/permission'
			);

			if (!in_array($route, $ignore) && !$this->user->hasPermission('access', $route)) {
				// For dynamic/* routes, also check section-specific permission
				$allowed = false;
				if (strpos($route, 'dynamic/') === 0 && $route !== 'dynamic/section' && isset($this->request->get['section_id'])) {
					$this->load->model('dynamic/section');
					$section = $this->model_dynamic_section->getSection((int)$this->request->get['section_id']);
					if ($section && $this->user->hasPermission('access', $route . '_' . $section['code'])) {
						$allowed = true;
					}
				}
				if (!$allowed) {
					return new Action('error/permission');
				}
			}
		}
	}
}
