<?php if (!defined('MS_XTIGER')) exit('Access Denied');
define('XT_VERSION', '2.0.0');
define('XT_TIME', time());
define('TIMEZONE', 'Etc/GMT-8');
if (PHP_VERSION > '5.1') {
    @date_default_timezone_set(TIMEZONE);
}
/**
 * Created by openXtiger.org
 * User: xTiger
 * Date: 2009-5-14
 * Time: 15:39:33
 */
/*
 * $_XC: xtiger configs & global configs
 * $_XR: xtiger routes
 * $_XM: xtiger mapping
 * $_XH_L: xtiger handle load
 * $_XH_D: xtiger handle data
 * $_AC: action configs
 * $_UC: user cache
 * $_XH_E: user cache
 * $_XH_LANG: lang
 */
$_XC = $_UD = $_XR = $_XH_L = $_XH_D = $_XH_L_DB = array();
$_UC = $_XH_E = $_XH_LANG = array();
/*
|--------------------------------------------------------------------------
| DEFAULT URI PROTOCOL
|--------------------------------------------------------------------------
|
| This item determines which server global should be used to retrieve the
| URI string.  The default setting of "AUTO" works for most servers.
| If your links do not seem to work, try one of the other delicious flavors:
|
| 'AUTO'			Default - auto detects
| 'PATH_INFO'		Uses the PATH_INFO
| 'QUERY_STRING'	Uses the QUERY_STRING
| 'REQUEST_URI'		Uses the REQUEST_URI
| 'ORIG_PATH_INFO'	Uses the ORIG_PATH_INFO
|
*/
$_XC['uri_protocol'] = 'AUTO';

/*
|--------------------------------------------------------------------------
| DEFAULT URL suffix
|--------------------------------------------------------------------------
|
| This option allows you to add a suffix to all URLs.
|
*/
$_XC['url_suffix'] = '.html';
/*
|--------------------------------------------------------------------------
| DEFAULT namespace prefix
|--------------------------------------------------------------------------
*/
$_XC['namespace_prefix'] = '';
/*
|--------------------------------------------------------------------------
| DEFAULT package name
|--------------------------------------------------------------------------
*/
$_XC['default_package_name'] = 'xtiger';
/*
|--------------------------------------------------------------------------
| Enable Query Strings
|--------------------------------------------------------------------------
|
| By default CodeIgniter uses search-engine friendly segment based URLs:
| example.com/who/what/where/
|
| You can optionally enable standard query string based URLs:
| example.com?who=me&what=something&where=here
|
| Options are: TRUE or FALSE (boolean)
|
|
*/
$_XC['enable_query_strings'] = FALSE;
$_XC['enable_rewrite_url'] = TRUE;
$_XC['action_trigger'] = 'c';
$_XC['method_trigger'] = 'm';

/*
|--------------------------------------------------------------------------
| DEFAULT action
|--------------------------------------------------------------------------
*/

$_XC['default_num_action'] = 'route';
$_XC['default_any_action'] = 'index';
$_XC['default_method'] = 'execute';

$_XC['view_ext'] = '.html.php';

$_XC['default_template'] = 'default';
$_XC['template'] = 'default';
$_XC['tplrefresh'] = TRUE;
$_XC['static_url'] = '';

//
$_XC['log_threshold'] = 1;
$_XC['default_xmarker_views'] = TRUE;
$_XC['gzipcompress'] = FALSE;
$_XC['language'] = 'zh_CN';
$_XC['timeoffset'] = 8;

$_XC['cookiepre'] = 'xtiger_';
$_XC['cookiepath'] = '/';
$_XC['cookiedomain'] = '';
$_XC['xtiger_key'] = '1a280fb72da5af08d7a8c9ea84c78b9f';
$_XC['xtiger_ip'] = '127.0.0.1';

