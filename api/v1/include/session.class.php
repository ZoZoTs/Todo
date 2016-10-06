<?php

class Session
{
	private $user_id         = 0;
	private $user_permissions = '';
	private $user_groups     = '';
	private $user_auth       = "FALSE";
		
	private $sql = null;
	
	/**
	 * method-role map
	 */
	protected $rolemap = Array(
								'GET' => 'R',
								'POST' => 'U',
								'PUT' => 'C',
								'DELETE' => 'D'
							);
	
	public function __construct($_sql, $_session_name){
		$this->sql = $_sql;
		session_name($_session_name);
		session_start();
	}

	public function get_user_id(){
		return $this->user_id;
	}
	
	public function get_user_permission(){
		return $this->user_permissions;
	}
	
	public function get_user_groups(){
		return $this->user_groups;
	}
	
	
	public function login($_user_name, $_user_password){
		global $_SESSION;
		
		
		try {
            $sql_query = $this->sql->prepare( "SELECT auth.set_new_session(?, PASSWORD(CONCAT(LCASE(?),?)), ?) as res");
            $sql_query->execute(Array($_user_name, $_user_name, $_user_password, session_id()));
            if ($sql_res = $sql_query->fetch()) {
   			    $sqlres = explode('|',$sql_res['res']);
   			    if ($sqlres[0] == 'OK')
    			{
    				$_SESSION["user_id"]    = $sqlres[1];
    				$_SESSION["groups"]     = $sqlres[3];
    				$_SESSION["permissions"]= explode(',', $sqlres[4]);
    				$_SESSION["Auth"]       = "TRUE";
    				$res = $this->personal_info(false);
    				$res["STATUS"] = 201;
    			}
    			else
    			{
    				$res["STATUS"] = 401;
    				header("WWW-Authenticate: Form-auth");
    				$res["DATA"] = Array("STATUS" => "UNAUTHORIZED" );
    				$_SESSION["Auth"] = "FALSE";
    			}
             }  else {
            	$res["STATUS"] = 401;
    			header("WWW-Authenticate: Form-auth");
    			$res["DATA"] = Array("STATUS" => "UNAUTHORIZED" );
    			$_SESSION["Auth"] = "FALSE"; 
             }
		} catch (PDOException $e) {
        	$res["STATUS"] = 401;
			header("WWW-Authenticate: Form-auth");
			$res["DATA"] = Array("STATUS" => "UNAUTHORIZED" );
			$_SESSION["Auth"] = "FALSE";
		}

		return $res;				
	}
	

	public function logout(){
		$this->check_session(true);
		global $_SESSION;
		
		if ($this->user_auth == "TRUE") {
			  $this->sql->query( 'DELETE FROM auth.sessions where session_id=\''.session_id().'\';');
		 }
		 // Unset all of the session variables. 
		$_SESSION = array();

		// If it's desired to kill the session, also delete the session cookie.
		// Note: This will destroy the session, and not just the session data!
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}

		// Finally, destroy the session.
		session_destroy();
		 
		$res["DATA"] = Array("STATUS" => "LOGEDOUT" );
		$res["STATUS"] = 202;
		
