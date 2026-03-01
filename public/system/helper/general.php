<?php
function token($length = 32) {
	// Create random token
	$string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	
	$max = strlen($string) - 1;
	
	$token = '';
	
	for ($i = 0; $i < $length; $i++) {
		$token .= $string[mt_rand(0, $max)];
	}	
	
	return $token;
}

function translit($string)
{
    // Массив для транслитерации русских символов
    $translit = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo',
        'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M',
        'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
        'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '',
        'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya'
    ];

    // Преобразуем русские символы в транслит
    $string = strtr($string, $translit);

    $string = str_replace([' ','_'], '-', $string);
    
    // Оставляем только строчные буквы, цифры и дефисы
    $string = preg_replace('/[^a-z0-9-]/', '', strtolower($string));

    // Удаляем возможные двойные дефисы
    $string = preg_replace('/-+/', '-', $string);

    // Удаляем дефисы в начале и конце строки
    $string = trim($string, '-');

    return $string;
}
/**
 * Backwards support for timing safe hash string comparisons
 * 
 * http://php.net/manual/en/function.hash-equals.php
 */

if(!function_exists('hash_equals')) {
	function hash_equals($known_string, $user_string) {
		$known_string = (string)$known_string;
		$user_string = (string)$user_string;

		if(strlen($known_string) != strlen($user_string)) {
			return false;
		} else {
			$res = $known_string ^ $user_string;
			$ret = 0;

			for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);

			return !$ret;
		}
	}
}
function writelog($text, $file = false, $color = null, $date = true)
{
    // ANSI color codes
    $colors = [
        'black'         => '0;30',
        'red'           => '0;31',
        'green'         => '0;32',
        'yellow'        => '0;33',
        'blue'          => '0;34',
        'purple'        => '0;35',
        'cyan'          => '0;36',
        'white'         => '0;37',
        'bright_black'  => '1;30',
        'bright_red'    => '1;31',
        'bright_green'  => '1;32',
        'bright_yellow' => '1;33',
        'bright_blue'   => '1;34',
        'bright_purple' => '1;35',
        'bright_cyan'   => '1;36',
        'bright_white'  => '1;37',
    ];

    if ($file AND !$color AND $colors[$file]) {
        $color = $file;
        $file  = false;
    }
    if (is_array($text)) {
        $text = json_encode($text, JSON_UNESCAPED_UNICODE);
    }
    $line = ($date ? (date('Y-m-d H:i:s') . ' ') : '') . $text;
        
    
    // Определяем, является ли запрос AJAX
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // В файл записываем без цветовых кодов
    if ($file) {
        file_put_contents(DIR_LOGS . $file . '.log', $line . PHP_EOL, FILE_APPEND);
    }
    if(!$isAjax){
        // Добавляем цвет для вывода в консоль
        if ($color && isset($colors[$color]) && posix_isatty(STDOUT)) {
            $coloredLine = "\e[" . $colors[$color] . 'm' . $line . "\e[0m";
            echo $coloredLine . PHP_EOL;
        } else {
            echo $line . PHP_EOL;
        }
    } else {
        return $line;
    }    
    

    
}