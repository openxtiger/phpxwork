<?php if (!defined('MS_XTIGER')) exit('Access Denied');
/**
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-5-16
 * Time: 11:57:17
 */
/**
 * parse template
 *
 * @param string $tplfile template file
 * @param string $objfile cache file
 * @return
 */
function _xtf_parse_template($tplfile, $objfile, $langdir, $viewdir)
{
    global $_XC;
    global $__xtf_langs;
    global $__xtf_js_langs;
    global $__xtf_xtigers;
    global $__xtf_viewdir;
    global $__xtf_langdir;

    $__xtf_viewdir = $viewdir;
    $__xtf_langdir = $langdir;

    $__xtf_xtigers = array();
    $xl = array();
    if (!$fp = @fopen($viewdir . $tplfile, 'r')) {
        //exit('Template file :<br>'.srealpath($tplfile).'<br>Not found or have no access!');
        // lunzi edit on 2009.9.28 changed 'srealpath($tplfile) to realpath($tplfile)'
        exit('Template file :<br>' . realpath($viewdir . $tplfile) . '<br>Not found or have no access!');
    }

    /**
     * lunzi add on 2009.9.28
     * to deal with error of 'Warning: fread()
     *   [function.fread]: Length parameter must be greater than 0 in '
     */
    $file_size = filesize($viewdir . $tplfile);
    //echo 'file_size='.$file_size;
    if ($file_size <= 0) {
        $template = "";
        // xtiger add for return pasre result
        if ($objfile) write_template_to_objfile($template, $objfile);
        else return $template;
        return '';
    }

    //$template = fread($fp, filesize($tplfile));
    $template = fread($fp, $file_size);
    fclose($fp);

    $template = str_replace('<?php exit?>', '', $template);
    //$template = preg_replace("/<\!\-\-\{&&(.+?)\}(.*?)\}\-\->/se","_xtf_read(\$viewdir,'\\1','\\2')", $template); //require views
    $template = preg_replace_callback("/<\!\-\-\{&&(.+?)\}(.*?)\}\-\->/s", '_xtf_read', $template); //require views
    $template = preg_replace_callback("/<\!\-\-\{&(.+?)\}\-\->/", '_xtf_read', $template); //require views
    // //parse
    if (defined('IN_XTIGER')) {
        $template = preg_replace("/<\!\-\-\/\*(?:block(\d*)(.*?)\-\->(.+?)<\!\-\-.*?|.*?)\*\/\-\->/es", "_xtf_xtiger(\$template,\$tplfile,\$viewdir,'\\1','\\2','\\3')", $template);
        $template .= '<script type="text/javascript" src="' . XTIGER_URL . '"></script>';
    } else {
        $template = preg_replace("/<\!\-\-\/\*.+?\*\/\-\->/s", "", $template); // <!-/*  */-->
    }
    unset($__xtf_xtigers);
    //<!--{!# }-->
    $matches = array();
    if (preg_match("/\{\!\#(.+?)\}/", $template, $matches)) {
        foreach (explode(',', $matches[1]) as $v) {
            if (file_exists(MS_APPPATH . $langdir . $v . '.lang.php')) {
                include_once(MS_APPPATH . $langdir . $v . '.lang.php');
                if (function_exists('xt_' . $v . '_lang')) {
                    $r = call_user_func('xt_' . $v . '_lang');
                    $xl = array_merge(is_array($r) ? $r : array($r), $xl);
                }
            }
        }
    }
    $__xtf_langs = $xl;

    $template = preg_replace("/<\!\-\-\{\!\#.+?\}\-\->/", "", $template); // <!--{!# }-->

    $template = preg_replace_callback("/<\!\-\-\{\!\&(.+?)\}\-\->/", 'xtf_includelang', $template); // <!--{!& }-->

    $template = preg_replace_callback("/<\!\-\-\{\{(.+?)\}\}\-\->/s", '_xtf_xpt', $template); // macro


    $var_regexp = "\\\$\{(.*?)(&)?\}"; //var,array
    $const_regexp = "([A-Z][A-Z0-9_]*)"; //const

    $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template); // remove /t
    $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template); //change <!--{}--> to {}
    $template = preg_replace("/\/\*\*\{(.+?)\}\*\*\//s", "{\\1}", $template); //change /**{}**/ to {} 

    $template = preg_replace_callback("/\{\!\@(.*?)(@)?\}/", 'xtf_language', $template);

    $template = str_replace("{LF}", "<?php echo \"\\n\"?>", $template);

    /*$template = preg_replace("/(\\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/s", "\\1['\\2']", $template);*/
    $template = preg_replace("/xtv=\"([^\"]*)\"/", "\\1", $template);
    $template = preg_replace_callback("/<(input|option)([^>]*?)xtc=\"([^\"]*)\"/", "_xtf_check", $template);

    $template = preg_replace_callback("/<(a|form|img|link|script|input)([^>]*?(?:src|action|href))\\s*?=([\"'])?\\s*?([^>]*?)\\3/i", "_xtf_uri", $template);
    $template = preg_replace_callback("/background:(.*?)url\((.*?)\)/i", "_xtf_uri1", $template);
    $template = preg_replace_callback("/(?<=url\()([^\)]*?)/", function ($m) {
        return xt_static_uri($m[1]);
    }, $template);
    $template = preg_replace_callback("/\#\{(.*?),?([^,]*?)\}\#/i", "_xtf_uri_auth", $template);
    $template = preg_replace_callback("/\#\((.*?)\)\#/i", "_xtf_suri_auth", $template);

    $template = preg_replace_callback("/(?<!\\\$)\{\!(.+?)(?:\?(.*?))?\}/", "_xtf_static_lang", $template);
    $template = preg_replace("/[\n\r\t]*\\$\{\=(.+?)\}[\n\r\t]*/is", "<?php echo \\1; ?>", $template);
    $template = preg_replace("/\{(break|continue)\s(.+?)\}/", "<?php if(\\2) \\1; ?>", $template);
    $template = preg_replace_callback("/$var_regexp/", "_xtf_var", $template);


    $template = ltrim($template);
    if ($objfile) $template = "<?php if(!defined('MS_XTIGER')) exit('Access Denied');" . (!defined('XT_NS_PREFIX') ? "if(!defined('XT_NS_PREFIX'))define('XT_NS_PREFIX','$_XC[namespace_prefix]')" : '') . "?>$template";
    $template = preg_replace("/[\n\r\t]*\{\#\s*(.+?)\}[\n\r\t]*/is", "\n<?php include xtf_template(\"\\1\"); ?>\n", $template);
    $template = preg_replace("/[\n\r\t]*\{elseif\s+(.+?)\}[\n\r\t]*/is", "\n<?php } elseif(\\1) { ?>\n", $template);
    $template = preg_replace("/[\n\r\t]*\{else\}[\n\r\t]*/is", "\n<?php } else { ?>\n", $template);
    for ($i = 0; $i < 5; $i++) {
        $template = preg_replace("/[\n\r\t]*\{loop\s+(\S+?)([<>=]+)(\S+)\s+(\S+?)([+-].*?)\}[\n\r]*(.+?)[\n\r]*\{(?:pool|\/loop)\}[\n\r\t]*/is", "<?php for(\\4=\\1;\\4\\2\\3;\\4\\5) { ?>\n\\6\n<?php }?>\n", $template);
        $template = preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{(?:pool|\/loop)\}[\n\r\t]*/is", "<?php if(isset(\\1) && is_array(\\1)) { \\2_index=0;foreach(\\1 as \\2) { \\2_index++;?>\n\\3\n<?php } } ?>\n", $template);
        $template = preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{(?:pool|\/loop)\}[\n\r\t]*/is", "<?php if(isset(\\1) && is_array(\\1)) { \\2_index=0;foreach(\\1 as \\2 => \\3) { \\2_index++;?>\n\\4\n<?php } } ?>\n", $template);
        $template = preg_replace("/[\n\r\t]*\{if\s+(.+?)\}[\n\r]*(.+?)[\n\r]*\{(?:fi|\/if)\}[\n\r\t]*/is", "<?php if(\\1) { ?>\n\\2\n<?php } ?>\n", $template);
    }
    $template = preg_replace_callback("/\{get\s+([^}]+\/)\}/is", "_xtf_get_parse", $template);
    $template = preg_replace_callback("/\{get\s+([^}]+)\}(.*?)(?:\{teg\}|\{\/get\})/is", "_xtf_get_parse", $template);
    //$template = preg_replace("/(['\"])\S+?#\{(.*?),?([^,]*?)\}#.*?\\1/e", "_xtf_uri('\\1','\\2','\\3')", $template);
    $template = preg_replace("/<\/form>/", "<input type=\"hidden\" name=\"xt_formhash\" value=\"{XT_FORMHASH}\" />\n</form>", $template);
    $template = preg_replace("/\{$const_regexp\}/s", "<?php echo \\1?>", $template);
    $template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);
    $template = preg_replace("/[\n\r\t]*\{@(.+?)@\}[\n\r\t]*/is", "<?php \\1 ?>", $template);

    $template = preg_replace("/<([\w]+)\s+xtif=\"([^\"]*)\"(.*?)<\/\\1>/is", "<?php if(\\2){?><\\1\\3</\\1><?php } ?>", $template);
    $template = preg_replace("/<([\w]+)\s+xtiif=\"([^\"]*)\"([^>])*>(.*?)<\/\\1>/is", "<?php if(\\2){?><\\1\\3>\\4</\\1><?php }else{?>\\4<?php } ?>", $template);
    $template = preg_replace("/<([\w]+)\s+xtacl=\"([^\"]*)\"(.*?)<\/\\1>/is", "<?php if(xt_acl('\\2')){?><\\1\\3</\\1><?php } ?>", $template);
    $template = preg_replace_callback("/xtp=\"([^\"]*)\"/", "_xtf_xpt_v", $template);
    $template = preg_replace_callback("/<!\-\-\/##\-\->(.*?)<!\-\-\\##\/\-\->/s", "_xtf_eval", $template);

    $template = str_replace('{$__xtf_js_langs};', "{{$__xtf_js_langs}};", $template);

    unset($__xtf_js_langs);
    unset($__xtf_viewdir);
    unset($__xtf_langs);

    if (!defined('XT_NS_PREFIX'))
        $template = xt_replace_once('<head>', "<head>\n<script type=\"text/javascript\">var XT_NS_PREFIX='$_XC[namespace_prefix]';var XT_STATIC_URL='" . xt_absurl($_XC['static_url']) . "';</script>", $template);

    // xtiger add for return pasre result
    if ($objfile) write_template_to_objfile($template, $objfile);
    else return $template;
    return '';
}

