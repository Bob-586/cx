<?php
/**
 * @link http://www.imavex.com/php-pdo-wrapper-class/
    PHP 5, PDO Extension:
				Appropriate PDO Driver(s) - PDO_SQLITE, PDO_MYSQL, PDO_PGSQL
				Only MySQL, SQLite, and PostgreSQL database types are currently supported.
 */
namespace cx\database;
use PDO;
use PDOException;

class db extends PDO {
  public $ssp_bind;
  private $count = 0;
  private $error;
	private $sql;
	private $bind;
	private $error_callback_function;

	public function __construct($dsn, $user="", $passwd="") {
    $error_mode = \cx_configure::a_get('database', 'PDO_ERROR');
    if ( $error_mode === false ) {
      $error_mode = PDO::ERRMODE_EXCEPTION;
    }
    
    $options = array(
      PDO::ATTR_PERSISTENT => \cx_configure::a_get('database', 'PDO_PERSISTENT'), 
      PDO::ATTR_ERRMODE => $error_mode,
      PDO::ATTR_TIMEOUT => \cx_configure::a_get('database', 'PDO_TIMEOUT'),
    );    

    if (! defined('PDO_ASSCO')) {
      define('PDO_ASSCO', PDO::FETCH_ASSOC);
    }
   
		try {
			parent::__construct($dsn, $user, $passwd, $options);
		} catch (PDOException $e) {
      $error = 'Failed to connect to Database! ' . $e->getMessage();
      cx_email_error('The Database is DOWN!!!' . $error);
      if (\cx_configure::a_get('cx', 'live') === false) {
        echo "The Database is down!!! {$error}"; // Show error msg on dev
        exit;
      } else {
        cx_global_error_handler(); // Show error page on live
        exit;
      }
    } catch (Exception $e) {
      $error = 'Failed to connect to Database! ' . $e->getMessage();
      cx_email_error('The Database is DOWN!!!' . $error);
      if (\cx_configure::a_get('cx', 'live') === false) {
        echo "The Database is down!!! {$error}"; // Show error msg on dev
        exit;
      } else {
        cx_global_error_handler(); // Show error page on live
        exit;
      }
    }
	}

  /*
   * Called to by the model...
   */
  public function init_db() {
    $db_type = \cx_configure::a_get('database', 'DB_Type');
    if ($db_type == 'mysql') {
      $this->query("SET NAMES 'utf8';");
      $this->query("SET CHARACTER SET utf8;");
      $this->query("SET CHARACTER_SET_CONNECTION=utf8;");
      $this->query("SET SQL_MODE = '';");
      $this->query("SET time_zone = '+00:00';");
    }

    if ($db_type == 'pgsql') {
      $this->query("SET NAMES 'UTF8';");
      $this->query("SET TIME ZONE 'UTC';");
    }

  }

// ---Begining of public functions....:-----------------------------------------
	public function select($table, $options = array(), $bind="") {
		$fields = (isset($options['fields'])) ? $options['fields'] : '*';
    if (strrpos($table, "`") === false) {
      $table = "`{$table}`";
    }
    if (strrpos($fields, "*") === false && strrpos($fields, "`") === false) {
      $cf = '';
      $ex = explode(',', $fields);
      foreach($ex as $field) {
        $fld_tbl = trim(\cx\app\main_functions::get_db_table($field));
        $safe_tbl = (! empty($fld_tbl)) ? "`{$fld_tbl}`." : '';
        $cf .= $safe_tbl . '`' . trim(\cx\app\main_functions::get_db_column($field)) . '` , ';
      }
      $fields = rtrim($cf, ", ");
    }
    
    $other = (isset($options['distinct'])) ? 'DISTINCT ' : '';
    $other .= (isset($options['other'])) ? $options['other'] . ' ' : '';
    
    $sql = "SELECT " . $other . $fields . " FROM " . $table . "";
    if(isset($options['inner_join'])) {
			$sql .= " INNER JOIN " . $options['inner_join'];
    }
    if(isset($options['natural_join'])) {
			$sql .= " NATURAL JOIN " . $options['natural_join'];
    }
    if(isset($options['left_join'])) {
			$sql .= " LEFT JOIN " . $options['left_join'];
    }
    if(isset($options['on'])) {
			$sql .= " ON " . $options['on'];
    } 
    if(isset($options['where'])) {
			$sql .= " WHERE " . $options['where'];
    }
    if(isset($options['group_by'])) {
			$sql .= " GROUP BY " . $options['group_by'];
    }
    if(isset($options['having'])) {
			$sql .= " HAVING " . $options['having'];
    }
    if(isset($options['order_by'])) {
			$sql .= " ORDER BY " . $options['order_by'];
    }
    if(isset($options['limit'])) {
			$sql .= " LIMIT " . $options['limit'];
    }
    if(isset($options['pageinator_limit'])) {
			$sql .= $options['pageinator_limit'];
    }
    if(isset($options['procedure'])) {
			$sql .= " PROCEDURE " . $options['procedure'];
    }
		$sql .= ";";
    $fetch_mode = (isset($options['fetch'])) ? $options['fetch'] : 'all';
		return $this->run($sql, $bind, $fetch_mode);
	}