ini_set('display_errors', E_ALL);
set_error_handler('xt_exception_handler'/*, E_ERROR | E_WARNING | E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE*/);
define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
unset($HTTP_ENV_VARS, $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_POST_FILES, $HTTP_COOKIE_VARS);
//set_magic_quotes_runtime(0); // Kill magic quotes
ini_set("magic_quotes_runtime", 0);
if ($_REQUEST) {
    if (MAGIC_QUOTES_GPC) {
        $_REQUEST = xt_addslashes($_REQUEST);
        if ($_COOKIE) $_COOKIE = xt_addslashes($_COOKIE);
    } else {
        $_POST = xt_addslashes($_POST);
        $_GET = xt_addslashes($_GET);
        $_COOKIE = xt_addslashes($_COOKIE);
    }
    //if($_COOKIE) $db->escape($_COOKIE);
}
/**
 * error_handler
 */
function xt_exception_handler($severity, $message, $filepath, $line)
{
    global $_XH_E;
    $dt = date("Y-m-d H:i:s B");
    $errortype = array(
        E_ERROR => 'Error',
        E_WARNING => 'Warning',
        E_PARSE => 'Parsing Error',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core Error',
        E_CORE_WARNING => 'Core Warning',
        E_COMPILE_ERROR => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR => 'User Error',
        E_USER_WARNING => 'User Warning',
        E_USER_NOTICE => 'User Notice',
        E_DEPRECATED => 'Deprecated',
        E_STRICT => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
    );
    // set of errors for which a var trace will be saved
    $user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
    $message = preg_replace("/\[<a href=.*?<\/a>\]/", '', $message);
    $filepath = pathinfo($filepath, PATHINFO_BASENAME);
    $_XH_E[] = array("$filepath($line)", $message, $errortype[$severity], $dt, $_SERVER["REMOTE_ADDR"]);
    //mail("phpframwork@gmail.com", "Phpframwork ERROR!", date("Y-m-d H:i:s")." {".$_SERVER["REMOTE_ADDR"]."} [$errortype[$errno]] $filepath($line)   $message");
}

function xt_log()
{
    global $_XH_E;
    if (count($_XH_E) > 0) {
        echo '<div ondblclick="this.style.display=\'none\'" style="width: 100%;clear: both;position: fixed;top: 0;background: #ffff00;"><pre style="text-align:left;font-size:14px">';
        foreach ($_XH_E as $v) {
            echo "  <span style='color:red'>$v[0] => $v[1] </span> <span style='color:#999999'>[$v[2]] $v[3] $v[4]</span>\n";
        }
        echo '</pre></div>';
    }
    /*echo '<fieldset>';
    echo '<legend><b>Include:</b> ';
    echo count(get_included_files());
    echo '</legend>';
    echo '<pre>'.print_r(get_included_files(), TRUE).'</pre>';
    echo '</fieldset>';*/

    /*echo '$cip:'.$cip = getenv('HTTP_CLIENT_IP');
    echo '<br/>$xip:'.$xip = getenv('HTTP_X_FORWARDED_FOR');
    echo '<br/>$rip:'.$rip = getenv('REMOTE_ADDR');
    echo '<br/>$srip:'.$srip = $_SERVER['REMOTE_ADDR'];*/
    //mail("phpframwork@gmail.com", "Phpframwork ERROR!", $err);
    /*error_log($err, 3, "/usr/local/php4/error.log");
    if ($errno == E_USER_ERROR) {
        mail("phpframwork@gmail.com", "Phpframwork ERROR!", $err);
    }*/
}

/**
 * check a key is in a array?
 * return value when in arrary,else return NULL
 */
function xt_hashmap($map, $key)
{
    if (empty($key)) return '';
    return array_key_exists($key, $map) ? $map[$key] : NULL;
}

function xt_lang_include($map, $key, $parse = TRUE)
{
    global $_XH_D;
    if (empty($key)) return '';
    $v = isset($map[$key]) ? $map[$key] : $key;
    $matchs = array();
    if ($parse && preg_match_all("/\{[^\}]*\}/", $v, $matchs)) {
        return preg_replace("/\{([^\}]*)\}/e", "xt_hashmap(\$_XH_D,'\\1')", $v);
    }
    return $v;
}

function xt_lang_start($lang)
{
    global $_XC, $_XH_LANG;
    $langdir = "language/$_XC[language]/";
    foreach (explode(',', $lang) as $v) {
        if (file_exists(MS_APPPATH . $langdir . $v . '.lang.php')) {
            include_once(MS_APPPATH . $langdir . $v . '.lang.php');
            if (function_exists('xt_' . $v . '_lang')) {
                $m = 'xt_' . $v . '_lang';
                $_XH_LANG = array_merge($m(), $_XH_LANG);
            }
        }
    }
}

