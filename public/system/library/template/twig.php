<?php

namespace Template;
final class Twig {
	private $twig;
	private $data = array();
	
	public function __construct() {
		// include and register Twig auto-loader
		/* include_once(DIR_SYSTEM . 'library/template/Twig/Autoloader.php');
		
		\Twig_Autoloader::register(); */
	}
	
	public function set($key, $value) {
		$this->data[$key] = $value;
	}
	
	public function render(string $filename, string $code = ''): string {
    if (!$code) {
        $file = DIR_TEMPLATE . $filename . '.twig';

        if (is_file($file)) {
            $code = file_get_contents($file);
        } else {
            throw new \Exception('Error: Could not load template ' . $file . '!');
        }
    }

    // initialize Twig environment
    $config = [
        'autoescape'  => false,
        'debug'       => false,
        'auto_reload' => true,
        'cache'       => DIR_CACHE . 'template/'
    ];

    try {
        $loader1 = new \Twig\Loader\ArrayLoader([$filename . '.twig' => $code]);
        $loader2 = new \Twig\Loader\FilesystemLoader([DIR_TEMPLATE]); // to find further includes
        $loader = new \Twig\Loader\ChainLoader([$loader1, $loader2]);

        $twig = new \Twig\Environment($loader, $config);
        $twig->addFunction(new \Twig\TwigFunction('form', function (\Twig\Environment $env, array $context, array $data = []) {
            return $env->render('tool/form.twig', array_merge($context, $data));
        }, ['needs_environment' => true, 'needs_context' => true, 'is_safe' => ['html']]));
        $twig->addFunction(new \Twig\TwigFunction('resize', function ($filename, $width, $height, $mode = 'resize') {
            $image_new = function_exists('resize_image') ? resize_image($filename, $width, $height, $mode) : '';

            if (!$image_new) {
                return '';
            }

            if (substr($image_new, 0, strlen(DIR_IMAGE)) == DIR_IMAGE) {
                return $image_new;
            }

            $catalog = (defined('HTTPS_CATALOG') ? HTTPS_CATALOG : (defined('HTTP_CATALOG') ? HTTP_CATALOG : ''));

            return $catalog ? $catalog . 'image/' . $image_new : 'image/' . $image_new;
        }));
        $twig->addFilter(new \Twig\TwigFilter('unescape', function ($value) {
            return html_entity_decode((string)$value, ENT_QUOTES, 'UTF-8');
        }, ['is_safe' => ['html']]));

        return $twig->render($filename . '.twig', $this->data);
    } catch (\Exception $e) {
        trigger_error('Error: Could not load template ' . $filename . '! Error: ' . $e->getMessage());
        throw $e;
    }    
}
}
