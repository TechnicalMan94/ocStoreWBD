<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ControllerExtensionThemeDefault extends Controller {
	public function index() {
		$this->response->redirect($this->url->link('setting/setting', 'user_token=' . $this->session->data['user_token'], true));
	}
}
