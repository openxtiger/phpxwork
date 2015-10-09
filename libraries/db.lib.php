<?php
/**
 * [WeiZan System] Copyright (c) 2014 WeiZan.Com
 * WeiZan is NOT a free software, it under the license terms, visited http://www.wdlcms.com/ for more details.
 */
defined('MS_XTIGER') or exit('Access Denied');
define('PDO_DEBUG', true);

class XTDB
{

    private $pdo;
    private $cfg;
    private $errors = array();

    public function getPDO()
    {
        return $this->pdo;
    }

    public function __construct($name = 'db')
    {
        global $_XC;
        if (is_array($name)) {
            $cfg = $name;
        } else {
            $cfg = $_XC[$name];
        }
        $dsn = "mysql:dbname={$cfg['name']};host={$cfg['host']};port={$cfg['port']}";
        $options = array();
        if (class_exists('PDO')) {
            if (extension_loaded("pdo_mysql") && in_array('mysql', PDO::getAvailableDrivers())) {
                $dbclass = 'PDO';
                $options = array(PDO::ATTR_PERSISTENT => $cfg['pconnect']);
            } else {
                if (!class_exists('_PDO')) {
                    xt_load()->library("pdo.PDO");
                }
                $dbclass = '_PDO';
            }
        } else {
            xt_load()->library("pdo.PDO");
            $dbclass = 'PDO';
        }
        try {
            $this->pdo = new $dbclass($dsn, $cfg['username'], $cfg['password'], $options);
        } catch (RuntimeException $e) {
            xt_exception_handler(E_NOTICE, $e->getMessage(), $e->getFile(), $e->getLine());
            return;
        }
        $sql = "SET NAMES '{$cfg['charset']}';";
        $this->pdo->exec($sql);
        $this->pdo->exec("SET sql_mode='';");
        $this->cfg = $cfg;
        if (PDO_DEBUG) {
            $info = array();
            $info['sql'] = $sql;
            $info['error'] = $this->pdo->errorInfo();
            $this->debug($info);
        }
    }


    public function query($sql, $params = array())
    {
        if ($this->pdo == null) {
            return false;
        }
        try {
            $starttime = microtime();
            if (empty($params)) {
                $result = $this->pdo->exec($sql);
                if (PDO_DEBUG) {
                    $info = array();
                    $info['sql'] = $sql;
                    $info['error'] = $this->pdo->errorInfo();
                    $this->debug($info);
                }
                return $result;
            }
            $statement = $this->pdo->prepare($sql);
            $result = $statement->execute($params);
            if (PDO_DEBUG) {
                $info = array();
                $info['sql'] = $sql;
                $info['params'] = $params;
                $info['error'] = $statement->errorInfo();
                $this->debug($info);
            }
            $endtime = microtime();
            /*global $_XC;
            if (empty($_XC['setting']['maxtimesql'])) {
                $_XC['setting']['maxtimesql'] = 5;
            }
            if ($endtime - $starttime > $_XC['setting']['maxtimesql']) {
                $sqldata = array(
                    'type' => '2',
                    'runtime' => $endtime - $starttime,
                    'runurl' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                    'runsql' => $sql,
                    'createtime' => time()
                );
            }*/
            if (!$result) {
                return false;
            } else {
                return $statement->rowCount();
            }
        } catch (RuntimeException $e) {
            xt_exception_handler(E_NOTICE, $e->getMessage(), $e->getFile(), $e->getLine());
        }
        return false;
    }


