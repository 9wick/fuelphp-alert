<?php

//

namespace Alert;

class Error extends \Fuel\Core\Error {

    public static function show_php_error(\Exception $e) {
        self::mail_exception($e);
        parent::show_php_error($e);
    }

    public static function notice($msg, $always_show = false) {
        ob_start();
        parent::notice($msg, $always_show);
        $body = ob_get_contents();
        ob_end_clean();
        self::mail($body);
    }

    public static function show_production_error(\Exception $e) {
        self::mail_exception($e);
        parent::show_production_error($e);
    }

    public static function mail($body) {
        if (empty($body)) {
            return;
        }
        if (\Fuel\Core\Fuel::$env == \Fuel\Core\Fuel::PRODUCTION && \Fuel\Core\Config::get('alert.production_only', true)) {

            \Fuel\Core\Config::load('alert', true);
            $to = \Fuel\Core\Config::get('alert.to', NULL);
            $from = \Fuel\Core\Config::get('alert.from', NULL);
            $subject = \Fuel\Core\Config::get('alert.subject', 'Error');
            $header = "From:" . $from . "\n"
                    . "MIME-Version: 1.0\n"
                    . "Content-Type: text/html;";
            mail($to, $subject, $body, $header);
        }

        if (\Fuel\Core\Fuel::$env != \Fuel\Core\Fuel::PRODUCTION && \Fuel\Core\Config::get('alert.mail_and_show', true)) {
            echo $body;
        }
    }
    
    public static function mail_exception(\Exception $e){
        $body = "";
        $data = static::prepare_exception($e);

		$data['contents'] = ob_get_contents();
		$data['non_fatal'] = static::$non_fatal_cache;
		try{
				$body =  \View::forge('errors'.DS.'php_fatal_error', $data, false);
		}catch (\FuelException $view_exception){
				$body = $data['severity'].' - '.$data['message'].' in '.\Fuel::clean_path($data['filepath']).' on line '.$data['error_line'];
    	}

        self::mail($body);
    }

}