  public function insert($table, $info) {
		$fields = $this->filter($table, $info);
    
    if (strrpos($table, "`") === false) {
      $table = "`{$table}`";
    }
    
    $sql = "INSERT INTO {$table} (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
    $bind = array();
		foreach($fields as $field) {
			$bind[":$field"] = $info[$field];
    }
		return $this->run($sql, $bind);
	}

  public function update($table, $info, $where, $bind="", $unsafe = false) {
    $bind = $this->cleanup($bind);
    $fields = $this->filter($table, $info);
    
    if (strrpos($table, "`") === false) {
      $table = "`{$table}`";
    }
    
		$sql = "UPDATE {$table} SET ";
    $f = 0;
    
		foreach($fields as $key=>$value) {
      if($f > 0) {
				$sql .= ", ";
      }
      $f++;
     
      $value = trim($value);
      
      if (strrpos($value, "`") === false) {
        $cf = '`' . $value . '`';
      } else {
        $cf = $value;
      }
      if ($unsafe === true) {
        $my_sql_safe = $this->quote($info[$value]);
        $sql .= $cf . " = '{$my_sql_safe}'"; 
      } else {
        $sql .= $cf . " = :update_" . $value; 
        $bind[":update_$value"] = $info[$value];
      }
		}
    
    $sql .= " WHERE " . $where . ";";
    
		return $this->run($sql, $bind);
	}

