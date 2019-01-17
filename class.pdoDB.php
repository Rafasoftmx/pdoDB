<?php
include_once dirname(__FILE__)."/../app/Config.php";
include_once "class.logApp.php";

/*
            _       _____  ____                                
           | |     |  __ \|  _ \                               
  _ __   __| | ___ | |  | | |_) |                              
 | '_ \ / _` |/ _ \| |  | |  _ <                               
 | |_) | (_| | (_) | |__| | |_) |                              
 | .__/ \__,_|\___/|_____/|____/                               
 | |                                                           
 |_|___        __                 __ _     ___   ___  __  ___  
 |  __ \      / _|               / _| |   |__ \ / _ \/_ |/ _ \ 
 | |__) |__ _| |_ __ _ ___  ___ | |_| |_     ) | | | || | (_) |
 |  _  // _` |  _/ _` / __|/ _ \|  _| __|   / /| | | || |\__, |
 | | \ \ (_| | || (_| \__ \ (_) | | | |_   / /_| |_| || |  / / 
 |_|  \_\__,_|_| \__,_|___/\___/|_|  \__| |____|\___/ |_| /_/  
                                                               
                                                               
                                                                            

Simple class to handle a connection to MySQL DB and work around of the extension "PHP Data Objects" (PDO)
which provides a data access abstraction layer.

PDO benefits:
	-security
	-usability 
	-reusability

The class only serves to open the connection and maintain some useful variables to work around of PDO.

*/

class pdoDB
{

 	var  $db_type="mysql";
	var  $db_host= APP_BD_HOST; //Server host and port if any
	var  $db_name= APP_BD_NAME; //Database name
	var  $db_usr= APP_BD_USR; //User name
	var  $db_pss= APP_BD_PSS; //Password
	var  $db_dsn = ""; //Data Source Name(DSN)
	var  $db_driver_options = null; //A key=>value array of driver-specific connection options
	var  $pdo=null; //PDO database handler object
	var  $stmt=null; //It can be used to contain the object "PDOStatement" result of the function "PDO::prepare"
	var  $result=FALSE; //It can be used to contain result of "PDOStatement::execute" (Returns TRUE on success or FALSE on failure.)
	var  $count=0; //It can be used to contain result of "PDOStatement::rowCount" (number of rows affected by the last DELETE, INSERT, or UPDATE statement executed)
	var  $sql =""; //It can be used to contain an SQL statement


	/*
	Constructor
	create the DSN and establish the connection, assign the PDO database handler object to $this->pdo
	*/
	function __construct() 
	{
		
			$this->db_dsn = $this->db_type.":host=".$this->db_host."; dbname=".$this->db_name.";charset=utf8";
		
		try 
		{
			$this->pdo = new PDO($this->db_dsn,$this->db_usr, $this->db_pss,$this->db_driver_options);
		} catch (PDOException $e) 
		{
			logApp::error('Falló la conexión: ' . $e->getMessage());
		}
	}

	/*
	breakFree
	To close the connection, it is necessary to destroy the object that is ensuring that all references to it have to be removed;
	This can be done by assigning NULL to the variable that contains the object.
	*/
	function breakFree() 
	{		
		$this->stmt = null;
		$this->pdo = null;
	}
	
	/*
	directQuery
	Not recommended, it is vulnerable to SQL injection. but it can be used if you do not want to parameterize the query
	*/
	function directQuery($query) 
	{
		$this->stmt = $pdo->query($query);
	}
	
	/*
	preparedQuery
	
	*/
	function preparedQuery($query,$arrayParameters) 
	{
		$this->stmt = $pdo->prepare($query);
		$stmt->execute($arrayParameters);
	}
	
	/*
	getSingleValue
	name or 0-indexed column number
	*/
	function fetchSingleValue($column) 
	{
		
		if(is_string ($column))
		{
			for ($i = 0; $i < $stmt->columnCount(); $i++) {
				
				if($stmt->getColumnMeta($i) == $column)
				{
					$column = $i;
				}			
			}
			
			if(is_numeric ($column))
			{
				return $stmt->fetchColumn($column);// 0-indexed column number
			}
			
		}
		else
		{
			return $stmt->fetchColumn($column);// 0-indexed column number
		}
		
		return false; //fail		
	}


}// fin class
?>