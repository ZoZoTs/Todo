<?php

class SQL_model
{	
	private $sql = null;
	private $name = null;
	
	public function __construct($_sql, $_name) {
		$this->sql = $_sql;
		$this->name = $_name;
	}

	public function get($_sql_query, $_sql_parameters) {
	    try {
            $sql_query = $this->sql->prepare($_sql_query);
            $sql_query->execute($_sql_parameters);
            if ($sql_res = $sql_query->fetchAll()) {
				$res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
				$res["DATA"]   = "NOT FOUND";
				$res["STATUS"] = 404;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage().' Query: '.$_sql_query." Parameters: ".json_encode($_sql_parameters);
			$res["STATUS"] = 500;
		}
	
		return $res;
	}
	
	public function first($_sql_query, $_sql_parameters) {
	    try {
            $sql_query = $this->sql->prepare($_sql_query);
            $sql_query->execute($_sql_parameters);
            if ($sql_res = $sql_query->fetch()) {
				$res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
				$res["DATA"]   = "NOT FOUND";
				$res["STATUS"] = 404;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage().' Query: '.$_sql_query." Parameters: ".json_encode($_sql_parameters);
			$res["STATUS"] = 500;
		}
	
		return $res;
	}
	
	public function add($_sql_insert_query, $_sql_insert_parameters, $_sql_select_query, $_sql_select_parameters, $json) {
	    try {
            $sql_query = $this->sql->prepare($_sql_insert_query);
            if ($sql_query->execute($_sql_insert_parameters)) {
                $sql_query = $this->sql->prepare($_sql_select_query);
                $sql_query->execute($_sql_select_parameters);
                if ($sql_res = $sql_query->fetch()) {
                    if (class_exists ( "Relations" )) {
                            $rel = new Relations($this->sql);
                    		$rel->action("add", $this->name, $sql_res["id"], $json->{'mycreator'} );
                    }
    				$res["DATA"]   = $sql_res;
    				$res["STATUS"] = 200;
                }  else {
				    $res["DATA"]   = "DATA IS INSERTED BUT CANNOT SELECT FROM TABLE";
				    $res["STATUS"] = 500;
                }
            } else {
				$res["DATA"]   = "NOT INSERTED";
				$res["STATUS"] = 500;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage().' INSERT: Query: '.$_sql_insert_query." Parameters: ".json_encode($_sql_insert_parameters).
                                              ' SELECT: Query: '.$_sql_select_query." Parameters: ".json_encode($_sql_select_parameters);
			$res["STATUS"] = 500;
		}
	
		return $res;
	}
	
	public function update($_sql_query, $_sql_parameters, $_model) {
	    try {
            $sql_query = $this->sql->prepare($_sql_query);
            $sql_query->execute($_sql_parameters);
            $res["DATA"]   = $_model;
			$res["STATUS"] = 200;
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage().'Query: '.$_sql_query." Parameters: ".json_encode($_sql_parameters);
			$res["STATUS"] = 500;
		}
	
		return $res;
	}
	
	public function delete($_sql_query, $_id) {
	    try {
            $sql_query = $this->sql->prepare($_sql_query);
            $sql_query->execute($_id);
            if (class_exists ( "Relations" )) {
                $rel = new Relations($this->sql);
        		$rel->action("delete", $this->name, $_id);
            }
            $res["DATA"]   = "";
			$res["STATUS"] = 204;
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage().'Query: '.$_sql_query." Parameters: ".$_id;
			$res["STATUS"] = 500;
		}
	
		return $res;
	}
}