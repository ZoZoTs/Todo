<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

require_once 'include/API.class.php';
require_once 'include/session.class.php';
require_once 'include/log.class.php';
require_once 'include/pdo_sql_parameters.php';

class Relations {
    private $p_sql = null;
    
    public function __construct($_sql) {
		$this->p_sql = $_sql;
    }
    
    public function action($_action, $_name, $_id, $_my_creator="") {
       global $SESSION;
       //return "";
       $log = new LOG($this->p_sql);
        try {
            $sql_query = $this->p_sql->prepare("SELECT `$_action` FROM `auth`.`permissions` WHERE name=?");
            $sql_query->execute(Array($_name));
            if ($sql_res = $sql_query->fetch()) {
                $acts = explode(",",$sql_res[$_action]);
                if ($_action == "add") {
                    if ($_my_creator) {
                        if (in_array($_my_creator, explode(',',$SESSION->get_user_groups()))) { 
                            foreach ($acts as $tbname) {
                                try {
                	               $sql_query = $this->p_sql->prepare("INSERT INTO granted_roles_$tbname (granted_roles_id, resource_id) SELECT gr.id as granted_roles_id, ? as resource_id FROM auth.granted_roles gr WHERE member_id = ? and function_name='$tbname' and (access_type like '%U%' or access_type like '%D%' or access_type like '%R%') ");
                	               $sql_query->execute(Array($_id, $_my_creator));
                	            } catch (PDOException $e) {
                	               $log->put('relations', $SESSION->get_user_id(), $_action.':'.$tbname, 'PUT', $e->getMessage(), 'C'); 
                	            }   
                            }
                        } else {
                            $log->put('security', $SESSION->get_user_id(), $_action.':'.$_name, 'PUT', 'mycreator not in user_groups: '.$_my_creator, 'C'); 
                        }
                    } else {
                        foreach( $acts as $key => $name )
        			        {
        			            $val="insert into granted_roles_%name% (granted_roles_id, resource_id) select gr.id as granted_roles_id, '%id%' as resource_id from granted_roles_%name% grr right join auth.granted_roles gr on grr.granted_roles_id=gr.id where ( member_id=%user_id%  or  member_id in (%user_groups%) ) and function_name='%name%' and (access_type like '%U%' or access_type like '%D%') and gr.to_all=0 order by if(LOCATE('U', gr.access_type)>0,1,2), grr.resource_id limit 1";
        			            $val=str_replace("%id%", $_id, $val);
        			            $val=str_replace("%name%", $name, $val);
        			            $val=str_replace("%user_id%", $SESSION->get_user_id(), $val);
        			            $groups=$SESSION->get_user_groups();
        			            if (!$groups){
        			                $groups=-1;
        			            }
        			            $val=str_replace("%user_groups%", $groups, $val);
        			            try {
        			                $sql_res = $this->p_sql->query($val);
        			            } catch (PDOException $e) {
        			               $log->put('relations', $SESSION->get_user_id(), $_action.':'.$_name, 'PUT', $e->getMessage(), 'C'); 
        			            }
        			                
        			        }
                    }
                    if ( $_id != '' ) {
                	   $log->put('relations', $SESSION->get_user_id(), $_action.':'.$_name, 'PUT', $_id, 'C'); 
                	}
                } // elseif ($_action == "del") {}
            }
        } catch (PDOException $e) {
           $log->put('relations', $SESSION->get_user_id(), $_action.':'.$_name, 'PUT', $e->getMessage(), 'C'); 
        }   
    }

}
 
class MyAPI extends API
{
	private $sql=null;
	private $session=null;
	private $log=null;
	
    public function __construct($_request, $_sql, $_session) {
        parent::__construct($_request);
		$this->sql = $_sql;
		$this->session = $_session;
		$this->log = new LOG($_sql);
    }
    