function xt_lang($key, $d = '')
{
    global $_XH_LANG;
    return array_key_exists($key, $_XH_LANG) ? $_XH_LANG[$key] : ($d ? $d : $key);
}

function xt_lang1($key, $p = '@')
{
    global $_XH_LANG;
    if ($p) return xt_lang_include($_XH_LANG, $key);
    else return xt_lang($key);
}

/*function xt_lang2($key) {
    global $_XH_LANG;
    if(empty($key)) return '';
    var_dump($key);
    if(preg_match("/\(\![^\}]*\)/",$key)){
        return preg_replace("/\(\!([^\)]*)\}/e","xt_lang_include(\$_XH_LANG,'\\1')",$key);
    }
    return xt_lang_include($_XH_LANG, $key);
}*/
function xt_implode($array, $level = 0)
{
    $evaluate = "array(";
    $comma = "";
    foreach ($array as $key => $val) {
        $key = is_string($key) ? '\'' . addcslashes($key, '\'\\') . '\'=>' : '';
        $val = is_object($val) ? (array)$val : $val;
        if (!is_array($val)) {
            $val = '\'' . addcslashes($val, '\'\\') . '\'';
            $evaluate .= "$comma$key$val";
        } else {
            $evaluate .= "$comma$key" . xt_implode($val, $level + 1);
        }
        $comma = ",";
    }
    $evaluate .= ")";

    return $evaluate;
}

function xt_random($length, $numeric = 0)
{
    PHP_VERSION < '4.2.0' ? mt_srand((double)microtime() * 1000000) : mt_srand();
    $seed = base_convert(md5(print_r($_SERVER, 1) . microtime()), 16, $numeric ? 10 : 35);
    $seed = $numeric ? (str_replace('0', '', $seed) . '012340567890') : ($seed . 'zZ' . strtoupper($seed));
    $hash = '';
    $max = strlen($seed) - 1;
    for ($i = 0; $i < $length; $i++) {
        $hash .= $seed[mt_rand(0, $max)];
    }
    return $hash;
}

function xt_size($size, $prec = 3)
{
    $size = round(abs($size));
    $units = array(0 => " B ", 1 => " KB", 2 => " MB", 3 => " GB", 4 => " TB");
    if ($size == 0) return str_repeat(" ", $prec) . "0$units[0]";
    $unit = min(4, floor(log($size) / log(2) / 10));
    $size = $size * pow(2, -10 * $unit);
    $digi = $prec - 1 - floor(log($size) / log(10));
    $size = round($size * pow(10, $digi)) * pow(10, -$digi);
    return $size . $units[$unit];
}

function xt_fileext($filename)
{
    return trim(substr(strrchr($filename, '.'), 1, 10));
}

function xt_readdir($dir, $ext = '')
{
    $filearr = array();
    if (is_dir($dir)) {
        $filedir = dir($dir);
        $entry = NULL;
        if (!empty($ext)) {
            while (false !== ($entry = $filedir->read())) {
                if (strtolower(xt_fileext($entry)) == strtolower($ext)) {
                    $filearr[$entry] = $entry;
                }
            }
        } else {
            while (false !== ($entry = $filedir->read())) {
                if ($entry != '.' && $entry != '..') {
                    $filearr[$entry] = $entry;
                }
            }
        }
        $filedir->close();
    }
    return $filearr;
}

function xt_get($var, $d = '')
{
    return isset($_POST[$var]) ? $_POST[$var] : (isset($_GET[$var]) ? $_GET[$var] : $d);
}

function xt_eget($var, $d = '')
{
    return !empty($_POST[$var]) ? $_POST[$var] : (!empty($_GET[$var]) ? $_GET[$var] : $d);
}


function xt_pget($var, $d = '')
{
    return !empty($_POST[$var]) ? $_POST[$var] : $d;
}

function xt_post($var, $d = '')
{
    return isset($_POST[$var]) ? $_POST[$var] : $d;
}

function xt_gget($var, $d = '')
{
    return !empty($_GET[$var]) ? $_GET[$var] : $d;
}

