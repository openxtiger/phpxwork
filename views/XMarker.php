<?php if (!defined('MS_XTIGER')) exit('Access Denied');
/**
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-5-16
 * Time: 9:59:30
 */

function xtf_template($location)
{
    global $_XC, $_XM;
    $template = $_XC['template'];
    $language = $_XC['language'];//empty($_XH_D['language']) ? $_XC['language'] : $_XH_D['language'];
    $ext = empty($_XC['view_ext']) ? '.html.php' : $_XC['view_ext'];
    $filename = $location . $ext;
    $viewdir = MS_APPPATH . './views/' . $template . '/';
    if (!file_exists($viewdir . $filename)) {
        if (!file_exists(($viewdir = MS_APPPATH . './views/' . $_XC['default_template'] . '/') . $filename)) {
            return;
        }
    };

    $objfile = MS_CACHEPATH . "./tpl/$language/$template/$location.php";
    $tplrefresh = 1;

    if (($code = xt_get('xtiger_code'))) {
        $p = array();
        parse_str(rawurldecode(xt_authcode($code, 'DECODE', $_XC['xtiger_key'] . $_XC['xtiger_ip'])), $p);
        if (array_key_exists('xtiger_url', $p)) {
            $objfile = (empty($_XC['xtiger_path']) ? MS_CACHEPATH : $_XC['xtiger_path']) . "./xtpl/$language/$template/$location.php";
            define('IN_XTIGER', 'TRUE');
            define('XTIGER_URL', $p['xtiger_url']);
        }
    } else if (file_exists($objfile)) {
        /*if(empty($_XC['tplrefresh'])) {
            $tplrefresh = 0;
        } else {
            if(@filemtime($viewdir.$filename) <= @filemtime($objfile)) {
                $tplrefresh = 0;
            }
        }*/
        $tplrefresh = 1;
    }

    if ($tplrefresh) {
        /*if(!is_dir(MS_CACHEPATH.'./tpl/')){
            @mkdir(MS_CACHEPATH.'./tpl/',0777);
        }*/
        include_once(MS_XTIGER . './views/XMarkerParser.php');
        _xtf_parse_template($filename, $objfile, "language/$language/", $viewdir);
    }
    return $objfile;
}

function xtiger_xmarker_result($r)
{
    global $_XH_D, $_XM, $_UD, $_XC, $_XH_LANG;
    $objfile = xtf_template($r['location']);
    if (!$objfile) return;
    extract($_XH_D);
    if (is_array($_UD)) {
        extract($_UD);
    }
    include $objfile;
}