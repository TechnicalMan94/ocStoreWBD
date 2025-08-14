<?php

class ControllerMarketplacePromotion extends Controller {
	public function index() {
		return "";
	}

    private function strip($string, $config) {
        $purifier = new HTMLPurifier($config);
        if (is_array($string))  {
            foreach ($string as $k => $v) {
                $string[$k] = $this->strip($v, $config); } return $string;
        }
        return $purifier->purify($string);
    }
}