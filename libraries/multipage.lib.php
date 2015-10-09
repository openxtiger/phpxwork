<?php
/**
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-6-20
 * Time: 18:18:29
 */
/**
 * Created by openXtiger.org
 * User: lunzi
 * Date: 2009-6-18
 * Time: 15:34:48
 */

 /*********************************************
类名： PageSupport
功能：分页显示MySQL数据库中的数据
***********************************************/
class CXTMultipage {
	//属性
	var $sql;					//所要显示数据的SQL查询语句
	var $page_size;				//每页显示最多行数

	var $start_index;			//所要显示记录的首行序号
	var $total_records;			//记录总数
	var $current_records;		//本页读取的记录数
	var $result;				//读出的结果

	var $total_pages;			//总页数
	var $current_page;			//当前页数
	var $display_count = 3;     //显示的前几页和后几页数

	var $arr_page_query;		//数组，包含分页显示需要传递的参数

	var $first;
	var $prev;
	var $next;
	var $last;

	//方法
    /*********************************************
    构造函数：__construct()
    输入参数：
            $ppage_size：每页显示最多行数
    ***********************************************/
     function CXTMultipage($ppage_size) {
        $this->page_size=$ppage_size;
        $this->start_index=0;
     }


    /*********************************************
    构造函数：__destruct()
    输入参数：
    ***********************************************/
     function __destruct() {

     }

    /*********************************************
    get函数：__get()
    ***********************************************/
     function __get($property_name) {
         if(isset($this->$property_name)) {
                return($this->$property_name);
         } else {
                return(NULL);
         }
     }

    /*********************************************
    set函数：__set()
    ***********************************************/
     function __set($property_name, $value) {
        $this->$property_name = $value;
     }

    /*********************************************
    函数名：read_data
    功能：	根据SQL查询语句从表中读取相应的记录
    返回值：属性二维数组result[记录号][字段名]
    ***********************************************/
     function read_data() {
        $psql=$this->sql;
        $db = xt_load('db');

        //查询数据，数据库链接等信息应在类调用的外部实现
        //$result=mysql_query($psql) or die(mysql_error());
        $result= $db->query($psql);

        //$this->total_records=mysql_num_rows($result);
        $this->total_records= $db->num_rows($result);

        //利用LIMIT关键字获取本页所要显示的记录
        if($this->total_records>0)
        {
            $this->start_index = ($this->current_page-1)*$this->page_size;
            $psql=$psql.	" LIMIT ".$this->start_index." , ".$this->page_size;

            //$result=mysql_query($psql) or die(mysql_error());
            $result=$db->query($psql);
            $this->current_records=$db->num_rows($result);

            //将查询结果放在result数组中
            $i=0;
            //while($row=mysql_fetch_Array($result))
            while($row=$db->fetch_array($result))
            {
                $this->result[$i]=$row;
                $i++;
            }
        }


        //获取总页数、当前页信息
        $this->total_pages=ceil($this->total_records/$this->page_size);

        $this->first=1;
        $this->prev=$this->current_page-1;
        $this->next=$this->current_page+1;
        $this->last=$this->total_pages;
     }

     /**
      * 获取前一页
      * @return
      */
      function  getPrev(){
        return $this->prev;
      }

     /**
      * 获取后一页
      * @return
      */
      function  getNext(){
         return $this->next;
      }

     /**
      * 获取最后一页
      * @return
      */
      function getLast(){
         return $this->total_pages;
      }

     /**
      * 获取每页显示记录数
      * @return
      */
      function  getPageSize() {
         return $this->page_size;
      }

     /**
      * 获取总记录数
      * @return
      */
      function  getTotalCount() {
         return $this->total_records;
      }

     /**
      * 得到总页数
      * @return
      */
      function  getPageCount() {
         return $this->total_pages;
      }

     /**
      * 判断是否有前一页
      * @return
      */
      function  getIsPrev(){
         if($this->current_page>1){
             return TRUE;
         }
         return FALSE;
      }



     /**
      * 判断是否有后一页
      * @return
      */
      function  getIsNext(){
         if($this->current_page<$this->total_pages){
             return TRUE;
         }
         return FALSE;
      }

      function getPrevPages(){
        $front_start = 1;
        if($this->current_page > $this->display_count){
            $front_start = $this->current_page - $this->display_count;
        }
        for($i=$front_start;$i<$this->current_page;$i++){
             $pageArr[] = array('page_no'=>$i);
        }
        if(isset($pageArr)){
            return $pageArr;
        }
        return NULL;
     }

     function getNextPages(){
        $displayCount = $this->display_count;
        if($this->total_pages > $displayCount&&($this->current_page+$displayCount)<$this->total_pages){
            $displayCount = $this->current_page+$displayCount;
        }else{
            $displayCount = $this->total_pages;
        }


        for($i=$this->current_page+1;$i<=$displayCount;$i++){
            $pageArr[] = array('page_no'=>$i);
        }

        if(isset($pageArr)){
            return $pageArr;
        }
        return NULL;
     }

}