  public function delete($table, $where, $bind="", $just_one=' LIMIT 1') {
    if (strrpos($table, "`") === false) {
      $table = "`{$table}`";
    }
    
    $sql = "DELETE FROM {$table} WHERE " . $where . $just_one . ";";
		$this->run($sql, $bind);
	}

// ---End of Public functions---------------------------------------------------  
	public function run($sql, $bind="", $fetch_mode="all") {
		$this->sql = trim($sql);
		
    $this->bind = $this->cleanup($bind);
    
		$this->error = "";
    try {
      
			$pdostmt = $this->prepare($this->sql);
    
      if (is_array($this->ssp_bind)) {
        \ssp_do_bindings($pdostmt, $this->ssp_bind);
      } 
      
      if (count($this->bind) > 0) {
        $exec = $pdostmt->execute($this->bind);
      } else {
        $exec = $pdostmt->execute();
      }
      
      if ($exec !== false) {
				if (preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql)) {
					if ($fetch_mode == 'all') {
            
            if (\cx\app\main_functions::found($this->sql, "SQL_CALC_FOUND_ROWS")) {
              $ret = $pdostmt->fetchAll(PDO::FETCH_ASSOC);
              $fsql = "SELECT FOUND_ROWS() AS totalRows";
              $totalRows = $this->query( $fsql )->fetch();
              $this->count = $totalRows[0];
              return $ret;
            } else {
              $this->count = $pdostmt->rowCount();
              return $pdostmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
          } else {
            return $pdostmt->fetch(PDO::FETCH_ASSOC);
          }
        } elseif(preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $this->sql)) {
      		return $pdostmt->rowCount();
        }  
			}	
		} catch (PDOException $e) {
			$this->error = $e->getMessage();	
			$this->debug();
			return false;
    } 
  }

  public function get_pdo_count() {
    return $this->count;
  }
  
  public function set_bindings($bind) {
    $this->ssp_bind = $bind;
  }

  private function filter($table, $info) {
		$driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if (strrpos($table, "`") === false) {
      $table = "`{$table}`";
    }
    
		if($driver == 'sqlite') {
      $sql = "PRAGMA table_info('{$table}');";
			$key = "name";
		} elseif($driver == 'mysql') {
      $sql = "DESCRIBE {$table};";
			$key = "Field";
		}	else {	
      $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}';";
			$key = "column_name";
		}	

		if(false !== ($list = $this->run($sql))) {
			$fields = array();
      foreach($list as $record) {
				$fields[] = $record[$key];
      }
			return array_values(array_intersect($fields, array_keys($info)));
		}
		return array();
	}

	private function cleanup($bind) {
		if(!is_array($bind)) {
			if(!empty($bind)) {
				$bind = array($bind);
      } else {
				$bind = array();
      }
		}
		return $bind;
	}
  
	  /*
   * Called to by the model...
   */
  public function error_callback_set($error_callback_function) {
		//Variable functions for won't work with language constructs such as echo and print, so these are replaced with print_r.
		if(in_array(safe_strtolower($error_callback_function), array("echo", "print"))) {
			$error_callback_function = "print_r";
    }  
		if(function_exists($error_callback_function)) {
			$this->error_callback_function = $error_callback_function;	
		}	
	}

  private function debug() {
    $error = array("Error" => $this->error);
    if(!empty($this->sql)) {
      $error["SQL Statement"] = $this->sql;
    }
    if(!empty($this->bind)) {
      $error["Bind Parameters"] = trim(print_r($this->bind, true));
    }  
    $backtrace = debug_backtrace();
    if(!empty($backtrace)) {
      foreach($backtrace as $info) {
        if($info["file"] != __FILE__)
          $error["Backtrace"] = $info["file"] . " at line " . $info["line"];	
      }		
    }

    $msg = "SQL Error\n" . str_repeat("-", 50);
      foreach($error as $key => $val)
        $msg .= "\n\n$key:\n$val";

      if (\cx_configure::a_get('cx', 'live') === false) {
        echo "<pre>";
        echo $msg;
        echo "</pre>";
        exit;
      } else {
        cx_email_error($msg);
        if(!empty($this->error_callback_function)) {
          $func = $this->error_callback_function;
          $func($msg);
          exit;
        }
      }
	}
  
  public function get_members($table) {
		$driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    if (strrpos($table, "`") === false) {
      $table = "`{$table}`";
    }
    
		if($driver == 'sqlite') {
      $sql = "PRAGMA table_info('{$table}');";
			$key = "name";
		}
		elseif($driver == 'mysql') {
      $sql = "DESCRIBE {$table};";
			$key = "Field";
		}
		else {	
      $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '{$table}';";
			$key = "column_name";
		}	

		if(false !== ($list = $this->run($sql))) {
			$fields = array();
      
      if (count($list) == 0) {
        return array();
      }
      
			foreach($list as $record) {
				$fields[] = $record[$key];
      }
			return $fields;
		}
		return array();
	}
  
  private function dd($m, $end=true) {
    var_dump($m);
    echo '<br><br>';
    echo '<pre>';
    print_r($m);
    echo '</pre>';
    echo '<br><br>';
    if ($end === true) {
      exit;
    }
  }
  
}	