    public function fetchcolumn($sql, $params = array(), $column = 0)
    {
        if ($this->pdo == null) {
            return false;
        }
        try {

            $starttime = microtime();
            $statement = $this->pdo->prepare($sql);
            $result = $statement->execute($params);
            if (PDO_DEBUG) {
                $info = array();
                $info['sql'] = $sql;
                $info['params'] = $params;
                $info['error'] = $statement->errorInfo();
                $this->debug($info);
            }
            $endtime = microtime();
            /*global $_XC;
            if (empty($_XC['setting']['maxtimesql'])) {
                $_XC['setting']['maxtimesql'] = 5;
            }
            if ($endtime - $starttime > $_XC['setting']['maxtimesql']) {
                $sqldata = array(
                    'type' => '2',
                    'runtime' => $endtime - $starttime,
                    'runurl' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                    'runsql' => $sql,
                    'createtime' => time()
                );
            }*/
            if (!$result) {
                return false;
            } else {
                return $statement->fetchColumn($column);
            }
        } catch (RuntimeException $e) {
            xt_exception_handler(E_NOTICE, $e->getMessage(), $e->getFile(), $e->getLine());
        }
        return false;
    }


    public function fetch($sql, $params = array())
    {
        if ($this->pdo == null) {
            return false;
        }
        try {

            $starttime = microtime();
            $statement = $this->pdo->prepare($sql);
            $result = $statement->execute($params);
            if (PDO_DEBUG) {
                $info = array();
                $info['sql'] = $sql;
                $info['params'] = $params;
                $info['error'] = $statement->errorInfo();
                $this->debug($info);
            }
            $endtime = microtime();
            /*global $_XC;
            if (empty($_XC['setting']['maxtimesql'])) {
                $_XC['setting']['maxtimesql'] = 5;
            }
            if ($endtime - $starttime > $_XC['setting']['maxtimesql']) {
                $sqldata = array(
                    'type' => '2',
                    'runtime' => $endtime - $starttime,
                    'runurl' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                    'runsql' => $sql,
                    'createtime' => time()
                );
            }*/
            if (!$result) {
                return false;
            } else {
                return $statement->fetch(pdo::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            xt_exception_handler(E_NOTICE, $e->getMessage(), $e->getFile(), $e->getLine());
        }
        return false;
    }


    public function fetchall($sql, $params = array(), $keyfield = '')
    {
        if ($this->pdo == null) {
            return false;
        }
        $starttime = microtime();
        $statement = $this->pdo->prepare($sql);
        $result = $statement->execute($params);
        if (PDO_DEBUG) {
            $info = array();
            $info['sql'] = $sql;
            $info['params'] = $params;
            $info['error'] = $statement->errorInfo();
            $this->debug($info);
        }
        $endtime = microtime();
        /*global $_XC;
        if (empty($_XC['setting']['maxtimesql'])) {
            $_XC['setting']['maxtimesql'] = 5;
        }
        if ($endtime - $starttime > $_XC['setting']['maxtimesql']) {
            $sqldata = array(
                'type' => '2',
                'runtime' => $endtime - $starttime,
                'runurl' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'runsql' => $sql,
                'createtime' => time()
            );
        }*/
        if (!$result) {
            return false;
        } else {
            if (empty($keyfield)) {
                return $statement->fetchAll(pdo::FETCH_ASSOC);
            } else {
                $temp = $statement->fetchAll(pdo::FETCH_ASSOC);
                $rs = array();
                if (!empty($temp)) {
                    foreach ($temp as $key => &$row) {
                        if (isset($row[$keyfield])) {
                            $rs[$row[$keyfield]] = $row;
                        } else {
                            $rs[] = $row;
                        }
                    }
                }
                return $rs;
            }
        }
    }


    public function update($table, $data = array(), $params = array(), $glue = 'AND')
    {
        if ($this->pdo == null) {
            return false;
        }
        $fields = $this->implode($data, ',');
        $condition = $this->implode($params, $glue);
        $params = array_merge($fields['params'], $condition['params']);
        $sql = "UPDATE " . $this->tablename($table) . " SET {$fields['fields']}";
        $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
        return $this->query($sql, $params);
    }


    public function insert($table, $data = array(), $replace = FALSE)
    {
        if ($this->pdo == null) {
            return false;
        }
        $cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
        $condition = $this->implode($data, ',');
        return $this->query("$cmd " . $this->tablename($table) . " SET {$condition['fields']}", $condition['params']);
    }


    public function insertid()
    {
        if ($this->pdo == null) {
            return -1;
        }
        return $this->pdo->lastInsertId();
    }


    public function delete($table, $params = array(), $glue = 'AND')
    {
        if ($this->pdo == null) {
            return false;
        }
        $condition = $this->implode($params, $glue);
        $sql = "DELETE FROM " . $this->tablename($table);
        $sql .= $condition['fields'] ? ' WHERE ' . $condition['fields'] : '';
        return $this->query($sql, $condition['params']);
    }


    public function begin()
    {
        if ($this->pdo == null) {
            return;
        }
        $this->pdo->beginTransaction();
    }


    public function commit()
    {
        if ($this->pdo == null) {
            return;
        }
        $this->pdo->commit();
    }


    public function rollback()
    {
        if ($this->pdo == null) {
            return;
        }
        $this->pdo->rollBack();
    }


    private function implode($params, $glue = ',')
    {

        $result = array('fields' => ' 1 ', 'params' => array());
        $split = '';
        $suffix = '';
        if (in_array(strtolower($glue), array('and', 'or'))) {
            $suffix = '__';
        }
        if (!is_array($params)) {
            $result['fields'] = $params;
            return $result;
        }
        if (is_array($params)) {
            $result['fields'] = '';
            foreach ($params as $fields => $value) {
                $result['fields'] .= $split . "`$fields` =  :{$suffix}$fields";
                $split = ' ' . $glue . ' ';
                $result['params'][":{$suffix}$fields"] = is_null($value) ? '' : $value;
            }
        }
        return $result;
    }


    public function run($sql, $stuff = 'wep_')
    {
        if (!isset($sql) || empty($sql)) return;

        $sql = str_replace("\r", "\n", str_replace(' ' . $stuff, ' ' . $this->cfg['tablepre'], $sql));
        $sql = str_replace("\r", "\n", str_replace(' `' . $stuff, ' `' . $this->cfg['tablepre'], $sql));
        $ret = array();
        $num = 0;
        $sql = preg_replace("/\;[ \f\t\v]+/", ';', $sql);
        foreach (explode(";\n", trim($sql)) as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            foreach ($queries as $query) {
                $ret[$num] .= (isset($query[0]) && $query[0] == '#') || (isset($query[1]) && isset($query[1]) && $query[0] . $query[1] == '--') ? '' : $query;
            }
            $num++;
        }
        unset($sql);
        foreach ($ret as $query) {
            $query = trim($query);
            if ($query) {
                $this->query($query);
            }
        }
    }


    public function fieldexists($tablename, $fieldname)
    {
        $isexists = $this->fetch("DESCRIBE " . $this->tablename($tablename) . " `{$fieldname}`");
        return !empty($isexists) ? true : false;
    }


    public function indexexists($tablename, $indexname)
    {
        if (!empty($indexname)) {
            $indexs = pdo_fetchall("SHOW INDEX FROM " . $this->tablename($tablename));
            if (!empty($indexs) && is_array($indexs)) {
                foreach ($indexs as $row) {
                    if ($row['Key_name'] == $indexname) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


    public function tablename($table)
    {
        return "`{$this->cfg['tablepre']}{$table}`";
    }


    public function debug($info)
    {
        if (intval($info['error'][0]) != 0) {
            $traces = debug_backtrace();
            $file = '';
            $line = 0;
            foreach ($traces as $trace) {
                if (strpos($trace['file'], '/WEB-INF/') !== FALSE) {
                    $file = str_replace(MS_APPPATH, '', $trace['file']);
                    $line = $trace['line'];
                    break;
                }
            }
            xt_exception_handler(E_ERROR, "[SQL]: {$info['sql']} [PARAMS]:" .
                xt_implode($info['params']) . " [ERROR]:{$info['error'][2]}", $file, $line);
        }
    }


    public function tableexists($table)
    {
        if (!empty($table)) {
            $data = $this->fetch("SHOW TABLES LIKE '{$this->cfg['tablepre']}{$table}'");
            if (!empty($data)) {
                $data = array_values($data);
                $tablename = $this->cfg['tablepre'] . $table;
                if (in_array($tablename, $data)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}