function xt_sget($var, $d = '')
{
    return isset($_GET[$var]) ? $_GET[$var] : $d;
}

function xt_set($key, $val = NULL)
{
    global $_XH_D;
    if (is_array($key)) {
        if ($val) $_XH_D[$val] = isset($_XH_D[$val]) ? array_merge($_XH_D[$val], $key) : $key; else $_XH_D = array_merge($_XH_D, $key);
    } else {
        $_XH_D[$key] = $val;
    }
}

function xt_tname($table, $type = '', $join = '', $m = '')
{
    global $_XC;
    $type = empty($type) ? '' : '_' . $type;

    if ($join) {
        $p = xt_hashmap($_XC['db'], 'tablepre' . $type);
        return "$p$table t1 $m join $p$join t2 on ";
    }
    return xt_hashmap($_XC['db'], 'tablepre' . $type) . $table;
}

function xt_config($name)
{
    global $_XC;
    return xt_hashmap($_XC, $name);
}

function xt_xconfig()
{
    include MS_XTIGER . './config/Configuration.php';
    $configuration = ConfigurationManager::getConfiguration()->getRuntimeConfiguration();
    return xt_writefile(MS_CACHEPATH . './RuntimeXConfiguration.cache.php', $configuration, 'php', 'w', 0);
}

function xt_startWiths($str, $p)
{
    return substr($str, 0, strlen($p)) == $p;
}

function xt_endWiths($str, $p)
{
    return substr($str, -strlen($p)) == $p;
}

function xt_gmdate($timestamp, $dateformat = '')
{

    if (empty($dateformat)) {
        $dateformat = 'Y-m-d H:i:s';
    }
    if (!empty($timestamp)) {
        return gmdate($dateformat, $timestamp + xt_config('timeoffset') * 3600);
    } else {
        return '';
    }
}

function xt_seccodeconvert($seccode)
{
    $s = sprintf('%04s', base_convert($seccode, 10, 20));
    $seccodeunits = 'CEFHKLMNOPQRSTUVWXYZ';
    $seccode = '';
    for ($i = 0; $i < 4; $i++) {
        $unit = ord($s{$i});
        $seccode .= ($unit >= 0x30 && $unit <= 0x39) ? $seccodeunits[$unit - 0x30] : $seccodeunits[$unit - 0x57];
    }
    return $seccode;
}

function xt_writefile($filename, $writetext, $filemod = 'text', $openmod = 'w', $eixt = 1)
{
    xt_mkdirs(dirname($filename));
    if (!$fp = @fopen($filename, $openmod)) {
        if ($eixt) {
            exit('File :Have no access to write!');
        } else {
            return false;
        }
    } else {
        $text = '';
        if ($filemod == 'php') {
            $text = "<?php if(!defined('MS_XTIGER')) exit('Access Denied');\r\n\r\n";
        }
        $text .= $writetext;
        if ($filemod == 'php') {
            $text .= "\r\n\r\n?>";
        }
        flock($fp, 2);
        fwrite($fp, $text);
        fclose($fp);
        /*if($filemod == 'php') {
            @chmod($filename, 0777);
        }*/
        return true;
    }
}

function xt_readfile($filename, $filemod = 'text')
{
    if (!$fp = @fopen($filename, 'r')) {
        return '';
    }
    if (filesize($filename) == 0) return '';
    $context = @fread($fp, filesize($filename));
    fclose($fp);
    $context = str_replace('<?php exit?>', '', $context);
    if ($filemod == 'xml') {
        if (!preg_match('/<\?xml.*?\?>/', $context)) {
            $context = '<?xml version="1.0" encoding="UTF-8"?> ' . $context;
        }
    }
    return $context;
}

function xt_writecache($filename, $data, $path = '', $have_zlib = 0, $openmod = 'w')
{
    if ($have_zlib && function_exists("gzdeflate")) $data = gzdeflate(serialize($data));
    $data = serialize($data);
    $data = '<?php exit?>' . $data;
    $path && xt_mkdirs($path);
    $fp = @fopen($path . $filename, $openmod);
    if ($fp) {
        flock($fp, 2);
        fwrite($fp, $data);
        fclose($fp);
        //@chmod($path.$filename, 0777);
        return TRUE;
    }
    return FALSE;
}