function write_template_to_objfile($template, $objfile)
{
    $template = trim($template);
    if (!empty($template)) {
        $needwrite = false;

        if (file_exists($objfile) && @unlink($objfile)) {
            xt_writefile($objfile . '.tmp', $template, 'text', 'w', 0);
            if (@rename($objfile . '.tmp', $objfile)) {
                $needwrite = false;
            } else {
                $needwrite = true;
            }
        } else {
            $needwrite = true;
        }
        //wirte second
        if ($needwrite) xt_writefile($objfile, $template, 'text', 'w', 0);
    } else {
        $template = "<?php if(!defined('MS_XTIGER')) exit('Access Denied');?>\r\n\r\n";
        xt_writefile($objfile, $template, 'text', 'w', 0);
    }
}

function _xtf_eval($m)
{
    $b = trim($m[1]);
    $r = preg_split('/<\?php (.*?)\?>/', $b, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    $s = '';
    $str = '';
    $c = count($r);
    if (substr($b, 0, 5) == '<?php') {
        for ($i = 0; $i < $c; $i = $i + 2) {
            $str .= $r[$i];
            if ($i + 1 < $c)
                $str .= '$s.="' . trim($r[$i + 1]) . '";';
        }
    } else {
        for ($i = 0; $i < $c; $i = $i + 2) {
            $str .= '$s.="' . trim($r[$i]) . '";';
            if ($i + 1 < $c)
                $str .= $r[$i + 1];
        }
    }
    eval($str);
    return $s;
}

function _xtf_read($p)
{
    global $_XC, $__xtf_xtigers, $__xtf_viewdir;

    $viewdir = $__xtf_viewdir;
    $m = $p[1];
    $body = isset($p[2]) ? $p[2] : "";

    $key = explode(',', $m);
    if ($body) $key[] = str_replace('\"', '"', $body);
    if (($idx = strpos($key[0], '.')) !== FALSE) {
        $viewfile = $key[0];
    } else {
        $ext = empty($_XC['view_ext']) ? '.html.php' : $_XC['view_ext'];
        $viewfile = $key[0] . $ext;
    }

    if (file_exists($viewdir . $viewfile)) {
        if (!$fp = @fopen($viewdir . $viewfile, 'r')) {
            return '';
        }
        $template = fread($fp, filesize($viewdir . $viewfile));
        fclose($fp);
        $template = str_replace('<?php exit?>', '', $template);
    } else {
        $template = '';
    }
    if (count($key) > 1) {
        $c = floor((count($key) - 1) / 2) * 2 + 1;
        for ($i = 1; $i < $c; $i += 2) {
            $template = str_replace($key[$i], $key[$i + 1], $template);
        }
        if (defined('IN_XTIGER')) {
            $id = array_pop($key);
            if (substr($key[0], 0, 6) == 'block/' && count($key) % 2 == 1 && substr($id, 0, 1) == '@') {
                $id = substr($id, 1);
                $key[0] = $viewfile;
                $__xtf_xtigers[$id] = array(implode(',', $key), $template);
                $template = '<div id="block_' . $id . '">' . $template . '</div>';
            }
            $template = preg_replace("/<\!\-\-\/\*(?:block(\d*)(.*?)\-\->(.+?)<\!\-\-.*?|.*?)\*\/\-\->/es", "_xtf_xtiger(\$template,\$viewfile,\$viewdir,'\\1','\\2','\\3')", $template);
        }
    }

    $template = preg_replace_callback("/<\!\-\-\{&&(.+?)\}(.*?)\}\-\->/s", '_xtf_read', $template); //require views
    $template = preg_replace_callback("/<\!\-\-\{&(.+?)\}\-\->/", '_xtf_read', $template); //require views
    return $template;
}

function _xtf_constant($m)
{
    if (defined($m[1])) {
        return constant($m[1]);
    }
    return $m[1];
}

function _xtf_replace_constant($str)
{
    return preg_replace_callback("/^(MS_.*?)\//", "_xtf_constant", $str);
}

function _xtf_check($m)
{
    $m[3] = trim($m[3]);
    return "<$m[1]$m[2]<?php echo $m[3]?'" . ($m[1] == 'option' ? 'selected="selected"' : 'checked="checked"') . "':''?>";
}

function _xtf_get_parse($m)
{
    $str = str_replace("\"", "\\\"", $m[1]);
    $end = isset($m[2]) ? $m[2] : "";

    $end = str_replace('\"', '"', $end);
    $matches = $r = array();
    preg_match_all("/([a-z]+)\=\"([^\"]+)\"/i", stripslashes($str), $matches, PREG_SET_ORDER);
    foreach ($matches as $v) {
        $r[$v[1]] = $v[2];
    }
    extract($r);
    if (!isset($dbsource)) $dbsource = '';
    if (!isset($sql)) $sql = '';
    if (!isset($start)) $start = 0;
    if (!isset($rows)) $rows = 1;
    if (!isset($distinctfield)) $distinctfield = '';
    if (!isset($return) || !preg_match("/^\w+$/i", $return)) $return = 'r';
    if (!isset($php)) $php = '';
    if (!isset($pair)) $pair = '';
    //if(!isset($pair)) $pair = '';
    if (!isset($split)) $split = NULL;
    if (isset($page)) {
        if (!isset($pagectrl)) $pagectrl = $return . '_page';
        if (substr($str, -1) == '/') {
            return "<?php $php\$ARRAY = xt_sql(\"$sql\" , $start, $rows, $page,'$pair', '$dbsource','$distinctfield');\${$return}=\$ARRAY['data'];\${$pagectrl}=\$ARRAY['pagectrl'];?>";
        }
        return "<?php $php\$ARRAY = xt_sql(\"$sql\" , $start, $rows, $page,'$pair', '$dbsource','$distinctfield');\${$return}_data=\$ARRAY['data'];\${$pagectrl}=\$ARRAY['pagectrl'];if(isset(\${$return}_data) &&is_array(\${$return}_data)){foreach(\${$return}_data as \${$return}_n=>\${$return}){\${$return}_n++;?>$end<?php }} unset(\${$return}_data);?>";
    } else {
        if (substr($str, -1) == '/') {
            if ($split) {
                $split = explode(',', $split);
                $idx = 1;
                $idx1 = 0;
                $r = '';
                foreach ($split as $v) {
                    if (intval($v, 0) == 0) continue;
                    $r .= "\${$return}$idx=array_slice(\$ARRAY,$idx1,$v);\n";
                    $idx++;
                    $idx1 += intval($v, 0);
                }
                $r .= "unset(\$ARRAY);unset(\$A);";
                return "<?php $php\$ARRAY = xt_sql(\"$sql\", $start, $idx1,-1,'$pair','$dbsource');\$A=array();\n" . $r . '?>';
            }
            return "<?php $php\${$return} = xt_sql(\"$sql\", $start, $rows,-1,'$pair','$dbsource');\${$return}_cnt=count(\${$return});?>";
        }
        if ($rows == 1) {
            return "<?php $php\${$return} = xt_sql(\"$sql\", $start, $rows, -1 ,'$pair', '$dbsource');if(\${$return}) {?>$end<?php } unset(\${$return});?>";
        }
        return "<?php $php\${$return}_data = xt_sql(\"$sql\", $start, $rows, -1 ,'$pair', '$dbsource');\${$return}_n =0;if(isset(\${$return}_data) && is_array(\${$return}_data)) {foreach(\${$return}_data as  \${$return}) { \${$return}_n++;?>$end<?php }} unset(\${$return}_data);?>";
    }
}

function xtf_language($m)
{
    global $__xtf_js_langs;
    global $__xtf_langs;
    $lang = $__xtf_langs;
    $x = $m[1];
    $js = isset($m[2]) ? $m[2] : "";
    if (!empty($x)) {
        if ($js == '@') {
            $d = ',';
            $r = '';
            if (!$__xtf_js_langs) {
                $r .= '<script type="text/javascript">function $lang(k){var lang={$__xtf_js_langs};var p = arguments;return (p.length>1?lang[k].replace(/\{(\d+?)\}/g,function(p0,p1){return p[p1]}):lang[k])}</script>';
                $d = '';
            }
            foreach (explode(',', $x) as $v) {
                $__xtf_js_langs .= "$d'$v':'" . str_replace("'", "\'", xt_lang_include($lang, $v, FALSE)) . "'";
                $d = ',';
            }
        } else {
            $r = '<?php $_XH_LANG=array_merge(array(';
            $d = '';
            foreach (explode(',', $x) as $v) {
                $r .= "$d'$v'=>'" . str_replace("'", "\'", xt_lang_include($lang, $v, FALSE)) . "'";
                $d = ',';
            }
            $r .= '),$_XH_LANG); ?>';
        }
        return $r;
    }
    return '';
}

function xtf_includelang($p)
{
    global $__xtf_langdir;
    $langdir = $__xtf_langdir;
    $lang = $p[1];
    foreach (explode(',', $lang) as $v) {
        if (file_exists(MS_APPPATH . $langdir . $v . '.lang.php')) {
            include_once(MS_APPPATH . $langdir . $v . '.lang.php');
            if (function_exists('xt_' . $v . '_lang')) {
                return "<?php include_once MS_APPPATH.'$langdir$v.lang.php';  \$_XH_LANG=array_merge(xt_{$v}_lang(),\$_XH_LANG);?>";
            }
        }
    }
    return '';
}

function _xtf_static_lang($m)
{
    global $__xtf_langs;
    $map = $__xtf_langs;
    $key = $m[1];
    $d = isset($m[2]) ? $m[2] : "";

    if (empty($key)) return '';

    $key = explode(',', $key);
    $v = array_key_exists($key[0], $map) ? $map[$key[0]] : ($d ? str_replace('\"', '"', $d) : $key[0]);
    if (count($key) > 1) {
        return preg_replace("/\{(\d*)\}/e", "_xtf_static_klang(\$key,'\\1')", $v);
    }
    if (preg_match("/\{\\\$[^\}]*\}/", $v)) {
        return '<?php echo "' . str_replace('"', '\"', $v) . '" ?>';
    }
    return $v;
}

function _xtf_static_klang($key, $k)
{
    return isset($key[$k]) ? $key[$k] : '';
}

function _xtf_uri_auth($m)
{

    return xt_uri($m[2], $m[1]);
}

function _xtf_suri_auth($m)
{
    return xt_static_uri($m);
}

function _xtf_uri1($m)
{
    return 'background:' . $m[1] . 'url(' . xt_static_uri($m[2]) . ')';
}

function _xtf_uri($m)
{

    $p1 = strtolower($m[1]);
    $p2 = str_replace('\"', '"', $m[2]);
    $p3 = str_replace('\"', '"', $m[3]);
    $p4 = $m[4];
    $matches = array();
    if (preg_match("/\S*?\#\{(.*?),?([^,]*?)\}\#/", $p4, $matches)) {
        $p4 = xt_uri($matches[2], $matches[1]);
    } else if ($p1 == 'a' || $p1 == 'form') {
        if (defined('SKIP_HREF')
            || substr($p4, 0, 10) == 'javascript'
            || substr($p4, 0, 1) == '#'
            || substr($p4, 0, 1) == '/'
            || preg_match("/:\/\/|@\\(|\\$\\{/", $p4)
        )
            return "<$p1$p2=$p3$p4$p3";

        if (substr($p4, 0, 2) == '##' || substr($p4, -2) == '##') {
            $p4 = trim($p4, '#');
        } else {
            $p4 = xt_uri('', $p4);
        }
    } else {
        $p4 = xt_static_uri($p4);
    }
    return "<$p1$p2=$p3$p4$p3";
}

/**
 *
 * add quote for array
 * @param string $var
 * @return
 */
function _xtf_var($m)
{
    $var = str_replace("\"", "\\\"", $m[1]);
    $g = isset($m[2]) ? str_replace("\"", "\\\"", $m[2]) : "";
    //1.change @(v2) to {$v2}
    //2.change $v[key] to $v['key']
    $var = preg_replace("/([\\$!a-zA-Z_][a-zA-Z0-9_]*)\[(\\\\\")?([a-zA-Z_][a-zA-Z0-9_\-\.]+)(\\2)?\]/", "\\1['\\3']", '$' . preg_replace("/@\((.*?)\)/", '{$\\1}', trim($var)));
    //change sub langauges,{$!lang} or {$!$lang}
    $var = preg_replace("/\{\\\$\!(\\\$)?([^\}]*)\}/", "\".xt_lang1(\$\\2,'\\1').\"", $var);

    $m = array();
    if (preg_match("/(\\$[!\\\$a-zA-Z_][a-zA-Z0-9_]*?(\['[a-zA-Z0-9_\-\.]+'\])?)(\!{1,2}|\?{1,2}|@)([^!]*)(.*)/", $var, $m)) {
        //1. ${var?e1!e2}
        //2. ${var!e2}
        //3. ${var@funcion}
        $v = preg_replace("/\\\$\!(\\\$)?(.*)/", "xt_lang1(\$\\2,'\\1')", $m[1]);
        $m[1] = preg_replace("/\\\$\!(\\\$)?(.*)/", "\$\\2", $m[1]);
        $mp = $m[3] == '!' || $m[3] == '!!' ? $m[4] : substr($m[5], 1);
        if ($g) $g = " global $m[1];";
        if ($m[3] == '@') {
            $m[4] = strpos($m[4], '(') == FALSE ? $m[4] . "($m[1])" : str_replace('(', "($m[1],", $m[4]);
            return "<?php$g echo $m[4]?>";
        }
        if (($m[3] == '?' || $m[3] == '??') && $m[4] && !$mp) {
            $m[4] = preg_replace("/\(\s*\)/", '', $m[4]);
            return $m[3] == '?' ? "<?php$g echo isset($m[1])?\"$m[4]\":'' ?>" : "<?php$g echo \"$m[4]\"?>";
        }
        return ($m[3] == '??' || $m[3] == '!!' ? "<?php$g echo isset($m[1]) && !empty($m[1])" : "<?php$g echo isset($m[1])") . '?' . (empty($m[5]) ? $v : ("\"$m[4]\"")) . ":\"" . $mp . "\"?>";
    }
    $var = preg_replace("/\\\$\!(\\\$)?(.*)/", "xt_lang1(\$\\2,'\\1')", $var);
    if ($g) $g = " global $var;";
    return "<?php$g echo $var?>";
}

function _xtf_xtiger($template, $p, $viewdir, $id, $title, $body)
{
    global $__xtf_xtigers, $_XC;
    if ($title) {
        $body = str_replace("\\\"", "\"", $body);
        $match = array();
        if ($id) {
            $data = $viewdir . ',' . $p . ',' . $title . ',' . $__xtf_xtigers[$id][0];
            $data = xt_authcode(strlen($data) . ',' . strlen($__xtf_xtigers[$id][1]) . ',' . $data . $body . $__xtf_xtigers[$id][1], 'ENCODE', $_XC['xtiger_key'] . $_XC['xtiger_ip']);
            return "<div href='" . XTIGER_URL . "?id=$id' data='$data' target='_blank'  id='float_div_$id'
                class='block_float_div nyroModal' blockid='$id'  blockname='$title'
                style='display:none'></div>";
        }
        $data = $viewdir . ',' . $p . ',' . $title;
        $data = xt_authcode(strlen($data) . ',' . $data . $body, 'ENCODE', $_XC['xtiger_key'] . $_XC['xtiger_ip']);
        return "<div href='" . XTIGER_URL . "?id=-1' data='$data'  target='_blank' class='block_add nyroModal'
                blockname='$title' style='display:none'></div>$body";
    }
}

global $__xtps;
function _xtf_xpt($m)
{
    global $__xtps;
    $var = $m[1];
    $v = str_replace("\\\"", "\"", trim($var));
    $r = preg_split('/(xtp_[\d]+?):/', $v, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    for ($i = 0; $i < count($r); $i = $i + 2) {
        $__xtps[$r[$i]] = $r[$i + 1];
    }
    return "";
}

function _xtf_xpt_v($m)
{
    global $__xtps;
    $var = $m[1];
    $key = explode(',', $var);
    if (count($key) > 1) {
        $c = count($key);
        $var = $__xtps['xtp_' . $key[0]];
        for ($i = 1; $i < $c; $i++) {
            $var = str_replace('{' . $i . '}', $key[$i], $var);
        }
        return '<?php ' . $var . ' ?>';
    }
    return '<?php ' . $__xtps['xtp_' . $var] . ' ?>';
}

/**
 *
 *
 * @param string $expr
 * @return
 */
function _xtf_striptagquotes($expr)
{
    $expr = preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr);
    $expr = str_replace("\\\"", "\"", preg_replace("/\[\'([a-zA-Z0-9_\-\.\x7f-\xff]+)\'\]/s", "[\\1]", $expr));
    return $expr;
}

/**
 *
 * @param string $var
 * @return
 */
/*function _xtf_languagevar($m, $lang) {
	return xt_hashmap($lang,$m);
}*/

/**
 *
 *
 * @param string $cachekey
 * @param string $parameter
 * @return
 */
function _xtf_blocktags($cachekey, $parameter)
{
    return striptagquotes("<?php block(\"$cachekey\", \"$parameter\"); ?>");
}

function _xtf_replacevars($v)
{
    global $_XH_D, $_XC;
    return empty($_XH_D[$v]) ? $_XC[$v] : $_XH_D[$v];
}

?>