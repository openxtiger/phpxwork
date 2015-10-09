<?php
/**
 * Created by openXtiger.org.
 * User: xtiger
 * Date: 2009-6-20
 * Time: 18:18:29
 */
class CXTTree {
    var $db;
	var $tree, $branch;
	var $skeep = false;
    public function CXTTree($db, $tree_name = 'tree', $branch_name = 'branch'){
        $this->db = &$db;
		$this->tree = &$tree_name;
		$this->branch = &$branch_name;
    }
    public function getTree($id = 0, $allChild = FALSE) {
        $r = $this->getTreeNode($id);
		if($r) {
		    $s = "SELECT {$this->branch}.*,lft,rgt FROM {$this->branch}, {$this->tree} WHERE tree_id = tid AND lft>={$r['lft']} AND rgt<={$r['rgt']}";
		    if(!$allChild) $s.=' AND pid='.$r['tid'];
		    $query = $this->db->query($s .' order by lft');
            $result = array();
            while ($row = $this->db->fetch_array($query)) {
                $result[] = $row;
            }
            return $result;
		}
		return FALSE;   
    }
    public function loadNodeById($bid) {
        return $this->db->fetch_first("SELECT {$this->branch}.* FROM {$this->branch}, {$this->tree} WHERE tree_id = tid AND id = {$bid}");   
    }
    public function getParentTree($bid,$allParent = TRUE){
        $r = $this->getTreeNode($bid);
		if($r) {
		    $s = "SELECT {$this->branch}.* FROM {$this->branch}, {$this->tree} WHERE tree_id = tid AND lft<={$r['lft']} AND rgt>={$r['rgt']} AND bid={$r['bid']} ";
		    if(!$allParent) $s.=' AND pid='.$r['tid'];
		    $query = $this->db->query($s .' order by lft');
            $result = array();
            while ($row = $this->db->fetch_array($query)) {
                $result[] = $row;
            }
            return $result;
		}
		return FALSE;
    }
    public function retrievePath($bid,$sp='/'){
        $r = $this->getTreeNode($bid);
		if($r) {
		    $s = "SELECT {$this->branch}.name FROM {$this->branch}, {$this->tree} WHERE tree_id = tid AND lft<={$r['lft']} AND rgt>={$r['rgt']} AND bid={$r['bid']} ";
		    $query = $this->db->query($s .' order by lft');
            $result = array();
            while ($row = $this->db->fetch_array($query)) {
                $result[] = $row['name'];
            }
            return $sp?implode($result,$sp):$result;
		}
		return $sp?'':array();
    }
    public function insertNode($bid){
        $r = $this->getTreeNode($bid);
		if($r) {
		    $destRgt = $r['rgt'];
		    $this->db->query("UPDATE {$this->tree} SET lft = lft + 2 WHERE lft >= $destRgt");
		    $this->db->query("UPDATE {$this->tree} SET rgt = rgt + 2 WHERE rgt >= $destRgt");
            $this->db->query("INSERT INTO {$this->tree} SET lft=$destRgt, rgt=".($destRgt + 1));
		    return $this->db->insert_id();
		}
		return -1;
    }
    public function removeNode($bid){
        $r = $this->getTreeNode($bid);
        if($r) {
            $lft = $r['lft'];$rgt = $r['rgt'];
            $sub = (($rgt - $lft  - 1) / 2 + 1) * 2;
            if($sub > 2 ) return 0;
            $this->db->query("DELETE FROM {$this->tree} WHERE lft>=$lft and rgt<=$rgt");
            $this->db->query("UPDATE {$this->tree} SET lft = lft - $sub WHERE lft > $lft");
		    $this->db->query("UPDATE {$this->tree} SET rgt = rgt - $sub WHERE rgt > $rgt");
		    return 1;
        }
        return -1;
    }
    
    public function moveNode() {

    }

    public function rebuildTree($pid = 0, $left = 0, $tid = 0) {
        $right = $left + 1;
        $result = $this->db->query("SELECT id,tree_id FROM {$this->branch} WHERE pid=$pid");
        while ($row = $this->db->fetch_array($result)) {
            $right = $this->rebuildTree($row['id'], $right,$row['tree_id']);
        }
        $this->db->query("UPDATE {$this->tree} SET lft=$left, rgt=$right WHERE tid=$tid");
        return $right+1;
    }
    public function rebuildRoot($pid, $rid){
        $result = $this->db->query("SELECT id FROM {$this->branch} WHERE pid=$pid and rid=0");
        while ($row = $this->db->fetch_array($result)) {
            $this->db->query("UPDATE {$this->branch} SET rid=$rid WHERE id={$row['id']}");
            $this->rebuildRoot($row['id'], $rid);
        }
    }
    private function getTreeNode($bid) {
        if($bid==0) {
            return $this->db->fetch_first("SELECT	lft,rgt,tid,bid FROM {$this->tree} WHERE lft = 1");
        }
        return $this->db->fetch_first("SELECT	lft,rgt,tid,bid FROM {$this->branch}, {$this->tree} WHERE tree_id = tid AND id = {$bid}");
    }
     
}