function xt_mkdirs($path)
{
    if (!is_dir($path)) {
        xt_mkdirs(dirname($path));
        mkdir($path, 0777);
    }
}

function xt_readcache($filename, $path = '', $have_zlib = 0)
{
    $have_zlib && $have_zlib = function_exists("gzinflate");
    $data = file_get_contents($path . $filename);
    if ($data && strlen($data) > 12) {
        $data = substr($data, 12);
        if ($have_zlib) return unserialize(gzinflate($data));
        return unserialize($data);
    }
    return NULL;
}

function xt_stripsearchkey($string)
{
    if ($string == '') return $string;
    if (is_array($string)) {
        foreach ($string as $key => $val) {
            $string[$key] = xt_stripsearchkey($val);
        }
    } else {
        $string = trim($string);
        $string = str_replace('*', '%', addcslashes($string, '%_\''));
        $string = str_replace('_', '\_', $string);
    }
    return $string;
}

function xt_stripslashes($string)
{
    if (!is_array($string)) return stripslashes($string);
    foreach ($string as $key => $val) $string[$key] = xt_stripslashes($val);
    return $string;
}

function xt_addslashes($string)
{
    if (!is_array($string)) return addslashes($string);
    foreach ($string as $key => $val) $string[$key] = xt_addslashes($val);
    return $string;
}

function xt_replace_once($needle, $replace, $haystack)
{
    $pos = strpos($haystack, $needle);
    if ($pos === false) {
        return $haystack;
    }
    return substr_replace($haystack, $replace, $pos, strlen($needle));
}

function xt_strtotime($timestamp, $check = 0)
{
    $timestamp = trim($timestamp);
    if (empty($timestamp)) return $check ? XT_TIME : 0;
    $hour = $minute = $second = $month = $day = $year = 0;
    $exparr = $timearr = array();
    if (strpos($timestamp, ' ') !== false && strpos($timestamp, '-') !== false) {
        $timearr = explode(' ', $timestamp);
        $exparr = explode('-', $timearr[0]);
        $year = empty($exparr[0]) ? 0 : intval($exparr[0]);
        $month = empty($exparr[1]) ? 0 : intval($exparr[1]);
        $day = empty($exparr[2]) ? 0 : intval($exparr[2]);
        $exparr = explode(':', $timearr[1]);
        $hour = empty($exparr[0]) ? 0 : intval($exparr[0]);
        $minute = empty($exparr[1]) ? 0 : intval($exparr[1]);
        $second = empty($exparr[2]) ? 0 : intval($exparr[2]);
    } elseif (strpos($timestamp, '-') !== false && strpos($timestamp, ' ') === false) {
        $exparr = explode('-', $timestamp);
        $year = empty($exparr[0]) ? 0 : intval($exparr[0]);
        $month = empty($exparr[1]) ? 0 : intval($exparr[1]);
        $day = empty($exparr[2]) ? 0 : intval($exparr[2]);
    } elseif (!strpos($timestamp, '-') === false && strpos($timestamp, ' ') !== false) {
        $exparr = explode(':', $timestamp);
        $hour = empty($exparr[0]) ? 0 : intval($exparr[0]);
        $minute = empty($exparr[1]) ? 0 : intval($exparr[1]);
        $second = empty($exparr[2]) ? 0 : intval($exparr[2]);
    } else {
        return $check ? XT_TIME : 0;
    }
    $r = gmmktime($hour, $minute, $second, $month, $day, $year) - 8 * 3600;
    if ($check && $r > XT_TIME) return XT_TIME;
    return $r;
}

function xt_send_error()
{

}

function xt_getip()
{
    static $ip = '';
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
        $ip = $_SERVER['HTTP_CDN_SRC_IP'];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
        foreach ($matches[0] AS $xip) {
            if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                $ip = $xip;
                break;
            }
        }
    }
    return $ip;
}

define('CLIENT_IP', xt_getip());
define('TIMESTAMP', time());


function xt_obclean()
{
    global $_XC;
    ob_end_clean();
    if ($_XC['gzipcompress'] && function_exists('ob_gzhandler')) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
}

