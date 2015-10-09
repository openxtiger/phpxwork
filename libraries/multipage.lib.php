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
������ PageSupport
���ܣ���ҳ��ʾMySQL���ݿ��е�����
***********************************************/
class CXTMultipage {
	//����
	var $sql;					//��Ҫ��ʾ���ݵ�SQL��ѯ���
	var $page_size;				//ÿҳ��ʾ�������

	var $start_index;			//��Ҫ��ʾ��¼���������
	var $total_records;			//��¼����
	var $current_records;		//��ҳ��ȡ�ļ�¼��
	var $result;				//�����Ľ��

	var $total_pages;			//��ҳ��
	var $current_page;			//��ǰҳ��
	var $display_count = 3;     //��ʾ��ǰ��ҳ�ͺ�ҳ��

	var $arr_page_query;		//���飬������ҳ��ʾ��Ҫ���ݵĲ���

	var $first;
	var $prev;
	var $next;
	var $last;

	//����
    /*********************************************
    ���캯����__construct()
    ���������
            $ppage_size��ÿҳ��ʾ�������
    ***********************************************/
     function CXTMultipage($ppage_size) {
        $this->page_size=$ppage_size;
        $this->start_index=0;
     }


    /*********************************************
    ���캯����__destruct()
    ���������
    ***********************************************/
     function __destruct() {

     }

    /*********************************************
    get������__get()
    ***********************************************/
     function __get($property_name) {
         if(isset($this->$property_name)) {
                return($this->$property_name);
         } else {
                return(NULL);
         }
     }

    /*********************************************
    set������__set()
    ***********************************************/
     function __set($property_name, $value) {
        $this->$property_name = $value;
     }

    /*********************************************
    ��������read_data
    ���ܣ�	����SQL��ѯ���ӱ��ж�ȡ��Ӧ�ļ�¼
    ����ֵ�����Զ�ά����result[��¼��][�ֶ���]
    ***********************************************/
     function read_data() {
        $psql=$this->sql;
        $db = xt_load('db');

        //��ѯ���ݣ����ݿ����ӵ���ϢӦ������õ��ⲿʵ��
        //$result=mysql_query($psql) or die(mysql_error());
        $result= $db->query($psql);

        //$this->total_records=mysql_num_rows($result);
        $this->total_records= $db->num_rows($result);

        //����LIMIT�ؼ��ֻ�ȡ��ҳ��Ҫ��ʾ�ļ�¼
        if($this->total_records>0)
        {
            $this->start_index = ($this->current_page-1)*$this->page_size;
            $psql=$psql.	" LIMIT ".$this->start_index." , ".$this->page_size;

            //$result=mysql_query($psql) or die(mysql_error());
            $result=$db->query($psql);
            $this->current_records=$db->num_rows($result);

            //����ѯ�������result������
            $i=0;
            //while($row=mysql_fetch_Array($result))
            while($row=$db->fetch_array($result))
            {
                $this->result[$i]=$row;
                $i++;
            }
        }


        //��ȡ��ҳ������ǰҳ��Ϣ
        $this->total_pages=ceil($this->total_records/$this->page_size);

        $this->first=1;
        $this->prev=$this->current_page-1;
        $this->next=$this->current_page+1;
        $this->last=$this->total_pages;
     }

     /**
      * ��ȡǰһҳ
      * @return
      */
      function  getPrev(){
        return $this->prev;
      }

     /**
      * ��ȡ��һҳ
      * @return
      */
      function  getNext(){
         return $this->next;
      }

     /**
      * ��ȡ���һҳ
      * @return
      */
      function getLast(){
         return $this->total_pages;
      }

     /**
      * ��ȡÿҳ��ʾ��¼��
      * @return
      */
      function  getPageSize() {
         return $this->page_size;
      }

     /**
      * ��ȡ�ܼ�¼��
      * @return
      */
      function  getTotalCount() {
         return $this->total_records;
      }

     /**
      * �õ���ҳ��
      * @return
      */
      function  getPageCount() {
         return $this->total_pages;
      }

     /**
      * �ж��Ƿ���ǰһҳ
      * @return
      */
      function  getIsPrev(){
         if($this->current_page>1){
             return TRUE;
         }
         return FALSE;
      }



     /**
      * �ж��Ƿ��к�һҳ
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