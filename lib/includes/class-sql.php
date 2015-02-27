<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - SQL Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

class WPBackItUp_SQL {

	private $logger;
    private $connection;

	function __construct($logger) {
		try {
			$this->logger = $logger;
            $this->connection = $this->get_sqlconnection();

		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
   }

   function __destruct() {
       // Close the connection
       $this->connection->close() ;
   }

   public function mysqldump_export($sql_file_path,$with_mysqlpath=false) {

			$this->logger->log('(SQL.mysqldump_export) Export Database to: ' .$sql_file_path);

            $db_name = DB_NAME; 
            $db_user = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = $this->get_hostonly(DB_HOST);
        	$db_port = $this->get_portonly(DB_HOST);
			
			//This is to ensure that exec() is enabled on the server           
			if(exec('echo EXEC') == 'EXEC') {
				try {
                    $mysql_path='';
                    if ($with_mysqlpath)  {
                        $mysql_path = $this->get_mysql_path();
                        if ($mysql_path===false) return false;
                    }

                    $process = $mysql_path .'mysqldump';
		            $command = $process
		        	 . ' --host=' . $db_host;
					
					//Check for port
		        	 if (false!==$db_port){
		        	 	$command .=' --port=' . $db_port;
		        	 }	

		        	 $command .=
		        	   ' --user=' . $db_user
		        	 . ' --password=' . $db_pass	        	 
		        	 .=' ' . $db_name		        	 
		        	 . ' > "' . $sql_file_path .'"';

                    if (WPBACKITUP__DEBUG) {
                        $this->logger->log('(SQL.db_SQLDump)Execute command:' . $command);
                    }

            		exec($command,$output,$rtn_var);
		            $this->logger->log('(SQL.mysqldump_export)Execute output:');
		            $this->logger->log($output);
		            $this->logger->log('Return Value:' .$rtn_var);

		            //0 is success
		            if ($rtn_var>0){
                        $this->logger->log('(SQL.mysqldump_export) EXPORT FAILED return Value:' .$rtn_var);
		            	return false;
		            }

            		//Did the export work
            		clearstatcache();
	           		if (!file_exists($sql_file_path) || filesize($sql_file_path)<=0) {
	           			$this->logger->log('(SQL.mysqldump_export) EXPORT FAILED: Dump was empty or missing.');
	           			return false;
	           		}	
	           	} catch(Exception $e) {
                 	$this->logger->log('(SQL.mysqldump_export) EXPORT FAILED Exception: ' .$e);
                 	return false;
                }
            }
            else
            {
            	$this->logger->log('(SQL.mysqldump_export) EXPORT FAILED Exec() disabled.');
            	return false;
            }

            $this->logger->log('(SQL.mysqldump_export) SQL Dump SUCCESS.');
            return true;
	}


    public function manual_export($sql_file_path) {
		$this->logger->log_info(__METHOD__,'Manually Create SQL Backup File:'.$sql_file_path);
		
		$mysqli = $this->connection;
		$mysqli->set_charset('utf8');

		if (false===$mysqli) {
			$this->logger->log_error(__METHOD__,'No SQL Connection');
		 	return false;
		}

		// Script Header Information
		$return  = '';
		$return .= "-- ------------------------------------------------------\n";
		$return .= "-- ------------------------------------------------------\n";
		$return .= "--\n";
		$return .= "-- WP BackItUp Manual Database Backup \n";
		$return .= "--\n";
		$return .= '-- Created: ' . date("Y/m/d") . ' on ' . date("h:i") . "\n";
		$return .= "--\n";
		$return .= "-- Database : " . DB_NAME . "\n";
		$return .= "--\n";
		$return .= "-- ------------------------------------------------------\n";
		$return .= "-- ------------------------------------------------------\n";
		$return .= 'SET AUTOCOMMIT = 0 ;' ."\n" ;
		$return .= 'SET FOREIGN_KEY_CHECKS=0 ;' ."\n" ;
        $return .= "\n";
        $return .= '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;' ."\n" ;
        $return .= '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;' ."\n" ;
        $return .= '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;' ."\n" ;
        $return .= '/*!40101 SET NAMES utf8 */;' ."\n" ;

		$tables = array() ; 

		// Exploring what tables this database has
		$result = $mysqli->query('SHOW TABLES' ) ; 

		// Cycle through "$result" and put content into an array
		while ($row = $result->fetch_row()) {
			$tables[] = $row[0] ;
		}

		// Cycle through each  table
		foreach($tables as $table) {
			$this->logger->log($table);

			// Get content of each table
			$result = $mysqli->query('SELECT * FROM '. $table) ; 

			// Get number of fields (columns) of each table
			$num_fields = $mysqli->field_count  ;
			
			// Add table information
			$return .= "--\n" ;
			$return .= '-- Table structure for table `' . $table . '`' . "\n" ;
			$return .= "--\n" ;
			$return.= 'DROP TABLE  IF EXISTS `'.$table.'`;' . "\n" ; 
			
			// Get the table-shema
			$shema = $mysqli->query('SHOW CREATE TABLE '.$table) ;
			
			// Extract table shema 
			$tableshema = $shema->fetch_row() ; 
			
			// Append table-shema into code
			$return.= $tableshema[1].";" . "\n\n" ; 
			
			// Cycle through each table-row
			while($rowdata = $result->fetch_row()) { 
							
				$return.= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j<$num_fields; $j++){
				        $rowdata[$j] = addslashes($rowdata[$j]);
						$rowdata[$j] = str_replace("\n","\\n",$rowdata[$j]);

						if (isset($rowdata[$j])) {
							 $return.= '"'.$rowdata[$j].'"' ;
						 } else {
						 	if (is_null($rowdata[$j])) {
						 		$return.= 'NULL';//Dont think this is working but not causing issues	
						 	} else {
						 		$return.= '""';
						 	}
						  }
				  		
				        if ($j<($num_fields-1)) { $return.= ','; }
				}
				$return.= ");\n";
			} 
			$return .= "\n\n" ; 
		}

		$return .= 'SET FOREIGN_KEY_CHECKS = 1 ; '  . "\n" ; 
		$return .= 'COMMIT ; '  . "\n" ;
		$return .= 'SET AUTOCOMMIT = 1 ; ' . "\n"  ; 
		
		//save file
		$handle = fopen($sql_file_path,'w+');
		fwrite($handle,$return);
		fclose($handle);
		clearstatcache();

		//Did the export work
		if (!file_exists($sql_file_path) || filesize($sql_file_path)<=0) {
			$this->logger->log('(SQL.manual_export) Failure: SQL Export file was empty or didnt exist.');
			return false;
		}

		$this->logger->log('(SQL.manual_export)SQL Backup File Created:'.$sql_file_path);
	    return true;
	}

