<?php

class Profile
{	
	private $sql = null;
	
	public function __construct($_sql) {
		$this->sql = $_sql;
	}

	
	public function get($_user_id) {
		try {
            $sql_query = $this->sql->prepare("SELECT id, name, full_name from users_groups where id=?");
            $sql_query->execute(Array($_user_id));
            if ($sql_res = $sql_query->fetch()) {
				$res["DATA"]   = $sql_res;
				$res["STATUS"] = 200;
            } else {
				$res["DATA"]   = "USER NOT FOUND";
				$res["STATUS"] = 404;
            }
		} catch (PDOException $e) {
            $res["DATA"]   = $e->getMessage();
			$res["STATUS"] = 500;
		}

		return $res;
	}
		
	public function update($_user_id, $_user) {
		if ($_user_id != '') {
			try {
        		if ($_user->{'password'}) {
        			$sql_query = $this->sql->prepare("UPDATE users_groups SET  full_name=?, password=PASSWORD(CONCAT(LCASE(?),?)) WHERE id=?;");
                    $sql_query->execute(Array($_user->{'full_name'}, $_user->{'name'}, $_user->{'password'}, $_user_id));
        		} else {
        		    $sql_query = $this->sql->prepare("UPDATE users_groups SET  full_name=? WHERE id=?;");
                    $sql_query->execute(Array($_user->{'full_name'}, $_user_id));
        		}
                $res = $this->get($_user_id);
    		} catch (PDOException $e) {
                $res["DATA"]   = $e->getMessage();
    			$res["STATUS"] = 500;
    		}
		} else {
			$res["DATA"]   = "USER NOT FOUND";
			$res["STATUS"] = 404;		
		}
		return $res;
	}
}	
	