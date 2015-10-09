<?php

defined('MS_XTIGER') or exit('Access Denied');


function pdo()
{
    static $db;
    if (empty($db)) {
        xt_load()->library('db');
        $db = new XTDB();
    }
    return $db;
}


function pdo_query($sql, $params = array())
{
    return pdo()->query($sql, $params);
}


function pdo_fetchcolumn($sql, $params = array(), $column = 0)
{
    return pdo()->fetchcolumn($sql, $params, $column);
}

function pdo_fetch($sql, $params = array())
{
    return pdo()->fetch($sql, $params);
}

function pdo_fetchall($sql, $params = array(), $keyfield = '')
{
    return pdo()->fetchall($sql, $params, $keyfield);
}


function pdo_update($table, $data = array(), $params = array(), $glue = 'AND')
{
    return pdo()->update($table, $data, $params, $glue);
}


function pdo_insert($table, $data = array(), $replace = FALSE)
{
    return pdo()->insert($table, $data, $replace);
}


function pdo_delete($table, $params = array(), $glue = 'AND')
{
    return pdo()->delete($table, $params, $glue);
}


function pdo_insertid()
{
    return pdo()->insertid();
}


function pdo_begin()
{
    pdo()->begin();
}


function pdo_commit()
{
    pdo()->commit();
}


function pdo_rollback()
{
    pdo()->rollBack();
}


function pdo_run($sql)
{
    pdo()->run($sql);
}


function pdo_fieldexists($tablename, $fieldname = '')
{
    return pdo()->fieldexists($tablename, $fieldname);
}


function pdo_indexexists($tablename, $indexname = '')
{
    return pdo()->indexexists($tablename, $indexname);
}


function pdo_fetchallfields($tablename)
{
    $fields = pdo_fetchall("DESCRIBE {$tablename}", array(), 'Field');
    $fields = array_keys($fields);
    return $fields;
}


function pdo_tableexists($tablename)
{
    return pdo()->tableexists($tablename);
}