    public function run_sql_exec($sql_file,$with_mysqlpath=false) {
        $this->logger->log('(SQL.run_sql_exec)SQL Execute:' .$sql_file);

        //Is the backup sql file empty
        if (!file_exists($sql_file) || filesize($sql_file)<=0) {
            $this->logger->log('(SQL.run_sql_exec) Failure: SQL File was empty:' .$sql_file);
            return false;
        }

        //This is to ensure that exec() is enabled on the server
        if(exec('echo EXEC') != 'EXEC') {
            $this->logger->log('(SQL.run_sql_exec) Failure: Exec() disabled.');
            return false;
        }

        try {

            $mysql_path='';
            if ($with_mysqlpath)  {
                $mysql_path = $this->get_mysql_path();
                if ($mysql_path===false) return false;
            }

            $db_name = DB_NAME;
            $db_user = DB_USER;
            $db_pass = DB_PASSWORD;
            $db_host = $this->get_hostonly(DB_HOST);
            $db_port = $this->get_portonly(DB_HOST);

            $process = $mysql_path .'mysql';
            $command = $process
                . ' --host=' . $db_host;

            //Check for port
            if (false!==$db_port){
                $command .=' --port=' . $db_port;
            }

            $command .=
                ' --user=' . $db_user
                . ' --password=' . $db_pass
                . ' --database=' . $db_name
                . ' --execute="SOURCE ' . $sql_file .'"';

            if (WPBACKITUP__DEBUG) {
                $this->logger->log( '(SQL.db_run_sql)Execute command:' . $command );
            }

            //$output = shell_exec($command);
            exec($command,$output,$rtn_var);
            $this->logger->log('(SQL.run_sql_exec)Execute output:');
            $this->logger->log($output);
            $this->logger->log('Return Value:' .$rtn_var);

            //0 is success
            if ($rtn_var!=0){
                $this->logger->log('(SQL.run_sql_exec) An Error has occurred RTNVAL: ' .$rtn_var);
                return false;
            }

        }catch(Exception $e) {
            $this->logger->log('(SQL.run_sql_exec) Exception: ' .$e);
            return false;
        }

        //Success
        $this->logger->log('(SQL.run_sql_exec)SQL Executed successfully');
        return true;
    }

