<?php if(!defined('MS_XTIGER')) exit('Access Denied');
/**
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-6-4
 * Time: 21:46:49
 */
function _xtc_constant($c) {
    if(defined($c)) {
       return constant($c);
    }
    return $c;
}
function _xtc_rconstant($m) {
    if(defined($m[0])) {
       return "$m[0].'";
    }
    return $m[0];
}
function _xtc_replace_constant($str) {
    return preg_replace("/^(MS_.*?)\//e","_xtc_constant('\\1')",$str);
}
function _xtc_check_constant($str) {
    return preg_replace_callback("'(MS_.*?)\//e","_xtc_rconstant",$str);
}
function _xtc_getParams($paramsElement) {
    $params = array();
    foreach ($paramsElement->childNodes as $child) {
        if($child->nodeType == XML_ELEMENT_NODE && 'param' == $child->nodeName) {
            $v =  $child->getAttribute('value');
            $params[] = !empty($v) ? trim($v) : trim($child->nodeValue);
        }
    }
    return $params;
}

function _xtc_constructInterceptors($context,$refName,$refParams) {
    $allInterceptorConfigs = $context->getAllInterceptorConfigs();
    $referencedConfig = xt_hashmap($allInterceptorConfigs,$refName);
    $result = array();

    if(empty($referencedConfig))
        return $result;

    if($referencedConfig instanceof InterceptorConfig) {
        $referencedConfig->mergeParams($refParams);
        $result[] = $referencedConfig;
    } else if ($referencedConfig instanceof InterceptorStackConfig) {
        if(count($refParams)>0){
            $referencedConfig->mergeParams($refParams);
        }
        $configs = $referencedConfig ->getInterceptorConfigs();
        if(is_array($configs) && count($configs)) {
            $result = array_merge($result,$configs);
        }
    }
    return $result;
}
?>