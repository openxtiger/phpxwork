<?php if (!defined('MS_XTIGER')) exit('Access Denied');
/**
 * Created by openXtiger.org.
 * User: Administrator
 * Date: 2009-5-11
 * Time: 11:19:43
 */
define('SUCCESS', 'success');
define('ERROR', 'error');
define('NONE', 'none');
require(MS_XTIGER . './Common.php');
if (!@include(MS_CACHEPATH . './RuntimeSConfiguration.cache.php')) {
    __xt__getMapping();
    return NONE;//xtr_redirect('', '/install/index.php');
}

@include(MS_CACHEPATH . './RuntimeXConfiguration.cache.php');
function xt_absurl($url)
{
    $url = preg_replace("/\/\.(?=\/)/", '', preg_replace("/(?<!:)\/{2,}/", "/", $url));
    if (preg_match("/\/\.{2,}/", $url)) {
        $url = preg_replace("/\/\.{3,}/", "/..", $url);
        $pa = explode('/', $url);
        $r = array();
        $idx = 0;
        for ($i = count($pa) - 1; $i >= 0; $i--) {
            if ($pa[$i] == '..') {
                $idx++;
                continue;
            }
            $i -= $idx;
            $idx = 0;
            if ($i < 0) break;
            array_unshift($r, $pa[$i]);
        }
        return implode($r, '/');
    }
    return $url;
}

function xt_static_uri($uri, $namespace = '')
{
    global $_XC, $_XM;
    if (preg_match("/:\/\//", $uri)) {
        return $uri;
    }
    $np = rtrim(empty($_XC['static_url']) ? $_XC['namespace_prefix'] : $_XC['static_url'], '/');
    if (substr($namespace, -1) != '/' && !empty($namespace)) $namespace .= '/';
    $matchs = array();
    if (preg_match("/\/\.\/(.*?)\/\.\//", $uri, $matchs)) {
        if (!preg_match("/:\/\//", $np)) {
            $d = str_replace($matchs[0], "/$_XC[template]/", $uri);
            $npc = preg_replace("/\/\.\/.*?\/\./", "/$_XC[template]", $np);
            $p = $_XC['root_path'] . ltrim($npc, $_XC['namespace_prefix']);
            if (substr($uri, 0, 1) == '/') {
                if (file_exists($p . $d)) {
                    return xt_absurl($npc . $d);
                }
            } else {
                if (file_exists($p . '/' . $namespace . $d)) {
                    return $npc . '/' . $namespace . $d;
                }
            }
        }
        $uri = str_replace("$matchs[0]", "/$matchs[1]/", $uri);
    }
    if (substr($uri, 0, 1) == '/') {
        return xt_absurl($np . $uri);
    }
    return xt_absurl($np . '/' . $namespace . $uri);
}

function xt_uri($uri = '', $namespace = '')
{
    global $_XC, $_XM;

    $uri = trim($uri);
    $namespace = trim($namespace);

    $np = $_XC['namespace_prefix'];
    $ns = $_XM['namespace'];
    if ($uri == '' || $uri == '/') {
        if (!preg_match("/\.php$|\.php[^\w]/", $namespace)) {
            $namespace .= $_XM['scriptname'];
        }
        return xt_absurl(xt_startWiths($namespace, '/') ? $np . $namespace : "$np$ns/$namespace");
    }
    //echo 'np='.$np.',,uri='.$uri."-->namespace=".$namespace.'<br/>';
    if (xt_endWiths($ns, '/')) $ns = substr($ns, 0, strlen($ns) - 1);
    if (($idx = strpos($uri, '?')) !== FALSE) {
        $uri = __xt__addURISuffix(substr($uri, 0, $idx)) . substr($uri, $idx);
    } else {
        $uri = __xt__addURISuffix($uri);
    }

    if (!xt_startWiths($uri, '/')) $uri = '/' . $uri;
    if (empty($namespace)) {
        return $np . $ns . $_XM['scriptname'] . $uri;
    }
    if (!preg_match("/\.php$|\.php[^\w]/", $namespace)) {
        $namespace .= $_XM['scriptname'];
    }
    return xt_absurl(xt_startWiths($namespace, '/') || empty($namespace) ? $np . $namespace . $uri : "$np$ns/$namespace$uri");

}

/*    result types   */

function xtr_builder($location)
{
    global $_XH_D, $_XM, $_XC;
    $template = empty($_XH_D['template']) ? $_XC['template'] : $_XH_D['template'];
    $ext = empty($_XC['view_ext']) ? '.html.php' : $_XC['view_ext'];
    $filename = MS_APPPATH . './views/' . $template . '/' . $location . $ext;
    if (!file_exists($filename)) return;
    extract($_XH_D);
    include $filename;
    return NONE;
}