    public function run_sql_manual_OLD($sql_file) {
        $this->logger->log('(SQL.run_sql_manual)SQL Execute:' .$sql_file);

        //Is the backup sql file empty
        if (!file_exists($sql_file) || filesize($sql_file)<=0) {
            $this->logger->log('(SQL.run_sql_manual) Failure: SQL File was empty:' .$sql_file);
            return false;
        }

        $query = file_get_contents($sql_file);
        if (empty($query)) return false;

        try {

            $mysqli = $this->get_sqlconnection();
            $mysqli->set_charset('utf8');

            if (false===$mysqli) {
                return false;
            }

            $ctr=1;
            $this->logger->log('(SQL.run_sql_manual) 0: here ');
            if($mysqli->multi_query($query))
            {
                do {

                    $this->logger->log('(SQL.run_sql_manual) 1: ' .$ctr++);
                    /* store first result set */
                    if($resultSet = $mysqli->store_result())
                    {
                        $this->logger->log('(SQL.run_sql_manual) 2: ');

                        while($row = $resultSet->fetch_row())
                        {
                            $this->logger->log('(SQL.run_sql_manual) 3: ');
                        }
                        $resultSet->free();
                    }

                    if (!$mysqli->more_results()) break; //All done

                } while ($mysqli->next_result());

                $this->logger->log('(SQL.run_sql_manual) 4: ');
                $mysqli->close();
            }

        }catch(Exception $e) {
            $this->logger->log('(SQL.run_sql_manual) Exception: ' .$e);
            return false;
        }

        //Success
        $this->logger->log('(SQL.run_sql_manual)SQL Executed successfully:' .$sql_file);
        return true;
    }

    function run_sql_manual($sql_file_path, $delimiter = ';')
    {
        $this->logger->log('(SQL.run_sql_manual)SQL Execute:' .$sql_file_path);
        set_time_limit(0);

        //Is the backup sql file empty
        if (!file_exists($sql_file_path) || filesize($sql_file_path)<=0) {
            $this->logger->log('(SQL.run_sql_manual) Failure: SQL File was empty:' .$sql_file_path);
            return false;
        }

        try {
            if (is_file($sql_file_path) === true)
            {
                $sql_handle = fopen($sql_file_path, 'r');

                if (is_resource($sql_handle) === true)
                {
                    $query = array();

                    $mysqli = $this->get_sqlconnection();
                    $mysqli->set_charset('utf8');
//                    $mysqli->autocommit(FALSE);
//                    $mysqli->begin_transaction();

                    if (false===$mysqli) {
                        return false;
                    }

                    $error_count=0;
                    $total_query=0;
                    while (feof($sql_handle) === false)
                    {
                        $query[] = fgets($sql_handle);

                        if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1)
                        {
                            $query = trim(implode('', $query));

                            //Execute SQL statement
                            $total_query++;
                            if ($mysqli->query($query) === false) {
                                $error_count++;

                                $this->logger->log('(SQL.run_sql_manual)Total Queries Executed:' .$total_query);
                                $this->logger->log('(SQL.run_sql_manual)Query Errors:' .$error_count);

                                $this->logger->log('(SQL.run_sql_manual) SQL ERROR: ' . $query);

                                //$mysqli->rollback();
                                $mysqli->close();

                                fclose($sql_handle);
                                return false;
                            }
//                          else {
//                              $this->logger->log('(SQL.run_sql_manual) SUCCESS: ' . $query);
//                          }

                            while (ob_get_level() > 0)
                            {
                                ob_end_flush();
                            }

                            flush();
                        }

                        if (is_string($query) === true)
                        {
                            $query = array();
                        }
                    }

                    //$mysqli->commit();
                    $mysqli->close();

                    $this->logger->log('(SQL.run_sql_manual)SQL Executed successfully:' .$sql_file_path);
                    $this->logger->log('(SQL.run_sql_manual)Total Queries Executed:' .$total_query);
                    $this->logger->log('(SQL.run_sql_manual)Query Errors:' .$error_count);
                    return fclose($sql_handle);
                }
            }

        }catch(Exception $e) {
            $this->logger->log('(SQL.run_sql_manual) Exception: ' .$e);
            return false;
        }

