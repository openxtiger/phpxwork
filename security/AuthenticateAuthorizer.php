<?php
/**
 * Authentication and Authorization
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-6-19
 * Time: 10:52:57
 */
define('ACCESS_GRANTED',1);
define('ACCESS_ABSTAIN',0);
define('ACCESS_DENIED',-1); 
function __xta__auth($path, $auth) {
    $configAttributes = xt_load('cache','securitySource');
    $allow = 1;
    foreach($configAttributes as $key=>$v) {
        if($key=='allowIfAbstainDecision') {
            $allow = $v;
            continue;
        }
        $m = '__xta__'.$key.'_decide';
        if(isset($v[$path])) {
            return function_exists($m)?$m(intval($auth), intval($v[$path])):FALSE;
        }
        foreach($v as $k1=>$v1) {
            if(preg_match("|$k1|i",$path)){
                return function_exists($m)?$m(intval($auth), intval($v1)):FALSE;   
            }
        }
    }
    return $allow==1;
}

/**
 * grants access if any voter returns an affirmative response.
*/
function __xta__affirmative_decide($auth,$attribute) {
    return  ($auth & $attribute) > 0;
}
/**
*  requires all voters to abstain or grant access.
*/
function __xta__unanimous_decide($auth,$attribute) {
    return ($auth & $attribute) == $attribute;
}

/**
* "Consensus" here means majority-rule (ignoring abstains) rather than unanimous agreement (ignoring abstains)
*/
function __xta__consensus_decide($auth,$attribute) {
    /*if($attribute==ACCESS_ABSTAIN) return $allow;
    $granted = $auth & $attribute;
    $denied = $attribute ^ $granted;
    
    return __xta__vote($auth,$attribute) > 0;*/
}

//function __xta_cache
?>