function xtr_marker($location)
{
    $file = MS_XTIGER . './views/XMarker.php';
    include_once($file);
    $result = array('location' => $location);
    call_user_func('xtiger_xmarker_result', $result);
    return NONE;
}

function xtr_redirect($uri = '', $namespace = '')
{
    //xt_obclean();
    header("HTTP/1.1 301 Moved Permanently");
    //echo xt_uri($uri, $namespace);
    header('Location:' . xt_uri($uri, $namespace));
    return NONE;
}

function xtr_chain($action, $namespace = '', $method = '', $scriptname = '', $request = array())
{
    global $_XC, $_XM;
    if (empty($namespace)) $namespace = '/';
    if (empty($method)) $method = $_XC['default_method'];
    if (empty($scriptname)) $method = 'xclient.php';
    $uri = ($namespace == '/' ? '' : $namespace) . "/$action/$method/" . implode('/', $request);
    $_XM = array('action' => $action, 'namespace' => $namespace, 'scriptname' => $scriptname, 'method' => $method, 'request' => $request);
    __xt__dispatcher();
    return NONE;
}

function xtr_message($message, $url_forward = '', $namespace = '', $second = 2, $langfile = 'message')
{
    global $_XH_D, $_XC;
    //xt_obclean();
    $auto_forward = '';
    if (!empty($url_forward) || !empty($namespace)) {
        $second = $second * 1000;
        $url_forward = xt_uri($url_forward, $namespace);
        if ($second > 0) $auto_forward = "<script>setTimeout(\"window.location.href ='$url_forward';\", $second);</script>";
    }
    $_XH_D['message'] = $message;
    $_XH_D['auto_forward'] = $auto_forward;
    $_XH_D['url_forward'] = $url_forward;
    return 'message';
}

function xtr_json($r)
{
    echo json_encode($r);
    return NONE;
}

function xtr_echo($r)
{
    echo $r;
    return NONE;
}

function xtr_message_refresh($message, $url_forward = '', $namespace = '', $second = 2, $langfile = 'message')
{
    global $_XH_D, $_XC;
    //xt_obclean();
    $auto_forward = '';
    if (!empty($url_forward) || !empty($namespace)) {
        $second = $second * 1000;
        $url_forward = xt_uri($url_forward, $namespace);
        if ($second > 0) $auto_forward = "<script>setTimeout(\"window.close();\", $second);</script>";
    }
    $_XH_D['message'] = $message;
    $_XH_D['auto_forward'] = $auto_forward;
    $_XH_D['url_forward'] = $url_forward;
    return 'message_refresh';
}

/*    result types   */

/**              **/
function __xt__fetchNsSn($uri)
{
    global $_XC;
    $path = $_SERVER['SCRIPT_NAME'];
    $parsed_uri = preg_replace("|/{2,}|", "/", $path);
    if (isset($uri) AND !empty($uri)) {
        $parsed_uri = preg_replace("|" . $uri . "$|", "", $parsed_uri);
    }
    /*$ns = $_XC['namespace_prefix'];
    if(isset($ns) AND !empty($ns) ) {
        $parsed_uri = preg_replace("|^".$ns."|", "", $path);
    }*/
    $matches = array();
    if (preg_match("|(.*)/" . MS_FILE . "$|", $parsed_uri, $matches)) {
        return array(empty($matches[1]) ? '/' : $matches[1], $_XC['enable_rewrite_url'] ? '' : '/' . MS_FILE);
    }

    $i = strrpos($parsed_uri, '/');
    if ($i !== FALSE) {
        return array($i === 0 ? '/' : substr($parsed_uri, 0, $i), $_XC['enable_rewrite_url'] ? '' : '/' . substr($parsed_uri, $i + 1));
    }
    return array($parsed_uri, $_XC['enable_rewrite_url'] ? '' : '/index.php');
}

function __xt__fetchURI()
{
    global $_XC;
    if (strtoupper($_XC['uri_protocol']) == 'AUTO') {
        // Is there a PATH_INFO variable?  Note: some servers seem to have trouble with getenv() so we'll test it two ways
        $path = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
        if (trim($path, '/') != '' && $path != "/" . MS_FILE) {
            return $path;
        }  //  
        // No PATH_INFO?... Maybe the ORIG_PATH_INFO variable exists?
        $path = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
        if (trim($path, '/') != '' && $path != "/" . MS_FILE) {
            // remove path and script information so we have good URI data
            return str_replace($_SERVER['SCRIPT_NAME'], '', $path);
        }

        // We've exhausted all our options...
        return '';
    } else {
        $uri = strtoupper($_XC['uri_protocol']);

        if ($uri == 'REQUEST_URI') {
            return __xt__parseRequestURI();
        }
        return (isset($_SERVER[$uri])) ? $_SERVER[$uri] : @getenv($uri);
    }
}

