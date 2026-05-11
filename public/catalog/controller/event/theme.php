<?php
class ControllerEventTheme extends Controller {
	public function index(&$route, &$args, &$code) {
		if (is_file(DIR_TEMPLATE . 'template/' . $route . '.twig')) {
			$this->config->set('template_directory', 'template/');
		}

		// If there is a theme override we should get it
		$this->load->model('design/theme');

		$theme_info = $this->model_design_theme->getTheme($route, 'default');

        if ($theme_info && !is_file(DIR_MODIFICATION . 'catalog/view/template/'. $route . '.twig')) {
			$code = html_entity_decode($theme_info['code'], ENT_QUOTES, 'UTF-8');
		}
	}
}
