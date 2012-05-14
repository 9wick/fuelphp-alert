<?php

//

namespace Alert;

class Error extends \Fuel\Core\Error {

    public static function show_php_error(\Exception $e) {
        $body = "";

        if (\Fuel::$is_cli) {
            $fatal = (bool) (!in_array($e->getCode(), \Config::get('errors.continue_on', array())));
            $data = static::prepare_exception($e, $fatal);
            if ($fatal) {
                $data['contents'] = ob_get_contents();
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                ob_start(\Config::get('ob_callback', null));
            } else {
                static::$non_fatal_cache[] = $data;
            }

            $body = $data['severity'] . ' - ' . $data['message'] . ' in ' . \Fuel::clean_path($data['filepath']) . ' on line ' . $data['error_line'];
        } else {
            ob_start();
            parent::show_php_error($e);
            $body = ob_get_contents();
            ob_end_clean();
        }
        self::mail($body);
    }

    public static function notice($msg, $always_show = false) {
        ob_start();
        parent::notice($msg, $always_show);
        $body = ob_get_contents();
        ob_end_clean();
        self::mail($body);
    }

    public static function show_production_error(\Exception $e) {
        // always mail the php error
        return static::show_php_error($e);

        if (!headers_sent()) {
            $protocol = \Input::server('SERVER_PROTOCOL') ? \Input::server('SERVER_PROTOCOL') : 'HTTP/1.1';
            header($protocol . ' 500 Internal Server Error');
        }
        exit(\View::forge('errors' . DS . 'production'));
    }

    public static function mail($body) {
        if (empty($body)) {
            return;
        }

        \Fuel\Core\Config::load('alert', true);
        $to = \Fuel\Core\Config::get('alert.to', NULL);
        $from = \Fuel\Core\Config::get('alert.from', NULL);
        $subject = \Fuel\Core\Config::get('alert.subject', 'Error');
        $header = "From:" . $from . "\n"
                . "MIME-Version: 1.0\n"
                . "Content-Type: text/html;";
        mail($to, $subject, $body, $header);

        if (\Fuel\Core\Fuel::$env != \Fuel\Core\Fuel::PRODUCTION && \Fuel\Core\Config::get('alert.mail_and_show', true)) {
            echo $body;
        }
    }

}