function __xt__parseRequestURI()
{
    if (!isset($_SERVER['REQUEST_URI']) OR $_SERVER['REQUEST_URI'] == '') {
        return '';
    }

    $request_uri = preg_replace("|/(.*)|", "\\1", str_replace("\\", "/", $_SERVER['REQUEST_URI']));

    if ($request_uri == '' OR $request_uri == MS_FILE) {
        return '';
    }

    $fc_path = XTIGER_PATH;
    if (strpos($request_uri, '?') !== FALSE) {
        $fc_path .= '?';
    }

    $parsed_uri = explode("/", $request_uri);
    $i = 0;
    foreach (explode(DIRECTORY_SEPARATOR, $fc_path) as $segment) {
        if (isset($parsed_uri[$i]) && $segment == $parsed_uri[$i]) {
            $i++;
        }
    }

    $parsed_uri = implode("/", array_slice($parsed_uri, $i));

    if ($parsed_uri != '') {
        $parsed_uri = '/' . $parsed_uri;
    }
    return $parsed_uri;
}

function __xt__explodeSegments($str)
{
    $segments = array();
    foreach (explode("/", preg_replace("|/*(.+?)/*$|", "\\1", $str)) as $val) {
        // Filter segments for security
        $val = addslashes(trim($val));
        if ($val != '') {
            $segments[] = $val;
        }
    }
    return $segments;
}

function __xt__removeURISuffix($uri)
{
    global $_XC;
    if ($_XC['url_suffix'] != "") {
        return preg_replace("|" . preg_quote($_XC['url_suffix']) . "$|", "", $uri);
    }
    return $uri;
}

function __xt__addURISuffix($uri)
{
    global $_XC;
    if ($_XC['url_suffix'] != "" && !preg_match("/\/\w*\.\w*$/", $uri)) {
        return $uri . $_XC['url_suffix'];
    }
    return $uri;
}

function __xt__getMapping()
{
    global $_XC, $_XR;
    $uri = __xt__fetchURI();
    $ns = __xt__fetchNsSn($uri);
    $namespace = $ns[0];
    if ($namespace != '/') {
        //namespace and namespace_prefix;
        $nss = explode('/', $namespace);
        $c = count($nss);
        $ppath = '';
        $p = getcwd();
        for ($i = 1; $i < $c; $i++) {
            if (is_dir($p . $ppath . '/WEB-INF')) {
                $r = array_slice($nss, 0, $c - $i + 1);
                $_XC['namespace_prefix'] = count($r) == 0 ? '/' : implode('/', $r);
                $_XC['root_path'] = $p . $ppath . '/';
                $r = array_slice($nss, $c - $i + 1);
                $namespace = '/' . implode('/', $r);
                break;
            }
            $ppath .= '/..';
        }
    }
    //define('XT_CONTENT',$_XC['namespace_prefix'])
    $scriptname = $ns[1];

    if ($_XC['enable_query_strings'] === TRUE && isset($_GET[$_XC['action_trigger']])) {
        $method = empty($_GET[$_XC['method_trigger']]) ? $_XC['default_method'] : empty($_GET[$_XC['method_trigger']]);
        $action = $_GET[$_XC['action_trigger']];
        $uri = ($namespace == '/' ? '' : $namespace) . "/$action/$method";
        return array('action' => $action, 'namespace' => $namespace, 'scriptname' => $scriptname, 'method' => $method, 'request' => array());
    }
    $uri = __xt__removeURISuffix($uri);

    if ($uri == '') {
        $i = strrpos($scriptname, '.');
        $action = $_XC['default_any_action'];
        if ($i !== FALSE) {
            $action = substr($scriptname, 1, $i - 1);
        }
        //echo $namespace.','.$action.','.$scriptname.','.$_XC['default_method'].'<br/>';
        return array(
            'uri' => $namespace,
            'action' => $action,
            'scriptname' => $scriptname,
            'namespace' => $namespace,
            'method' => $_XC['default_method'],
            'request' => array()
        );
    }
    $segments = __xt__explodeSegments($uri);
    $action = $segments[0];
    if (isset($segments[1])) {
        if (is_numeric($segments[1])) {
            $method = $_XC['default_method'];
            $segments = array_slice($segments, 1);
        } else {
            $method = $segments[1];
            $segments = array_slice($segments, 2);
        }
        /*if (isset($segments[1]) && isset($_XR) && is_array($_XR) && !empty($_XR[$segments[1]])) {
            if (is_array($_XR[$segments[1]])) {
                $action = empty($_XR[$segments[1]][0]) ? $_XC['default_num_action'] : $_XR[$segments[1]][0];
                $method = empty($_XR[$segments[1]][1]) ? $_XC['default_method'] : $_XR[$segments[1]][1];
            } else {
                $action = $_XR[$segments[1]];
                $method = $_XC['default_method'];
            }
            $segments[1] = $segments[0];
            $segments = array_slice($segments, 1);
        } else {
            $segments[0] = $action;
            $action = $_XC['default_num_action'];
            $method = $_XC['default_method'];
        }*/
    } else {
        $method = $_XC['default_method'];
        $segments = array_slice($segments, 1);
    }
    $uri = ($namespace == '/' ? '' : $namespace) . "/$action/$method" . (count($segments) > 0 ? '/' . implode('/', $segments) : '');
    return array('uri' => $uri, 'action' => $action, 'namespace' => $namespace, 'scriptname' => $scriptname, 'method' => $method, 'request' => $segments);
}