/*function xt_auth($path) {
    global $_XC,$_UD;
    if(!function_exists(xt_hashmap($_XC,'__xt__auth'))){
        return TRUE;
    }
    return $_XC['__xt__auth']($path,xt_hashmap($_UD,'role'));
}*/
function xt_acl($path)
{
    global $_XC, $_UD;
    if (!function_exists(xt_hashmap($_XC, '__xt__auth'))) {
        return TRUE;
    }
    return $_XC['__xt__auth']($path, xt_hashmap($_UD, 'role'));
}

function xt_load()
{
    static $loader;
    if (empty($loader)) {
        $loader = new CXTLoader();
    }
    return $loader;
    /*
    global $_XH_L, $_XH_L_DB, $_XC;
    switch ($type) {
        case 'model':
            $k = $type . '_' . $file;
            if (!isset($_XH_L[$k])) {
                $file = str_replace('.', '/', $file);
                if (file_exists(MS_APPPATH . './models/' . $file . '.php')) {
                    include_once(MS_APPPATH . './models/' . $file . '.php');
                    $_XH_L[$k] = true;
                }
            }
            return $_XH_L[$k];
        case 'db':
            $driver = !empty($paras) && is_string($paras) ? $paras : 'mysql';
            $s = empty($file) ? '' : '_' . $file;
            $k = $driver . '_' . $s;
            if (!isset($_XH_L_DB[$k])) {
                if (file_exists(MS_XTIGER . "./database/drivers/$driver/{$driver}_driver.php")) {
                    include_once(MS_XTIGER . "./database/drivers/$driver/{$driver}_driver.php");
                    $c = 'CXT_' . $driver . '_driver';
                    $db = new $c();
                    $db->connect($_XC['dbhost' . $s], $_XC['dbuser' . $s], $_XC['dbpwd' . $s],
                        $_XC['dbname' . $s], $_XC['pconnect' . $s], $_XC['dbcharset' . $s]);
                    $_XH_L_DB[$k] = $db;
                }
            }
            return $_XH_L_DB[$k];
        case 'dbset':
            $s = empty($file) ? '' : '_' . $file;
            $d = explode(':', $_XC['dbhost' . $s]);
            $dbport = count($d) == 2 ? $d[1] : '3306';
            return array(
                'dbhost' => $_XC['dbhost' . $s],
                'dbport' => $dbport,
                'dbuser' => $_XC['dbuser' . $s],
                'dbpwd' => $_XC['dbpwd' . $s],
                'dbname' => $_XC['dbname' . $s],
                'dbcharset' => $_XC['dbcharset' . $s]);
        case 'library':
            $k = $type . '_' . $file;
            if (!isset($_XH_L[$k])) {
                $p = './libraries/' . str_replace('.', '/', $file) . '.lib.php';
                if (($b = file_exists(MS_APPPATH . $p)) || file_exists(MS_XTIGER . $p)) {
                    include_once($b ? MS_APPPATH . $p : MS_XTIGER . $p);
                    $c = 'CXT' . ucfirst(str_replace('.', '_', $file));
                    $_XH_L[$k] = $paras === TRUE ? new $c : $c;
                } else {
                    return NULL;
                }
            }
            return $_XH_L[$k];
        case 'cache':
            $k = $type . '_' . $file;
            if (!isset($_XH_L[$k]) || $paras === TRUE) {
                if ($paras === 1) {
                    include_once(MS_CACHEPATH . $file . '.cache.php');
                    $_XH_L[$k] = TRUE;
                    return;
                }
                if (file_exists(MS_CACHEPATH . './Runtime' . ucfirst($file) . '.cache.php')) {
                    $_XH_L[$k] = xt_readcache(MS_CACHEPATH . './Runtime' . ucfirst($file) . '.cache.php');
                    return empty($_XH_L[$k]) ? array() : $_XH_L[$k];

                }
                return $_XH_L[$k] = array();

            }
            return $_XH_L[$k];
        case 'class':
            $k = $type . '_' . $file;
            if (!isset($_XH_L[$k])) {
                $p = '/classes/' . str_replace('.', '/', $file) . '.php';
                if (($b = file_exists(MS_APPPATH . $p)) || file_exists(MS_XTIGER . $p)) {
                    include_once($b ? MS_APPPATH . $p : MS_XTIGER . $p);

                }
                $_XH_L[$k] = TRUE;
            }
    }*/
}

