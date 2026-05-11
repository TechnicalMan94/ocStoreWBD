<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerCommonLanguage extends Controller {
	public function index() {
		$this->load->language('common/header');

		$data['action'] = $this->url->link('common/language/language', 'user_token=' . $this->session->data['user_token'], true);

		$data['code'] = $this->session->data['admin_language'];
		$data['text_language'] = $this->language->get('text_language');

		$this->load->model('localisation/language');

		$data['languages'] = array();

		$results = $this->model_localisation_language->getLanguages();

		foreach ($results as $result) {
			if ($result['status']) {
				$data['languages'][] = array(
					'name' => $result['name'],
					'code' => $result['code']
				);
			}
		}

		if (!isset($this->request->get['route'])) {
			$data['redirect'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$url_data = $this->request->get;

			$route = $url_data['route'];

			unset($url_data['route']);

			$url = '';

			if ($url_data) {
				$url = '&' . urldecode(http_build_query($url_data, '', '&'));
			}

			$data['redirect'] = $this->url->link($route, $url, true);
		}

		return $this->load->view('common/language', $data);
	}

	public function language() {
		if (isset($this->request->post['code'])) {
			$this->load->model('localisation/language');

			$languages = $this->model_localisation_language->getLanguages();

			if (isset($languages[$this->request->post['code']]) && $languages[$this->request->post['code']]['status']) {
				$this->session->data['admin_language'] = $this->request->post['code'];

				setcookie('admin_language', $this->request->post['code'], time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);
			}
		}

		if (isset($this->request->post['redirect'])) {
			$this->response->redirect($this->request->post['redirect']);
		} else {
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}
	}
}
