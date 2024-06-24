<?php

/**
 * Class Utils
 */
class Utils
{
    public static function secureString($str) {
        return DB::getInstance()->real_escape_string(htmlentities(trim($str)));
    }

    public static function formatPrice($price, $euro=TRUE) {
        $price ??= 0;
        $str = number_format($price,2,",",".");
        if ($euro) $str .= " &euro;";
        return $str;
    }

    public static function aesencrypt($str) {
        $str ??= "";
        return openssl_encrypt($str, "AES-256-CBC", \SECRET);
    }

    public static  function aesdecrypt($str) {
        $str ??= "";
        return openssl_decrypt($str, "AES-256-CBC", \SECRET);
    }

    /* deprecated */
    public static function secure($str) {
        return self::escape($str);
    }

    public static function escape($str) {
        $str ??= "";
        return trim(DB::getInstance()->real_escape_string($str));
    }

    public static function parse($str) {
        $str = strip_tags($str);
        $search = array(
            "italic" => "'(^|\s+)\/(.*?)\/($|\s+)'is",
            "bold" => "'(^|\s+)\*(.*?)\*($|\s+)'is",
            "stroke" => "'(^|\s+)\~(.*?)\~($|\s+)'is",
            "underline" => "'(^|\s+)\_(.*?)\_($|\s+)'is",
            "url" => '#((https?|ftp)://(\S*?\.\S*?))([\s)\[\]{},;"\':<]|\.\s|$)#i'
        );

        $replace = array(
            "italic" => "\\1<em>\\2</em>\\3",
            "bold" => "\\1<strong>\\2</strong>\\3",
            "stroke" => "\\1<s>\\2</s>\\3",
            "underline" => "\\1<u>\\2</u>\\3",
            "url" => "<a href=\"$1\" target=\"_blank\">$3</a>$4"
        );

        $str = preg_replace($search, $replace, $str);

        $trans = array ("\n" => "<br/>" , "\l" => "", chr(128) => "&euro;");
        $str = trim(nl2br($str));
        #$str = strtr(trim($str), $trans);

        return $str;
    }

    public static function getTimeDifference(\DateTime $datetime) {
        $interval = date_create('now')->diff( $datetime );
        $suffix = ( $interval->invert ? ' ago' : '' );
        if ( $v = $interval->y >= 1 ) return self::pluralize( $interval->y, 'Jahr', 'Jahren' );
        if ( $v = $interval->m >= 1 ) return self::pluralize( $interval->m, 'Monat', 'Monaten');
        if ( $v = $interval->d >= 1 ) return self::pluralize( $interval->d, 'Tag', 'Tagen');
        if ( $v = $interval->h >= 1 ) return self::pluralize( $interval->h, 'Stunde', 'Stunden');
        if ( $v = $interval->i >= 1 ) return self::pluralize( $interval->i, 'Minute', 'Minuten');
        if ( $v = $interval->s >= 10 ) return self::pluralize( $interval->s, 'Sekunde', 'Sekunden');
        return "gerade eben";
    }

    public static function pluralize($count, $singular, $plural) {
        return "vor ".$count . " " . ( ( $count == 1 ) ? $singular : $plural );
    }

    public static function parseUrl($str) {
        $search = array(
            "'(http://www\.|http://|https://)([0-9a-z][0-9a-z-][0-9a-z-\.]*[0-9a-z]\.)+([a-z]{2,4}|museum)(/[\\?\\=\\&\\/\\#\.a-z0-9_-]+)*'is"
        );

        $replace= array(
            "<a href=\"\\1\\2\\3\\4\" target=\"_blank\">\\1\\2\\3\\4</a>"
        );

        $str = preg_replace ($search, $replace, $str);
        return $str;
    }

    public static function encrypt($string, $algorithm="password_hash") {
        $salt = \PW_SALT;
        return password_hash($string.$salt, PASSWORD_BCRYPT);
    }

    public static function password_verify($pw, $hash) {
        $salt = \PW_SALT;
        return password_verify($pw.$salt, $hash);
    }

    // formatiert Preisausgabe
    public static function price($price, $euro=TRUE) {
        $str = number_format($price,2,",",".");
        if ($euro) $str .= "&nbsp;&euro;";
        return $str;
    }

    // validiert E-Mail-Adresse
    public static function validateEmail($email)
    {
        return preg_match("'([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@([_a-zA-Z0-9-]+\.)+([a-zA-Z]+))'is", $email);
    }

    // validiert eine URL
    public static function is_url($url) {
        $info = self::get_url_info($url);
        if ($info["http_code"] == 0) return false;
        return true;
    }