function __xt__getActionConfig($namespace, $name)
{
    global $namespaceActionConfigs, $namespaceConfigs, $_XC;
    $actions = xt_hashmap($namespaceActionConfigs, $namespace);
    if (!empty($actions)) {
        $config = xt_hashmap($actions, $name);
    }

    $config = empty($config) ? array() : $config;
    if (empty($actions['package_name'])) {
        $config['package'] = $namespace == '/' ? $_XC['default_package_name'] : str_replace('/', '_', trim($namespace, '/'));
    } else {
        $config['package'] = $actions['package_name'];
    }
    $config['name'] = $name;
    if (empty($config['interceptors'])) {
        $config['interceptors'] = xt_hashmap($namespaceConfigs, $namespace);
    }
    return $config;
}

function __xt__invocation()
{
    global $_AC;
    $interceptors = $_AC['interceptors'];
    if (!empty($interceptors) && isset($interceptors['files'])) {
        $files = $interceptors['files'];
        if (!is_array($files)) {
            $files = array($files);
        }
        foreach ($files as $v) {
            $file = $v;
            if (file_exists($file)) {
                require_once($file);
            }
        }
        unset($_AC['interceptors']['files']);
    }
    $r = __xt__invocationInvoke();
    if (!empty($r)) {
        __xt__invocationResult($r);
    }

}

function __xt__invocationInvoke()
{
    global $__xt__ivokeindex, $_AC;
    $params = array();
    $interceptors = $_AC['interceptors'];
    if ($__xt__ivokeindex >= count($interceptors)) {
        $m = '__xt__invokeActionOnly';
    } else {
        $v = $interceptors[$__xt__ivokeindex];
        $__xt__ivokeindex++;
        //$method = $v['package'].'_'.$v['name'].'_'.(empty($v['method'])?'intercept':$v['method']);
        $params = empty($v['params']) ? array() : $v['params'];
        if (function_exists($v['method'])) {
            $m = $v['method'];
        } else {
            $m = '__xt__invocationInvoke';
        }
    }
    array_unshift($params, '__xt__invocationInvoke');
    return call_user_func_array($m, $params);
}

function __xt__invokeActionOnly()
{
    global $_AC, $_XM;
    $r = $_XM['request'];
    if (!is_array($r)) {
        $r = array($r);
    }
    $file = empty($_AC['file']) ? MS_APPPATH . 'actions' . $_XM['namespace'] . ($_XM['namespace'] == '/' ? '' : '/') . $_AC['name'] . '.ctrl.php' : $_AC['file'];
    unset($_AC['file']);

    if (file_exists($file)) {
        include_once($file);
        $actionExecute = $_AC['package'] . '_' . $_XM['action'] . '_' . $_XM['method'];
        if (function_exists($actionExecute)) {
            __xt__invocationResult(call_user_func_array($actionExecute, $r));
        }
        return;
    }
    @header('HTTP/1.1 500 Internal Server Error');
    return NULL;
}

function __xt__invocationResult($resultCode)
{
    global $_AC, $_XH_D, $_XC;
    if ($resultCode === NONE) return;
    $results = xt_hashmap($_AC, 'results');
    if (!empty($results)) {
        $result = xt_hashmap($results, $resultCode);
        if (!empty($result)) {
            $file = $result['file'];

            if (file_exists($file)) {
                include_once($file);
                $method = $result['package'] . '_' . $result['name'] . '_' . (empty($result['method']) ? 'result' : $result['method']);
                if (function_exists($method)) {
                    call_user_func($method, $result['params']);
                }
                return;
            }
        }
    }
    foreach (explode(',', $resultCode) as $v) {
        if ($_XC['default_xmarker_views'] == TRUE) {
            xtr_marker($v);
        } else {
            xtr_builder($v);
        }
    }
}

function __xt__dispatcher()
{
    global $_XM, $_AC, $__xt__ivokeindex;
    __xt__init();
    $_AC = __xt__getActionConfig($_XM['namespace'], $_XM['action']);
    $__xt__ivokeindex = 0;
    __xt__invocation();
    __xt__exit();
}


xt_formhash();
$_XM = __xt__getMapping();
$_AC = NULL;
$__xt__ivokeindex = 0;


__xt__dispatcher();