        $this->logger->log('(SQL.run_sql_manual)SQL File could not be opened:' .$sql_file_path);
        return false;
    }

	private function get_sqlconnection() {
		$this->logger->log('(SQL.get_sqlconnection)Get SQL connection to database.');
		$db_name = DB_NAME; 
        $db_user = DB_USER;
        $db_pass = DB_PASSWORD; 
        $db_host = $this->get_hostonly(DB_HOST);
        $db_port = $this->get_portonly(DB_HOST);

        $this->logger->log('(SQL.get_sqlconnection)Host:' . $db_host);
        $this->logger->log('(SQL.get_sqlconnection)Port:' . $db_port);     
      
      	if (false===$db_port){
      		$mysqli = new mysqli($db_host , $db_user , $db_pass , $db_name);
      	}
        else {
			$mysqli = new mysqli($db_host , $db_user , $db_pass , $db_name,$db_port);
        }
		
		if ($mysqli->connect_errno) {
			$this->logger->log('(SQL.get_sqlconnection)Cannot connect to database.' . $mysqli->connect_error);
		   	return false;
		}
		return $mysqli;
    }

	private function get_hostonly($db_host) {
		//Check for port
		$host_array = explode(':',$db_host);
		if (is_array($host_array)){
			return $host_array[0];
		}
		return $db_host;
	}

	private function get_portonly($db_host) {
		//Check for port
		$host_array = explode(':',$db_host);
		if (is_array($host_array) && count($host_array)>1){
			return $host_array[1];
		}

		return false;
	}

    //Get SQL scalar value
    public function get_sql_scalar($sql){
        $value='';
        if ($result = mysqli_query($this->connection, $sql)) {
            while ($row = mysqli_fetch_row($result)) {
                $value = $row[0];
            }
            mysqli_free_result($result);
        }
        return $value;
    }

    //Run SQL command
    public function run_sql_command($sql){
        if(!mysqli_query($this->connection, $sql) ) {
            $this->logger->log('Error:SQL Command Failed:' .$sql);
            return false;
        }
        return true;
    }

    //Get path to MQSQL Bin
    private function get_mysql_path(){
        $base_directory = $this->get_sql_scalar('select @@basedir');
        if (!empty($base_directory)){
            $base_directory.='/bin/';
            $this->logger->log('MySQL install path found:' .$base_directory);
            return $base_directory;
        }
        $this->logger->log('MySQL install path NOT found');
        return false;
    }

    //This function is untested
//    function get_database_size($dbname) {
//        mysqli_select_db($dbname);
//        $result = mysqli_query("SHOW TABLE STATUS");
//        $dbsize = 0;
//        while($row = mysqli_fetch_array($result)) {
//            $dbsize += $row["Data_length"] + $row["Index_length"];
//        }
//        return $dbsize;
//    }

}