class CXTLoader
{

    private $cache = array();

    function func($name)
    {
        if (isset($this->cache['function'][$name])) {
            return true;
        }
        $file = str_replace('.', '/', $name);
        if (file_exists(MS_XTIGER . './libraries/' . $file . '.func.php')) {
            include_once(MS_XTIGER . './libraries/' . $file . '.func.php');
            $this->cache['function'][$name] = true;
        } else {
            trigger_error('Invalid Model /framework/function/' . $file . '.func.php', E_USER_ERROR);
        }
        return false;
    }

    function model($name)
    {
        if (isset($this->cache['model'][$name])) {
            return true;
        }
        $file = str_replace('.', '/', $name);
        if (file_exists(MS_APPPATH . './models/' . $file . '.model.php')) {
            include_once(MS_APPPATH . './models/' . $file . '.model.php');
            $this->cache['model'][$name] = true;
        } else {
            trigger_error('Invalid Model /framework/model/' . $file . '.model.php', E_USER_ERROR);
        }
        return false;
    }

    function library($name)
    {
        if (isset($this->cache['library'][$name])) {
            return true;
        }
        $file = str_replace('.', '/', $name);
        if (file_exists(MS_XTIGER . './libraries/' . $file . '.lib.php')) {
            include_once(MS_XTIGER . './libraries/' . $file . '.lib.php');
            $this->cache['library'][$name] = true;
        } else {
            trigger_error('Invalid Model /framework/library/' . $file . '.php', E_USER_ERROR);
        }
        return false;

    }

}

function xt_save($type, $file, $paras)
{
    switch ($type) {
        case 'cache':
            $cachefile = MS_CACHEPATH . './Runtime' . ucfirst($file) . '.cache.php';
            /*$cachetext = 'function xt_'.$file.'_cache() {return '.xt_implode($paras).';}';
            xt_writefile($cachefile, $cachetext, 'php');*/
            xt_writecache($cachefile, $paras);
            break;
    }
}

function xt_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4;

    $key = md5($key ? $key : XC_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {

        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }

}

function xt_checkinput()
{
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

function xt_ncheckform()
{
    return xt_get('xt_formhash') == XT_FORMHASH ? FALSE : TRUE;
}

function xt_issubmit()
{
    return xt_pget('xt_formhash');
}

function xt_formhash()
{
    if (!defined('XT_KEY')) {
        define('XT_KEY', '63b0ba6m1b3Z9g5Uca3J5q5j6HaW2u3Cfd2f1vf8fleufNfE6a5D3EcoaH0t438G');
    }
    if (!defined('XT_FORMHASH')) {
        define('XT_FORMHASH', substr(md5(substr(XT_TIME, 0, -4) . XT_KEY), 16));
    }
}


function xt_writelog($action, $extra = '')
{
    global $_UD;
    $log = htmlspecialchars($_UD['userid'] . "\t" . xt_getip() . "\t" . time() . "\t$action\t$extra");
    $logfile = XT_APPPATH . './data/logs/' . gmdate('Ym', time()) . '.php';
    if (@filesize($logfile) > 2048000) {
        PHP_VERSION < '4.2.0' && mt_srand((double)microtime() * 1000000);
        $hash = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < 4; $i++) {
            $hash .= $chars[mt_rand(0, 61)];
        }
        @rename($logfile, XT_APPPATH . './data/logs/' . gmdate('Ym', $this->time) . '_' . $hash . '.php');
    }
    if ($fp = @fopen($logfile, 'a')) {
        @flock($fp, 2);
        @fwrite($fp, "<?PHP exit;?>\t" . str_replace(array('<?', '?>'), '', $log) . "\n");
        @fclose($fp);
    }
}