     protected function check_input_data( $_verb, $_method, $_list_of_parameters, $_json) {
    	if (!empty($_json)) {
        	if (!empty($_list_of_parameters)) {
    		    $rule_available=false;
    		    foreach ($_list_of_parameters as $value) {
    		        if ( $value[0] == $_verb && $value[1] == $_method) {
    		            $rule_available=true;
    		            foreach ($value[2] as $item) {
    		                if (!array_key_exists($item, $_json)) {
    		                    return Array("DATA" => "No '".$item."' parameter is given", "STATUS" => 406);
    		                }
    		            }
    		        }
    		    }
    		    if ($rule_available) {
    		        return false;    
    		    } else {
    		        return Array("STATUS"=> 500, "DATA" => "Parameters list not given for method: '".$_method."' in verb: '".$_verb."'");
    		    }
    		    
    	    } else {
    	        return Array("STATUS"=> 500, "DATA" => "Parameters list is empty");
    	    }
    	}
    }
    

    protected function login() {
        if ($this->method == 'GET' ) {
			return $this->session->personal_info(true);
        } elseif ($this->method == 'PUT' ) {
			$json=json_decode($this->file);
			return $this->session->login($json->{'login'}, $json->{'pass'});
		} elseif ($this->method == 'DELETE' ) {
			return $this->session->logout();
        } else {
            return Array("DATA" => "Only accepts GET, PUT, DELETE requests", "STATUS" => 405);
        }
     }

    protected function profile() {
         $list_of_parameters = array ( array ('', 'GET', array()),
                                       array ('', 'POST', array('full_name', 'name'))
                                    );
        if ( $perm = $this->check_input_data($this->verb, $this->method, $list_of_parameters, $json) ) { return $perm; }
        
		$perms_array  = array( array(''         , 'GET',     'pre' , __FUNCTION__,   'R', 0,         false),
	                           array(''         , 'POST',    'pre' , __FUNCTION__,   'U', 0,         false));
    	if ( $perm = $this->session->check_permission2($this->verb, $this->method, $perms_array) ) { return $perm; }


		$this->log->put('security', $this->session->get_user_id(), $this->url, $this->method, $this->file, 'CUD','password,password2');
		
		require_once 'include/profile.class.php';
		$profile = new Profile($this->sql);
		if ($this->method == 'GET' ) {
			return $profile->get( $this->session->get_user_id());
		} elseif ($this->method == 'POST' ) {
			return $profile->update($this->session->get_user_id(), json_decode($this->file));
		} else {
			return Array("DATA" => "Only accepts GET, PUT requests", "STATUS" => 405);
		}
     }

protected function permissions() {
	    $json=json_decode($this->file);
	    
	    $list_of_parameters = array (array ('getmycreatorsfor', 'GET', array()));
        if ( $perm = $this->check_input_data($this->verb, $this->method, $list_of_parameters, $json) ) { return $perm; }
	    
	    $perms_array  = array(array('getmycreatorsfor'   , 'GET',     'pre' , 'profile',   'R', $this->args[0],         true ));
	    if ( $perm = $this->session->check_permission2($this->verb, $this->method, $perms_array) ) { return $perm; }
	    
	    $this->log->put('security', $this->session->get_user_id(), $this->url, $this->method, $this->file, 'CUD');
        
        $res = $this->permissions_body();
		
	    $res = $this->session->filter_allowed_ids2($this->verb, $this->method, $perms_array, $res);
    	
	    
	    return $res;
     }
    protected function permissions_body() {
		require_once 'include/permission_control.class.php';
		$permissions = new Permission_control($this->sql);
    	if ($this->verb=="getmycreatorsfor"){
    	        if ($this->method == 'GET' ) {
    	            return $permissions->get_mygroupsfor($this->args[0],'C', $this->session->get_user_groups());
    	        }
	    } 
     }

