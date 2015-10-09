<?php if(!defined('MS_XTIGER')) exit('Access Denied');
/**
 * Created by openXtiger.org.
 * User: Administrator
 * Date: 2009-5-12
 * Time: 13:16:01
 */

function xtiger_debugging_intercept($invocationInvoke) {
    $r =  $invocationInvoke();
    xt_log();
    return $r;
}

/*function xtiger_logger_intercept($invocationInvoke) {
    echo "<br/>logger_intercept<br/>";
    return $invocationInvoke();
}

function xtiger_authenticate_intercept($invocationInvoke) {
    global $_XC,$_XM,$_UD;
    include_once(MS_XTIGER.'./security/AuthenticateAuthorizer.php');
    if(!__xta__auth($_XM['uri'],xt_hashmap($_UD,'role'))) {
        return xtr_message('access_is_denied');
    }
    $_XC['__xt__auth'] = '__xta__auth';
    return $invocationInvoke();
}
function xtiger_language_intercept($invocationInvoke) {
    echo "<br/>logger_intercept<br/>";
    return $invocationInvoke();
}*/