/*function xt_sql($sql, $start = 0, $rows = 1, $page = -1, $pair = '', $dbsource = '', $distinctfield = '')
{
    if (!$sql) return NULL;
    $db = xt_load('db', $dbsource);
    if ($page == -1) {
        if ($rows == 1) return $db->fetch_first($sql);
        $result = array();
        if ($rows == -1) $query = $db->query($sql);
        else $query = $db->query($sql . " LIMIT $start,$rows");
        if ($pair) {
            $pair = explode(',', $pair);
            if (isset($pair[1])) {
                while ($row = $db->fetch_array($query)) {
                    $result[$row[$pair[0]]] = $row[$pair[1]];
                }
                return $result;
            }
            while ($row = $db->fetch_array($query)) {
                $result[$row[$pair[0]]] = $row;
            }
            return $result;
        }
        while ($row = $db->fetch_array($query)) {
            $result[] = $row;
        }
        return $result;
    }
    return xt_multipage($sql, '', $page, $rows, $dbsource);
}

function xt_multipage($sqlstr, $ordersql = '', $page, $pageline = 20, $datasource = '', $numcount = 5)
{
    $db = xt_load('db', $datasource);

    $count = $db->result($db->query('SELECT COUNT(*) ' . stristr($sqlstr, ' FROM ')));
    if (!$count) {
        return NULL;
    }

    $pageline = $pageline < 1 ? 1 : $pageline;
    $realpages = @ceil($count / $pageline);
    $page = $page < 1 ? $page = 1 : $page > $realpages ? $realpages : $page;
    $start = ($page - 1) * $pageline;
    $query = $db->query("$sqlstr $ordersql  LIMIT $start,$pageline");
    $result = array();
    while ($row = $db->fetch_array($query)) {
        $result[] = $row;
    }
    if ($count <= $pageline) {
        return array('pagectrl' => array($page, $count, $pageline, $realpages, 0, 0, $numcount), 'data' => $result);
    }
    $from = $page > $numcount ? $page - $numcount : 1;
    $to = $page < $realpages - $numcount ? $page + $numcount : $realpages;
    return array('pagectrl' => array($page, $count, $pageline, $realpages, $from, $to, $numcount), 'data' => $result);
}*/

function xt_getcookie($var, $d = '')
{
    global $_XC;
    return isset($_COOKIE[$_XC['cookiepre'] . $var]) ? $_COOKIE[$_XC['cookiepre'] . $var] : $d;
}

function xt_setcookie($var, $value, $life = 0)
{
    global $_XC;
    setcookie($_XC['cookiepre'] . $var, $value, $life ? (XT_TIME + $life) : 0, $_XC['cookiepath'], $_XC['cookiedomain'], $_SERVER['SERVER_PORT'] == 443 ? 1 : 0);
}

function __xt__init()
{
    global $_XC;
    $mtime = explode(' ', microtime());
    $_XC['xt_starttime'] = $mtime[1] + $mtime[0];
    if (function_exists('xt_init')) {
        xt_init();
    }
}

function xt_substr($string, $length, $suffix = '', $trimhtml = 0)
{
    if (strlen($string) <= $length) {
        return $string;
    }
    if ($trimhtml) {
        $string = preg_replace("/<img[^>]*?>|[\r\n]/i", "", $string);
        $string = strip_tags(trim($string));
    }
    $wordscut = '';
    if (defined('CHARSET') && strtolower(CHARSET) == 'utf-8') {
        $n = 0;
        $tn = 0;
        $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n++;
                $noc++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t < 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n++;
            }
            if ($noc >= $length) {
                break;
            }
        }
        if ($noc > $length) {
            $n -= $tn;
        }
        $wordscut = substr($string, 0, $n);
    } else {
        for ($i = 0; $i < $length - 3; $i++) {
            if (ord($string[$i]) > 127) {
                $wordscut .= $string[$i] . $string[$i + 1];
                $i++;
            } else {
                $wordscut .= $string[$i];
            }
        }
    }
    if ($trimhtml == 2) {
        $wordscut = addslashes($wordscut);
    }
    if ($suffix === 1) {
        return $wordscut . '...';
    } else {
        return $wordscut . $suffix;
    }
}

function __xt__exit()
{
    global $_XH_L_DB, $_XH_E, $_XC;
    $mtime = explode(' ', microtime());
    $_XC['xt_endtime'] = $mtime[1] + $mtime[0];

    /*foreach ($_XH_L_DB as $v) {
        $v->close();
    }*/
    //xt_log();
    if (function_exists('xt_exit')) {
        xt_exit();
    }
}