    protected function todo() {
       $json=json_decode($this->file);
	   
       $list_of_parameters = array (  array ('', 'GET', array()),
                                      array ('', 'PUT',  array('name','json_body')),
                                      array ('', 'POST', array('name','json_body')),
                                      array ('', 'DELETE', array()),
                                    );
        if ( $perm = $this->check_input_data($this->verb, $this->method, $list_of_parameters, $json) ) { return $perm; }
	   
	  
	    $perms_array  = array( array(''         , 'GET',     'pre' , __FUNCTION__,   'R', $this->args[0],         true ),
	                           array(''         , 'GET',     'post', __FUNCTION__,   'id' ),
	                           
	                           array(''         , 'PUT',     'pre' , __FUNCTION__,   'C', $this->args[0],         true  ),

	                           array(''         , 'POST',    'pre' , __FUNCTION__,   'U', $this->args[0],         false  ),

	                           array(''         , 'DELETE',  'pre' , __FUNCTION__,   'D', $this->args[0],         false  ),
	                       );
	    

    	if ( $perm = $this->session->check_permission2($this->verb, $this->method, $perms_array) ) { return $perm; }
	    
	    $this->log->put('todo', $this->session->get_user_id(), $this->url, $this->method, $this->file, 'CUD');
        
        $res=$this->todo_body();
		
	    $res = $this->session->filter_allowed_ids2($this->verb, $this->method, $perms_array, $res);
    	
	    
	    return $res;	
        
    }
    protected function todo_body() {
        $json=json_decode($this->file);
		require_once 'include/sql_model.class.php';
		$model = new SQL_model($this->sql, 'todo');
		
        $lookForString = '"done":false';
        
		if ($this->args[0]) {
			if ($this->method == 'GET' ) {
				return $model->first("SELECT id, name, json_body, Round((LENGTH(json_body) - LENGTH(REPLACE(json_body, '$lookForString', ''))) / LENGTH('$lookForString')) as undone_count FROM todo WHERE id=?", Array($this->args[0]));
			} elseif ($this->method == 'PUT' ) {
			    return $model->add('INSERT INTO todo (name, json_body) VALUES (?,?);', Array($json->{'name'}, $json->{'json_body'}),
				                    'SELECT * FROM todo WHERE name=? and json_body=?;', Array($json->{'name'}, $json->{'json_body'}),$json);
			} elseif ($this->method == 'POST' ) {
				return $model->update('UPDATE todo SET name=?, json_body=? WHERE id=?;',
				                        Array($json->{'name'}, $json->{'json_body'}, $this->args[0]),
				                        $json);
			} elseif ($this->method == 'DELETE' ) {
				return $model->delete('DELETE FROM todo WHERE id=?;',Array($this->args[0]));
			
			} else {
				return Array("DATA" => "Only accepts GET, PUT, POST, DELETE requests", "STATUS" => 405);
			}
		} else {
		    if ($this->method == 'GET' ) {
				return $model->get("SELECT id, name, Round((LENGTH(json_body) - LENGTH(REPLACE(json_body, '$lookForString', ''))) / LENGTH('$lookForString')) as undone_count FROM todo", null);
			} elseif ($this->method == 'PUT' ) {
				return $model->add('INSERT INTO todo (name, json_body) VALUES (?,?);', Array($json->{'name'}, $json->{'json_body'}),
				                    'SELECT * FROM todo WHERE name=? and json_body=?;', Array($json->{'name'}, $json->{'json_body'}),$json);
			} else {
				return Array("DATA" => "Only accepts GET, PUT requests", "STATUS" => 405);
			}
		}
    
    }
    
}
 

try {
	$PDO_SQL = new PDO($sql_conf['type'].':host='.$sql_conf['host'].';dbname='.$sql_conf['database'].';charset=utf8', $sql_conf['username'], $sql_conf['password'], array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode = 'TRADITIONAL'"));
	$SESSION = new Session($PDO_SQL, "ZoZoLand_session_id");
	$API = new MyAPI($_REQUEST['request'], $PDO_SQL, $SESSION);
	echo $API->processAPI();
	$PDO_SQL = null;
} catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
	echo json_encode(Array('error' => $e->getMessage()));
}