		return $res;
	}

	public function delete_session($_session_id, $_user_id){
		$this->check_session(true);
		global $_SESSION;
		$current_session_id = session_id();
		session_commit();
		if ( !$_session_id && $_user_id ) {
			
			try {
                $sql_query = $this->sql->prepare( "SELECT session_id FROM auth.sessions where user_id=?");
                $sql_query->execute(array($_user_id));
                if ($sql_res = $sql_query->fetch()) {
       			    $_session_id=$sql_res['session_id'];
                 } 
    		} catch (PDOException $e) {
            
    		}
			
		}
		if ( $_session_id ) {
			session_id($_session_id);			
			session_start();

			$this->sql->query( 'DELETE FROM auth.sessions where session_id=\''.session_id().'\';');
			$_SESSION = array();

			// Finally, destroy the session.
			session_destroy();
			session_commit();

			session_id($current_session_id);
			session_start();
			session_commit();		 

			$res["DATA"] = "";
			$res["STATUS"] = 204;
		} else {
			$res["DATA"] = "SESSION NOT FOUND";
			$res["STATUS"] = 404;

		}
		
		return $res;
	}
	
	
	public function check_session($_check_token){
		if (getallheaders()['token'] != session_id() && $_check_token) {
		    $this->user_auth = "FALSE";
		    return;
		}
		
		if (!$this->user_id) {
        	global $_SESSION;  
        	if ($_SESSION["Auth"] == "TRUE")
        	 {
        	  $this->user_id          = $_SESSION["user_id"];
        	  $this->user_permissions = $_SESSION["permissions"];
        	  $this->user_groups      = $_SESSION["groups"];
        	  $this->user_auth        = "TRUE";
        	 }
        	else
        	 {
        	 
        	 	try {
                    $sql_query = $this->sql->query('SELECT auth.check_session(\''.session_id().'\') as res;');
                    if ($sql_res = $sql_query->fetch()) {
           			   if ($sql_res['res'] != '') {
            				$sqlres = explode('|',$sql_res['res']);
            				$this->user_id          = $sqlres[0];
            				$this->user_groups      = $sqlres[1];
            				$this->user_permissions = explode(',', $sqlres[2]);
            				$this->user_auth        = "TRUE";
            				$_SESSION["user_id"]     = $this->user_id;
            				$_SESSION["permissions"] = $this->user_permissions;
            				$_SESSION["groups"]      = $this->user_groups;
            				$_SESSION["Auth"]        = $this->user_auth;
            			}
                     }
        		} catch (PDOException $e) {
                
        		}
        	 }
	    }
	}
	
	
	public function personal_info($_check_token){
		$this->check_session($_check_token);
		if ($this->user_auth == "TRUE") {
		  	try {
                $sql_query = $this->sql->query('SELECT name, full_name from auth.users_groups where id='.$this->user_id.';');
                if ($sql_res = $sql_query->fetch()) {
        		    $data["USERNAME"] = $sql_res['name'];
        			$data["FULL_NAME"] = $sql_res['full_name'];
        			$data["AUTH"] = "TRUE";
                 }
    		} catch (PDOException $e) {
            
    		}
    		$res["DATA"] = $data;
		    $res["STATUS"] = 200;
		} else {
			$data["AUTH"] = "FALSE";	
			$res["DATA"] = $data;
		    $res["STATUS"] = 401;
		}
		
		return $res;
	}
	
	public function get_sessions(){
		$this->check_session(true);
		
		try {
            $sql_query = $this->sql->query('SELECT session_id, start_time, name, full_name FROM auth.sessions LEFT JOIN auth.users_groups ON sessions.user_id = users_groups.id');
            if ($sql_res = $sql_query->fetchAll()) {
                $res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
             	$res["DATA"]   = "SESSIONS NOT FOUND";
				$res["STATUS"] = 404;       
            }
		} catch (PDOException $e) {
        	$res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}
	
		return $res;
	}
	
	public function check_permission($_function, $_method, $_id){
		$this->check_session(true);
		//return false;
		if ( $this->user_auth != "TRUE" ) {
			header("WWW-Authenticate: Form");
			return Array("DATA"=>Array("STATUS" => "UNAUTHORIZED"), "STATUS" => 401);
		//} elseif  (in_array($_function.":".$this->rolemap[$_method], $this->user_permissions)) {
		} elseif  (preg_grep("/$_function:\S*".$this->rolemap[$_method]."\S*:1/",$this->user_permissions)) {
		    return false;
		} elseif ( $_id != 0 ) {
	        $sql_query = $this->sql->prepare("SELECT db_name FROM `auth`.`applications` WHERE id = (SELECT app_ids FROM `auth`.`permissions` WHERE name=?)");
            $sql_query->execute(Array($_function));
            if ($sql_res = $sql_query->fetch()) {
            
                $sql= " select gr.id from auth.granted_roles gr LEFt JOIN `auth`.`permissions` ap on gr.permission_id=ap.id where gr.member_id in ( ".$this->user_groups." )".
            		    " and ap.name='$_function' and ".
            		    " gr.access_type like '%".$this->rolemap[$_method]."%'";
                if ( $_id != -1) {
                    $sql = "SELECT resource_id as id from ".$sql_res["db_name"].".granted_roles_".$_function." where granted_roles_id in ( ".$sql." ) and resource_id=?";  
                } else {
                    $_id  = "";
                }
        	    try {
            	    $sql_query = $this->sql->prepare($sql);
            	    $sql_query->execute(Array($_id));
            	    if ( $sql_res=$sql_query->fetch() ){
            		    return false;
            	    }
        	    } catch (PDOException $e) {
        	        return Array("STATUS"=> 500, "DATA" => $e->getMessage());
        	    }
            }
		}
		return Array("STATUS"=> 403, "DATA" => Array("PERMISSION" => $_function.":".$this->rolemap[$_method].":".$_id));
	}
	
	public function filter_allowed_ids($_function, $_data, $_id_field){
	    if  (preg_grep("/$_function:\S*:1/",$this->user_permissions)) {
	        return $_data;
	    } else {
	        $res=array();
	        
	        $sql_query = $this->sql->prepare("SELECT db_name FROM `auth`.`applications` WHERE id = (SELECT app_ids FROM `auth`.`permissions` WHERE name=?)");
            $sql_query->execute(Array($_function));
            if ($sql_res = $sql_query->fetch()) {
    	        $sql_query = $this->sql->query("SELECT resource_id FROM ".$sql_res["db_name"].".granted_roles_$_function WHERE granted_roles_id in (".
    	                                                "SELECT id FROM auth.granted_roles WHERE member_id in (".$this->user_groups.") AND permission_id=(SELECT id FROM `auth`.`permissions` WHERE name='$_function'))");
            	if ( $sql_res=$sql_query->fetchAll() ){
            	    foreach ($_data as $val) {
            	        foreach ($sql_res as $val2) {
                	        if ($val[$_id_field]==$val2['resource_id']) {
                	            array_push($res, $val);
                	            break;
                	        }
            	        }
            	    }
            	}
            }
        	return $res;
	    }
	    
	}
	
	public function check_permission2($_verb, $_method, $_perm_array){
		$this->check_session(true);
		//return false;
		if ( $this->user_auth != "TRUE" ) {
			header("WWW-Authenticate: Form");
			return Array("DATA"=>Array("STATUS" => "UNAUTHORIZED"), "STATUS" => 401);
		//} elseif  (in_array($_function.":".$this->rolemap[$_method], $this->user_permissions)) {
		} else {
		    if (!empty($_perm_array)) {
    		    $rule_available=false;
    		    foreach ($_perm_array as $value) {
    		        if ( $value[0] == $_verb && $value[1] == $_method && $value[2] == 'pre' ) {
    		            $rule_available=true;
    		            $function=$value[3];
    		            $method_to_check=$value[4];
    		            $id=$value[5]?$value[5]:($value[6]?-1:0);
    		            
    		            if  (!preg_grep("/$function:\S*".$method_to_check."\S*:1/",$this->user_permissions)) {
                       		if ( $id != 0 ) {
                    	        $sql_query = $this->sql->prepare("SELECT db_name FROM `auth`.`applications` WHERE id = (SELECT app_ids FROM `auth`.`permissions` WHERE name=?)");
                                $sql_query->execute(Array($function));
                                if ($sql_res = $sql_query->fetch()) {
                        	        $sql= " select id from auth.granted_roles where member_id in ( ".$this->user_groups." )".
                                		    " and permission_id=(SELECT id FROM `auth`.`permissions` WHERE name='$function') and ".
                                		    " access_type like '%".$method_to_check."%'";
                        	        if ( $id != -1) {
                        	            $sql = "SELECT resource_id as id from ".$sql_res["db_name"].".granted_roles_".$function." where granted_roles_id in ( ".$sql." ) and resource_id=?";  
                        	        } else {
                        	            $id  = "";
                        	        }
                        		    try {
                                	    $sql_query = $this->sql->prepare($sql);
                                	    $sql_query->execute(Array($id));
                                	    if ( ! $sql_res=$sql_query->fetch() ){
                                		   return Array("STATUS"=> 403, "DATA" => Array("PERMISSION" => $function.":".$method_to_check.":".$id));
                                	    }
                        		    } catch (PDOException $e) {
                        		        return Array("STATUS"=> 500, "DATA" => $e->getMessage());
                        		    }
                                } else {
                    		         return Array("STATUS"=> 500, "DATA" => "db_name not found in application db");
                    		    }
                    		} else {
                    		     return Array("STATUS"=> 403, "DATA" => Array("PERMISSION" => $function.":".$method_to_check.":".$id));
                    		}
                		}
    		        }
    		    }
    		    if ($rule_available) {
    		        return false;    
    		    } else {
    		        return Array("STATUS"=> 500, "DATA" => "Method in verb is not allowed: ".$_method." in ".$_verb);
    		    }
    		    
		    } else {
		        return Array("STATUS"=> 500, "DATA" => "Permission rule is empty");
		    }
		        
		}
		
	}
	
	public function filter_allowed_ids2($_verb, $_method, $_perm_array, $_data){
	    if ($_data["STATUS"]>=200 && $_data["STATUS"]<300) {
	        if (!empty($_perm_array)) {
	            $data=$_data["DATA"];
	            if ( !empty($data) ) {
    	            $rule_available=false;
        		    foreach ($_perm_array as $value) {
        		        if ( $value[0] == $_verb && $value[1] == $_method && $value[2] == 'post' ) {
        		            $res=array();
        		            $rule_available=true;
        		            $function=$value[3];
        		            $id_field=$value[4];
    	                   
    	                    if  (!preg_grep("/$function:\S*:1/",$this->user_permissions)) {
                    	        $sql_query = $this->sql->prepare("SELECT db_name FROM `auth`.`applications` WHERE id = (SELECT app_ids FROM `auth`.`permissions` WHERE name=?)");
                                $sql_query->execute(Array($function));
                                if ($sql_res = $sql_query->fetch()) {
                                    
                        	        $sql_query = $this->sql->query("SELECT resource_id FROM ".$sql_res["db_name"].".granted_roles_$function WHERE granted_roles_id in (".
                        	                                                "SELECT id FROM auth.granted_roles WHERE member_id in (".$this->user_groups.") AND permission_id=(SELECT id FROM `auth`.`permissions` WHERE name='$function'))");
                                	if ( $sql_res=$sql_query->fetchAll() ){
                                	    //check if recived data is an array of arrays
                                	    if (is_array($data[0])) {
                                    	    foreach ($data as $val) {
                                    	        foreach ($sql_res as $val2) {
                                        	        if ($val[$id_field]==$val2['resource_id']) {
                                        	            array_push($res, $val);
                                        	            break;
                                        	        }
                                    	        }
                                    	    }
                                	    } else {
                                	        foreach ($sql_res as $val2) {
                                    	        if ($data[$id_field]==$val2['resource_id']) {
                                    	            $res=$data;
                                    	            break;
                                    	        }
                                	        }
                                	    }
                                	}
            		                $data=$res;
                                }
                    	    }
    
        		        }
        		    }
        		    if ($rule_available) {
        		        $_data["DATA"]=$data;
        		    }
	            }
	        }
	    }
    	return $_data;
	}
}