    /**
     * Holt Infos zu einer URL
     *
     * @param string $url Die zu prüfende URL
     * @param boolean $followlocation Soll einer Weiterleitung gefolgt werden? Standard:true
     * @param string $filename Wenn angegeben, wird der Inhalt in $filenamer geschrieben.
     *
     * @return array Infos zur URL (Curl-Result)
     **/
    public static function get_url_info($url, $followlocation=true, $filename=NULL) {
        $useragent = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:12.0) Gecko/20100101 Firefox/12.0";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followlocation);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $c = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($filename) file_put_contents($filename, $c);
        return $info;
    }

    // setzt eine Wert als Cookie und in die Session
    public static function setSessionValue($var, $val) {
        $_SESSION[$var] = $val;
    }

    // setzt eine Wert als Cookie und in die Session
    public static function setCSValue($var, $val, $cookie) {
        $val = self::decodeAjaxString($val);
        $_SESSION[$var] = $val;
        if ($cookie) {
            if (!$_COOKIE[$var]) self::setCookie($var, $val);
            else {
                self::deleteCookie($var);
                self::setCookie($var, $val);
            }
        }
        else {
            setcookie($var, NULL, time()-1000, "");
        }
    }

    // setzt eine Wert als Cookie
    public static function setCookie($var, $val) {
        if (!$_COOKIE[$var])
            setcookie($var, $val, time()+60*60*24*3650, "/", COOKIE_DOMAIN, false, true);
        else {
            self::deleteCookie($var);
            setcookie($var, $val, time()+60*60*24*3650, "/", COOKIE_DOMAIN, false, true);
        }
    }

    // setzt eine Wert als Cookie
    public static function deleteCookie($var) {
        $host = $_SERVER["HTTP_HOST"];
        setcookie($var, "", time()-10000, "/", COOKIE_DOMAIN, false, true);
        setcookie($var, "", time()-10000, "/", ".".$host, false, true);
    }

    // gibt einen Wert aus dem Post, Cookie und Session (Priorit�t absteigend) zur�ck
    public static function getCSValue($var) {
        $tmp = $_SESSION[$var];
        if (!$tmp && $_COOKIE[$var]) $tmp = $_COOKIE[$var];
        if ($_POST[$var]) $tmp = $_POST[$var];
        return $tmp;
    }

    // löscht eine Wert
    public static function unsetCSValue($var) {
        unset($_POST[$var]);
        unset($_SESSION[$var]);
        unset($_COOKIE[$var]);
    }
     /**
      * Normalisiert einen String, so dass er für eine URL verwendet werden kann
      **/
     public static function normalizeURL($menueURL) {
         $menueURL = preg_replace ('/<[^>]*>/', ' ', $menueURL);
         $menueURL = trim(strtolower($menueURL));
         $search = array("ä", "ö", "ü", "Ä", "Ö", "Ü", "ß", "é", "è", "á", "à");
         $replace = array("ae", "oe", "ue", "Ae", "Oe", "Ue", "ss", "e", "e", "a", "a");
         $menueURL = str_replace($search, $replace, $menueURL);
         $menueURL = preg_replace("'[\W|_]+'is", "-", $menueURL);
         $menueURL = strtolower($menueURL);
         return $menueURL;
     }

     public static function decodeAjaxString($string){
         $search = array(
             "%E4", "%F6", "%FC",
             "%C4", "%D6", "%DC",
             "%u00E4", "%u00F6", "%u00FC",
             "%u00C4", "%u00D6", "%u00DC",
             "%u00DF", "%u20AC", "%u00b3"
         );
         $replace = array(
             "ä", "ö", "ü",
             "Ä", "Ö", "Ü",
             "ä", "ö", "ü",
             "Ä", "Ö", "Ü",
             "ß", "&euro;", "³"
         );

         $string = str_replace($search, $replace, $string);
         $string = self::str_encode($string);
         return $string;
     }

     /**
      * Kodiert einen String nach $encodeto
      * Dabei wird geprüft, welche Kodierung der String momentan hat.
      *
      * @param String $string der String, der kodiert werden soll
      * @param String $encodeto Wie soll kodiert werden? z.B. UFT-8, ISO-8859-1, UTF-16, ...
      *
      * @return String der kodierte String
      **/
     static function str_encode($string, $encodeto = "UTF-8")
     {
        $string ??= "";
        $codes = "UTF-8, UTF-7, ISO-8859-1, ISO-8859-15, windows-1251";
        $from = mb_detect_encoding($string, $codes);
        return mb_convert_encoding($string, $encodeto, $from);
     }

     public static function getMimeType($file) {
         $mime_types = array(
             'txt' => 'text/plain',
             'htm' => 'text/html',
             'html' => 'text/html',
             'php' => 'text/html',
             'css' => 'text/css',
             'js' => 'application/javascript',
             'json' => 'application/json',
             'xml' => 'application/xml',
             'swf' => 'application/x-shockwave-flash',
             'flv' => 'video/x-flv',

             // images
             'png' => 'image/png',
             'jpe' => 'image/jpeg',
             'jpeg' => 'image/jpeg',
             'jpg' => 'image/jpeg',
             'gif' => 'image/gif',
             'bmp' => 'image/bmp',
             'ico' => 'image/vnd.microsoft.icon',
             'tiff' => 'image/tiff',
             'tif' => 'image/tiff',
             'svg' => 'image/svg+xml',
             'svgz' => 'image/svg+xml',

             // archives
             'zip' => 'application/zip',
             'rar' => 'application/x-rar-compressed',
             'exe' => 'application/x-msdownload',
             'msi' => 'application/x-msdownload',
             'cab' => 'application/vnd.ms-cab-compressed',

             // audio/video
             'mp3' => 'audio/mpeg',
             'qt' => 'video/quicktime',
             'mov' => 'video/quicktime',

             // adobe
             'pdf' => 'application/pdf',
             'psd' => 'image/vnd.adobe.photoshop',
             'ai' => 'application/postscript',
             'eps' => 'application/postscript',
             'ps' => 'application/postscript',

             // ms office
             'rtf' => 'application/rtf',
             'doc' => 'application/msword',
             'docx' => 'application/msword',
             'xls' => 'application/vnd.ms-excel',
             'xlsx' => 'application/vnd.ms-excel',
             'ppt' => 'application/vnd.ms-powerpoint',
             'pps' => 'application/vnd.ms-powerpoint',
             'pptx' => 'application/vnd.ms-powerpoint',

             // open office
             'odt' => 'application/vnd.oasis.opendocument.text',
             'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
         );

         $ext = strtolower(array_pop(explode('.',$file)));
         if (array_key_exists($ext, $mime_types)) {
             return $mime_types[$ext];
         } else {
             return 'application/octet-stream';
         }
     }

     /**
      * Gibt den Wochentag auf Deutsch zurück.
      *
      * @param $day Integer Der Wochenstag. 0= Sonntag, 1= Montag, ...
      * @param $short boolean Kurze Rückgabe? Mo, Di, Mi, ...
      *
      * @return String Der Wochentag
      **/
     public static function getDayname($day, $short = false) {
         $weekdays_long = array(0 => "Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
         $weekdays_short = array(0 => "So", "Mo", "Di", "Mi", "Do", "Fr", "Sa");
         return ($short) ? $weekdays_short[$day] : $weekdays_long[$day];
     }

     /**
      * Gibt den Monat auf Deutsch zurück.
      *
      * @param $month Integer Der Wochenstag. 1= Januar, 2= Februar, ...
      * @param $short boolean Kurze Rückgabe? Jan, Feb, ...
      *
      * @return String Der Wochentag
      **/
     public static function getMonthname($month, $short = false) {
         $months_long = array(1 => "Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
         $months_short = array(1 => "Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez");
         return ($short) ? $months_short[$month] : $months_long[$month];
     }

     /**
      * Diese Funktion kürzt einen String auf eine bestimmte Länge und entfernt dabei auch alle HTML-Tags.
      *
      * @param String $string Der zu kürzende String
      * @param Integer $length Wie lang soll das Ergebnis sein? Standard: 200 Zeichen
      *
      * @return String der gekürzte String
      **/
     public static function shortenString($string, $length=200, $expansion = true) {
         $content = preg_replace ('/<[^>]*>/', ' ', $string);
         $content = preg_replace('|\s+|is', ' ', $content);
         $content = trim($content);
         $content2 = wordwrap($content, $length, "|||( . Y . )|||");
         $content2 = explode("|||( . Y . )|||", $content2);
         $content2 = $content2[0];
         if ($expansion && $content2 != $content) $content2 .= " ...";
         return $content2;
     }

    /**
     *  Diese Funktion ersetzt die beim Decodieren von JSON entstehenden Sonderzeichen
     *
     * @param string $string    Der String, in dem die Sonderzeichen ersetzt werden müssen
     * @return string Der geänderte String
     */
    public static function json_character($string) {
        $trans  = array("u00e4" => "ä", "u00f6" => "ö", "u00fc" => "ü", "u00C4" => "Ä", "u00D6" => "Ö", "u00DC" => "Ü", "u00c4" => "Ä", "u00d6" => "Ö", "u00dC" => "Ü", "u00df" => "ß", "u00b3" => "³");
        return strtr($string, $trans);
    }

    /**
     * This function returns a new uuid
     *
     * @return string UUID
     * 
     * 340.282.366.920.938.463.463.374.607.431.768.211.456 - Theoretisch, aber muss durch 64 geteilt werden
     * 5.316.911.983.139.663.491.615.228.241.121.378.304 - Möglichkeiten !!!
     */
    public static function getUUID() {
        $data = PHP_MAJOR_VERSION < 7 ? openssl_random_pseudo_bytes(16) : random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // Set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
