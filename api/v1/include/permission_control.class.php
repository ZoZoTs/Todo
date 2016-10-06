<?php

class Permission_control
{	
	private $sql = null;
	
	public function __construct($_sql) {
		$this->sql = $_sql;
	}



    public function get_permissions_list() {
		try {
            $sql_query = $this->sql->query("SELECT id, name FROM `auth`.`permissions` ORDER BY name");
            if ($sql_res = $sql_query->fetchAll()) {
				$res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
				$res["DATA"]   = "PERMISSIONS NOT FOUND";
				$res["STATUS"] = 404;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
		
		return $res;
	}
	
	public function get_permissions() {
		try {
            $sql_query = $this->sql->query("SELECT gr.id, gr.member_id, ug.name, ug.type, ".
										    "gr.permission_id, gr.access_type, gr.to_all, ".
										    "ap.name as permission_name ".
									        "from auth.granted_roles gr ".
										    "left join auth.users_groups ug ".
											"on gr.member_id=ug.id ".
											"left join auth.permissions ap ".
											"on gr.permission_id=ap.id ".
											"ORDER BY type, name, permission_id, access_type;");
            if ($sql_res = $sql_query->fetchAll()) {
				$res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
				$res["DATA"]   = "PERMISSIONS NOT FOUND";
				$res["STATUS"] = 404;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
		
		return $res;
	}
	
	public function get_permission($_member_id) {
		try {
            $sql_query = $this->sql->prepare("SELECT gr.id, gr.member_id, gr.permission_id, gr.access_type, gr.to_all, ap.name as permission_name ".
            									"FROM auth.granted_roles gr ".
            									"left join auth.permissions ap ".
											    "on gr.permission_id=ap.id ".
            									"WHERE gr.member_id=?;");
            $sql_query->execute(Array($_member_id));
            if ($sql_res = $sql_query->fetchAll()) {
				$res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
				$res["DATA"]   = "PERMISSIONS NOT FOUND";
				$res["STATUS"] = 404;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
		
		return $res;
	}
	
	
	public function get_mygroupsfor($_function_name, $_access_type, $_user_groups) {
		try {
            $sql_query = $this->sql->prepare("SELECT ug.id, ug.name, ug.type ".
                                		        "FROM auth.granted_roles gr LEFT JOIN auth.users_groups ug ON gr.member_id=ug.id ".
                                		        "where gr.member_id in (".$_user_groups.") and gr.permission_id=(select id from `auth`.`permissions` where name=?) and ".
                                		        "gr.access_type like '%".$_access_type."%' and gr.to_all=0 ".
                                		        "order by ug.name");
            $sql_query->execute(Array($_function_name));
            if ($sql_res = $sql_query->fetchAll()) {
				$res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
				$res["DATA"]   = "PERMISSIONS NOT FOUND";
				$res["STATUS"] = 404;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
		
		return $res;
	}
	
	
	
	public function add_permission($_permission) {
		if ($_permission->{"permission_id"} != '' && $_permission->{"member_id"} != 0 ) {	
			try {
                $sql_query = $this->sql->prepare("INSERT INTO auth.granted_roles (member_id, permission_id, access_type, to_all) VALUE (?, ?, ?, ?);");
                $sql_query->execute(Array($_permission->{"member_id"}, $_permission->{"permission_id"}, $_permission->{"access_type"}, $_permission->{"to_all"} ));
                
                $sql_query = $this->sql->prepare("SELECT gr.id, gr.member_id, gr.permission_id, gr.access_type, gr.to_all ".
				            				"FROM auth.granted_roles gr ".
							        		"WHERE gr.member_id=? and gr.permission_id=? and access_type=? ".
							        		"ORDER BY gr.id;");
                $sql_query->execute(Array($_permission->{"member_id"}, $_permission->{"permission_id"}, $_permission->{"access_type"} ));
                if ($sql_res = $sql_query->fetchAll()) {
    				$res["DATA"]   = $sql_res[count($sql_res)-1];
    				$res["STATUS"] = 201;
                } else {
    				$res["DATA"]   = "PERMISSION NOT FOUND";
    				$res["STATUS"] = 404;
                }
    		} catch (PDOException $e) {
                $res["DATA"]   = $e->getMessage();
    			$res["STATUS"] = 500;
    		}
			
		} else {
			$res["DATA"]   = "MEMBER ID, TYPE AND FUNCTION NAME REGUIRED";
			$res["STATUS"] = 406;		
		}
		return $res;
	}
	
	public function update_permission($_member_id, $_id, $_permission) {
		try {
            
            if ($_permission->{"permission_id"} == '' || $_permission->{"member_id"} == 0 ) {
                $res["DATA"]   = "MEMBER ID AND FUNCTION NAME REGUIRED";
    			$res["STATUS"] = 406; 
    			return $res;
            }
            
        
            $sql_query = $this->sql->prepare("SELECT permission_id FROM `auth`.`granted_roles` WHERE member_id=? and id=?");
            $sql_query->execute(Array($_member_id, $_id));
            if (! $sql_res = $sql_query->fetch()) {
            	$res["DATA"]   = "INVALID INPUT DATA";
    			$res["STATUS"] = 406;
    			return $res;
            }
            $permission_id=$sql_res['permission_id'];
            if ($sql_res['permission_id'] != $_permission->{"permission_id"}) {
                $sql_query = $this->sql->prepare("SELECT ap.name, aa.db_name FROM `auth`.`permissions` ap LEFT JOIN `auth`.`applications` aa ON ap.app_ids=aa.id WHERE ap.id = ?");
                $sql_query->execute(Array($permission_id));
                if (! $sql_res = $sql_query->fetch()) {
    				$res["DATA"]   = "DB SETTINGS ERROR";
    				$res["STATUS"] = 500;
    				return $res;
                }    
                    
                $sql_query = $this->sql->prepare("SELECT count(*) as co FROM `".$sql_res['db_name']."`.`granted_roles_".$sql_res['name']."` WHERE granted_roles_id = ?;");
                $sql_query->execute(Array($_id));
                if (! $sql_res = $sql_query->fetch()) {    
                    $res["DATA"]   = "DB SETTINGS ERROR";
    				$res["STATUS"] = 500;
    				return $res;
                }
                
                if ($sql_res["co"] != "0") {
                    $res["DATA"]      = "RELATIONS FOUND CANNOT CHANGE PERMISSION_ID TO ".$_permission->{"permission_id"}." FROM $permission_id"; //$permission_id used by frontend to change back select element
				    $res["STATUS"]    = 406;
				    return $res;
                }
            }
               
            $sql_query = $this->sql->prepare("UPDATE auth.granted_roles SET permission_id=?, access_type=?, to_all=?  WHERE member_id=? and id=?;");
            $sql_query->execute(Array($_permission->{"permission_id"}, $_permission->{"access_type"}, $_permission->{"to_all"}, $_member_id, $_id));
            $res["DATA"]   = $_permission;
			$res["STATUS"] = 200;

            
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		} 
		
		return $res;
	}

	public function delete_permission($_member_id, $_id) {
		try {
            $sql_query = $this->sql->prepare("DELETE FROM auth.granted_roles WHERE member_id=? and id=?;");
            $sql_query->execute(Array($_member_id, $_id));
            if (class_exists ( "Relations" )) {
                $rel = new Relations($this->sql);
        		$rel->action("delete", "permissions", $_id );
            }
            $res["DATA"]   = "";
			$res["STATUS"] = 204;
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
		
		return $res;
	}
	
	public function get_relation($_member_id, $_id) {
	    try {
            $sql_query = $this->sql->prepare("SELECT gr.id, ap.name, ap.get_ids, aa.db_name FROM auth.granted_roles gr left join `auth`.`permissions` ap on gr.permission_id=ap.id".
				                                " left join  `auth`.`applications` aa on aa.id=ap.app_ids WHERE gr.member_id=? and gr.id=?");
            $sql_query->execute(Array($_member_id, $_id));
            if ($sql_res = $sql_query->fetch()) {
				$function_name=$sql_res["name"];
				$get_ids=$sql_res["get_ids"];
				$db_name=$sql_res["db_name"];

                if ($get_ids) {
                    $sql1 = "SELECT resource_id, id, granted_roles_id FROM $db_name.granted_roles_".$function_name." WHERE granted_roles_id =".$sql_res["id"];
    		
            		$sql2 = "SELECT ".$sql_res["id"]." as granted_roles_id, ? as member_id, ".
            		                "t1.resource_id, t1.name, IF( t2.id is not null, 'true' , 'false') as checked, t2.id as id ".
            		        "FROM ( ".$get_ids." ) t1 ".
            		        "LEFT JOIN ($sql1) t2 ON t1.resource_id=t2.resource_id ORDER BY name";
            		
    				$sql_query = $this->sql->prepare($sql2);
                    $sql_query->execute(Array($_member_id));
                    if ($sql_res = $sql_query->fetchAll()) {
        				$res["DATA"]   = $sql_res;
        				$res["STATUS"] = 200;
                    } else {
            				$res["DATA"]   = "RELATION NOT FOUND";
            				$res["STATUS"] = 406;
            		}
                } else {
            				$res["DATA"]   = "RELATION NOT FOUND";
            				$res["STATUS"] = 406;
            		}
            } else {
				$res["DATA"]   = "INVALID INPUT PARAMETERS";
				$res["STATUS"] = 406;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		} 
		
		return $res;
	}
	
	public function add_relation($_relation) {
	    try {
             $sql_query = $this->sql->prepare("SELECT gr.id, ap.name, aa.db_name FROM auth.granted_roles gr left join `auth`.`permissions` ap on gr.permission_id=ap.id".
				                                " left join  `auth`.`applications` aa on aa.id=ap.app_ids WHERE gr.member_id=? and gr.id=?");
            $sql_query->execute(Array($_relation->{"member_id"}, $_relation->{"granted_roles_id"}));
            if ($sql_res = $sql_query->fetch()) {
    			$sql_query = $this->sql->prepare("INSERT INTO ".$sql_res["db_name"].".granted_roles_".$sql_res["name"]." (granted_roles_id, resource_id) VALUE (?, ?);");
                $sql_query->execute(Array($_relation->{"granted_roles_id"}, $_relation->{"resource_id"}));
                
                $sql_query = $this->sql->prepare("SELECT gr.id FROM ".$sql_res["db_name"].".granted_roles_".$sql_res["name"]." gr ".
        						        		"WHERE granted_roles_id=? and resource_id=?;");
                $sql_query->execute(Array($_relation->{"granted_roles_id"}, $_relation->{"resource_id"}));
                if ($sql_res2 = $sql_query->fetch()) {	
				     $_relation->{'id'}=$sql_res2["id"];
    				$res["DATA"]   = $_relation;
    				$res["STATUS"] = 201;
                }
            } else {
				$res["DATA"]   = "INVALID INPUT PARAMETERS";
				$res["STATUS"] = 406;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
	    
	    
		return $res;
	}
	
	public function delete_relation($_granted_roles_id, $_member_id, $_id) {
	    try {
            $sql_query = $this->sql->prepare("SELECT gr.id, ap.name, aa.db_name FROM auth.granted_roles gr left join `auth`.`permissions` ap on gr.permission_id=ap.id".
				                                " left join  `auth`.`applications` aa on aa.id=ap.app_ids WHERE gr.member_id=? and gr.id=?");
            $sql_query->execute(Array($_member_id, $_granted_roles_id));
            if ($sql_res = $sql_query->fetch()) {
				$sql_query = $this->sql->prepare("DELETE FROM ".$sql_res["db_name"].".granted_roles_".$sql_res["name"]." WHERE id=?;");
                $sql_query->execute(Array($_id));
				$res["DATA"]   = "";
				$res["STATUS"] = 204;
            } else {
				$res["DATA"]   = "INVALID INPUT PARAMETERS";
				$res["STATUS"] = 406;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
	    
		return $res;
	}
	
}	
	