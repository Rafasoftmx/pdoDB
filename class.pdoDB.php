<?php


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
                                                               
                                                               
                                                                            

* Simple class to handle a connection to MySQL DB and work around of the extension "PHP Data Objects" (PDO)
* which provides a data access abstraction layer.
* see examples at the end of this file

* make connection, queries and fixes sentences 'IN', 'LIKE' and 'L'IMIT' for work properly in PDO MySQL
* 
* 
* 
*  
* 
*/

class pdoDB
{

 	var  $db_type="mysql"; //database management system 
	var  $db_host= "localhost"; //Server host and port if any
	var  $db_name= "estepais"; //Database name
	var  $db_usr= "root"; //User name
	var  $db_pss= "rafasoft"; //Password
	var  $db_dsn = ""; //Data Source Name(DSN)
	var  $db_driver_options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]; //show errors: PDO::ERRMODE_EXCEPTION hidden errors: PDO::ERRMODE_SILENT
	 //A key=>value array of driver-specific connection options http://php.net/manual/es/pdo.setattribute.php
	var  $pdo=null; //PDO database handler object
	var  $stmt=null; //Is used to contain the object "PDOStatement" result of the function "PDO::prepare"


	/*
	* Constructor
	* create the DSN and establish the connection, assign the PDO database handler object to $this->pdo
	*/
	function __construct() 
	{	
		if($this->db_dsn == "")
		{
			$this->db_dsn = $this->db_type.":host=".$this->db_host."; dbname=".$this->db_name.";charset=utf8";	
		}		
		
		try 
		{
			$this->pdo = new PDO($this->db_dsn,$this->db_usr, $this->db_pss,$this->db_driver_options);
		} catch (PDOException $e) 
		{
			//logApp::error('Falló la conexión: ' . $e->getMessage());
			echo ('Falló la conexión: ' . $e->getMessage());
		}
	}	

	/*
	* breakFree
	* To close the connection, it is necessary to destroy the object that is ensuring that all references to it have to be removed;
	* This can be done by assigning NULL to the variable that contains the object.
	*/
	function breakFree() 
	{		
		$this->stmt = null;
		$this->pdo = null;
	}
	
	/*
	* directQuery
	* Not recommended, it is vulnerable to SQL injection. but it can be used if you do not want to parameterize the query
	* 
	* @param string $query
	*/
	function directQuery($query) 
	{
		$this->stmt = $this->pdo->query($query);				
	}
	
	/*
	* preparedQuery
	* Prepare and execute a query, fixes sentences IN, LIKE and LIMIT for work properly in PDO MySQL
	* 
	* the sentence has to be parameterized with:
	* Positional placeholders e.g. 'SELECT * FROM users WHERE email = ? AND status=?' or 
	* Named placeholders e.g. 'SELECT * FROM users WHERE email = :email AND status=:status'
	* 
	* The array of parameters depends on the type of query that is made:
	* Positional placeholder: [$email, $status]
	* Named placeholder: ['email' => $email, 'status' => $status]
	* 
	* @param string $query
	* @param array $arrayParameters
	*/
	function preparedQuery($query,$arrayParameters = array()) 
	{
		//some validations and query fixes for clasues IN and LIKE
		$this->fixParametersClauseIN($query,$arrayParameters);
		$this->fixParametersClauseLIKE($query,$arrayParameters);		

		$this->stmt = $this->pdo->prepare($query);
		
		// detect LIMIT clause and send parameters by bindValue
		if($this->fixParametersClauseLIMIT($query,$arrayParameters))
		{
			return $this->stmt->execute();
		}
		else
		{
			return $this->stmt->execute($arrayParameters);
		}		
		
	}
	
	
	/*
	* getColumnIndex
	* Search for the name of a column in the metadata from a previous query and return the 0-index of the position. if not found return false
	* 
	* @param string #columnName
	*/
	private function getColumnIndex($columnName) 
	{
		$columnIndex = false;
		
		for ($i = 0; $i < $this->stmt->columnCount() ; $i++) 
		{				
			if($this->stmt->getColumnMeta($i)["name"] == $columnName)
			{
				$columnIndex = $i;
			}
		}		

		return $columnIndex; 
	}
	
	/*
	* getSingleValue
	* It allows to fetch the value of a particular column, you can pass the name of the column or the position of the column 0-indexed
	* 
	* @param string $query
	* @param array $arrayParameters
	* @param mixed $column
	*/
	function getSingleValue($query,$arrayParameters = array(),$column = 0) 
	{
		$this->preparedQuery($query,$arrayParameters);
		
		if(is_string ($column))
		{
			$colIndex = $this->getColumnIndex($column);
			
			if($colIndex !== false)
			{
				return  $this->stmt->fetchColumn($colIndex);// 0-indexed column number
			}
			
		}
		else
		{
			return $this->stmt->fetchColumn($column);// 0-indexed column number
		}
		
		return false; //fail		
	}

	/*
	* getArrayColumn
	* It allows to fetch all values of a particular column in one-dimensional array, you can pass the name of the column or the position of the column 0-indexed
	* 
	* @param string $query
	* @param array $arrayParameters
	* @param mixed $column
	*/
	function getArrayColumn($query,$arrayParameters = array(),$column = 0) 
	{
		$this->preparedQuery($query,$arrayParameters);
		
		if(is_string ($column))
		{
			$colIndex = $this->getColumnIndex($column);
			
			if($colIndex !== false)
			{
				return $this->stmt->fetchAll(PDO::FETCH_COLUMN,$colIndex);// 0-indexed column number
			}
			
		}
		else
		{
			return $this->stmt->fetchAll(PDO::FETCH_COLUMN,$column);// 0-indexed column number
		}
		
		return false; //fail		
	}


	/*
	* getKeyValuePairs
	* It allows to get an array key-value pairs indexed by the first field. e.g. 'SELECT id, name FROM users' 
	* fetch mode requires the result set to contain extactly 2 columns
	* return array(  104 => 'John',  110 => 'Mike',  120 => 'Mary');
	* 
	* @param string $query
	* @param array $arrayParameters
	*
	*/
	function getKeyValuePairs($query,$arrayParameters = array())
	{
		$this->preparedQuery($query,$arrayParameters);		

		return $this->stmt->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	
	
	/*
	* getKeyValuePairs
	* Same as getKeyValuePairs, but getting not one column but full row  indexed by unique field
	* e.g. 'SELECT * FROM users' return:
	* array (
	*  104 => array (
	*	'name' => 'John',
	*	'car' => 'Toyota',
	*  ), ...
	* )
	*
	* 
	* @param string $query
	* @param array $arrayParameters
	*
	*/
	function getIndexedUnique($query,$arrayParameters = array())
	{
		$this->preparedQuery($query,$arrayParameters);		

		return $this->stmt->fetchAll(PDO::FETCH_UNIQUE);
	}
	
	/*
	* getGroupedByFirstField
	* will group rows into a nested array, where indexes will be unique values from the first columns. e.g. 'SELECT sex, name, car FROM users' return:
	* 	array (
	* 	  'male' => array (
	* 		0 => array (
	* 		  'name' => 'John',
	* 		  'car' => 'Toyota',
	* 		)
	* 	  ),
	* 	  'female' => array (
	* 		0 => array (
	* 		  'name' => 'Mary',
	* 		  'car' => 'Mazda',
	* 		)
	* 	  ),
	* 	)
	* 
	* @param string $query
	* @param array $arrayParameters
	*
	*/
	function getGroupedByFirstField($query,$arrayParameters = array())
	{
		$this->preparedQuery($query,$arrayParameters);		

		return $this->stmt->fetchAll(PDO::FETCH_GROUP);
	}
	
	

	/*
	* fixParametersClauseLIKE
	* It’s a method to adjust the the query if contain incorect LIKE clause: 
	* e.g. [LIKE '%?%'] or [LIKE %?%] or [LIKE '%:someting%'] or [LIKE %:someting% ]
	* must be ---> [LIKE ?] or [LIKE :someting]
	* 
	* @param string &$query	
	*/
	private function fixParametersClauseLIKE(&$query,&$arrayParameters)
	{
				
		if(preg_match('/[\s]+LIKE[\s]+[^\s]+[\s|$]?/i', $query) == 0) //if NO contain LIKE clause
		{			
			return false;
		}
				
		
		
		
		//get all LIKE Clauses eg. [ LIKE ? ] or [ LIKE :something ]
		$match_array= array();
		preg_match_all('/[\s]+LIKE[\s]+[^\s]+[\s|$]?/i', $query,$match_array);

		//adjust the the query if contain incorect LIKE clause
		foreach($match_array[0] as $matchLike)
		{
			$match = $matchLike;
			$fixMatch = $match;

			$fixMatch = str_replace("%'","",$fixMatch);
			$fixMatch = str_replace("'%","",$fixMatch);
			$fixMatch = str_replace("%","",$fixMatch);
			$query = str_replace($match,$fixMatch,$query);					
		}
			
		
		// if is associative array, it means is a query with Named placeholders
		if(is_array($arrayParameters) && array_diff_key($arrayParameters,array_keys(array_keys($arrayParameters))))
		{
			// search in all Parameters until find one in like clauses
			foreach ($arrayParameters as $Key => $Value) 
			{
				foreach ($match_array[0] as $likeClause) 
				{
					if(strpos($likeClause,$Key) !== false)// if Key is in LIKE clause
					{
						if(preg_match('/^[%][^%]+[%]$/i', $query) == 0) //if NO is string between '%'
						{
							$arrayParameters[$Key] = '%'.$Value.'%';// fix string
						}						
					}
				}
					
			}
			
		}
		else// if is normal arrary, it means is a query with Positional placeholders
		{

				//find position of every  '?' and verifi if is in 'LIKE clause'
				$pos =0;
				for ($i=0;$i<substr_count($query, '?');$i++)
				{
					$pos = strpos($query,'?',$pos+1);

					foreach ($match_array[0] as $likeClause)
					{
						
						$posLike =0;
						$posLikeEnd = 0;
						
						for ($j=0;$j<substr_count($query, $likeClause);$j++)
						{
							$posLike = strpos($query,$likeClause,$posLikeEnd+1);
							$posLikeEnd = $posLike+strlen($likeClause);

							if($pos >=$posLike && $pos <= $posLikeEnd)
							{	
								if(preg_match('/^[%][^%]+[%]$/i', $query) == 0) //if NO is string between '%'
								{
									$arrayParameters[$i] = '%'.$arrayParameters[$i].'%';// fix string
								}
							}

						}

					}
				}
			
		}
						
		
		return true;
	}
	
	
	
	
	/*
	* fixParametersClauseIN
	* It’s a method to adjust the parameters of the query if has a IN clause and array to fill the clause
	* e.g. Positional placeholders: "SELECT * FROM table WHERE foo=? AND column IN (?) AND bar=? AND baz=?" for example it become:
	* "SELECT * FROM table WHERE foo=? AND column IN (?,?,?) AND bar=? AND baz=?" note the change: IN (?,?,?)
	* 
	* In this case the $arrayParameters need to be an array and the part to be in the IN clause also is an array:
	* $arrayParameters: ["foo",[55,2,3],"bar","baz"];
	* 
	* another example in Named placeholders: "SELECT * FROM table WHERE foo=:foo AND column IN (:in) AND bar=:bar AND baz=:baz" for example it become:
	* "SELECT * FROM table WHERE foo=:foo AND column IN (:in0,:in1,:in2) AND bar=:bar AND baz=:baz" note the change: IN (:in0,:in1,:in2)
	* 
	* $arrayParameters: [":foo"=>0,":in"=>[9,8,7],":bar"=>1,":baz"=>2];
	* 
	* @param string &$query
	* @param array &$arrayParameters
	*/
	private function fixParametersClauseIN(&$query,&$arrayParameters)
	{
		if(preg_match('/IN(.)*\((.*)\)/i', $query) == 0) //if NOT contain IN clause
		{
			return false;		
		}
		
		$fixedParameters = array();
		
		
		// if is associative array, it means is a query with Named placeholders
		if(is_array($arrayParameters) && array_diff_key($arrayParameters,array_keys(array_keys($arrayParameters))))
		{
			//merge sub arrays in continius one
			foreach ($arrayParameters as $Key => $Value) 
			{			
				if(is_array ($Value))
				{	
					$strClauseIN = "";
					
					foreach ($Value as $SubKey => $SubValue) 
					{	
						$fixedParameters[$Key.$SubKey] = $SubValue;
						
						if($strClauseIN != ""){$strClauseIN .= ",";} //add ',' separator
						$strClauseIN .=$Key.$SubKey;// adds new keys
					}
					$query = str_replace($Key,$strClauseIN,$query);// replace ":key" to ":key0,:key1,:key2,..."
				}
				else
				{
					$fixedParameters[$Key] = $Value;
				}			
			}
			
		}
		else// if is normal arrary, it means is a query with Positional placeholders
		{
			foreach ($arrayParameters as $Key => $Value) 
			{	
				$strClauseIN = "";
				
				if(is_array ($Value))
				{
					$fixedParameters = array_merge($fixedParameters,$Value);
					
					$strClauseIN = str_repeat('?,', count($Value) - 1) . '?'; //makes "?,?,?,?.."
					
					//find position of the corect '?' to replace wiht "?,?,?,?"
					$pos =0;
					for ($i=0;$i<substr_count($query, '?');$i++)
					{
						$pos = strpos($query,'?',$pos+1);
							
							if($Key == $i) // we find the corect '?'
							{
								$str1 = substr($query, 0,$pos);
								$str2 = substr($query, $pos+1,strlen($query));
								$query = $str1.$strClauseIN.$str2; //concatenate for replace
							}
							
					}
				}
				else
				{
					array_push($fixedParameters, $Value);				
				}			
			}
		}

		$arrayParameters = $fixedParameters;
		
		return true;
	}
	
	
	/*
	* fixParametersClauseLIMIT
	* in LIMIT clause PDO treats every parameter as a string When emulation mode is on (by default)
	* this function do the trick to avoid this issue
	*  
	* 
	* @param string $query
	* @param array $arrayParameters
	*/
	private function fixParametersClauseLIMIT($query,$arrayParameters)
	{

		$output_array= array();
		if(preg_match('/LIMIT(\s)+.+,?(.*)?/i', $query,$output_array) == 1) //if contain LIMIT clause
		{
			// if is associative array, it means is a query with Named placeholders
			if(is_array($arrayParameters) && array_diff_key($arrayParameters,array_keys(array_keys($arrayParameters))))
			{
				$limitClause = $output_array[0];
				
				//send parameters by bindValue, detect if param is in LIMIT clause and send it as PDO::PARAM_INT
				foreach ($arrayParameters as $Key => $Value)
				{
					if(strpos ($limitClause,$Key) === false)
					{
						$this->stmt->bindValue($Key, $Value);	
					}
					else
					{
						$this->stmt->bindValue($Key, intval($Value),PDO::PARAM_INT);
					}					
				}

			}
			else// if is normal arrary, it means is a query with Positional placeholders
			{
				// if mach numbers of '?' and number of elements in arrayParameters
				if(substr_count($query,'?') == count($arrayParameters))
				{
					$limitClause = $output_array[0];
					$limitClausePosIni = strpos($query, $limitClause );
					$limitClausePosEnd = $limitClausePosIni+strlen($limitClause);
					
				    //find position of every '?' to find the '?' inside LIMIT clause
					$pos =0;
					for ($i=0;$i<substr_count($query, '?');$i++)
					{	
						$pos = strpos($query,'?',$pos+1);
						
						// if is '?' in LIMIT clause sen it as PDO::PARAM_INT
						if($pos>= $limitClausePosIni && $pos <= $limitClausePosEnd )
						{
							$this->stmt->bindValue($i+1, intval($arrayParameters[$i]),PDO::PARAM_INT);//$i+1 because 1-based index
						}
						else
						{
							$this->stmt->bindValue($i+1, $arrayParameters[$i]);//$i+1 because 1-based index
						}							
					}
				}
			}
			
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/*
	* formatIdentifier
	* PDO has no placeholder for identifiers like table and field names, so a developer must manually format them.
	* in mysql to format an identifier, follow these rules:
	* *Enclose identifier in backticks.
	* *Escape backticks inside by doubling them
	* this method do the trick to try eliminate the classical SQL injection in table and field names when we added them dynamically.
	* 
	* @param string $identifier	
	*/
	function formatIdentifier($identifier)
	{
		return "`".str_replace("`","``",$identifier)."`";	
	}
	
}//EOC

/*

										Examples
----------------------------------------------------------------------------------------


Running queries. PDO::query()
-----------------------------------
Not recommended, it is vulnerable to SQL injection. but it can be used if you do not want to parameterize the query


$db = new pdoDB(); //create object and make connection
$db->directQuery("SELECT * FROM usrs");

foreach ($db->stmt as $row)
{
    var_dump($row);	
}
$db->breakFree();// close close connection / destroy the object


Prepared statements. Protection from SQL injections. PDO::prepare() 
----------------------------------------------------

NOTE: only string and numeric literals can be bound by PDO prepared statements. an identifier, or a comma-separated list, or a part of a quoted string literal or whatever else arbitrary query part cannot be bound using a prepared statement.

Positional placeholders:
----------

$db = new pdoDB(); //create object and make connection
$query = "SELECT username FROM usrs where id = ? and grupo = ?";
$arrayParameters = [1,1]; //Indexed or Numeric Array
$db->preparedQuery($query,$arrayParameters); // build and execute query, fixes sentences IN, LIKE and LIMIT for work properly in PDO MySQL

foreach ($db->stmt as $row)
{
    var_dump($row);	
}
$db->breakFree();// close close connection / destroy the object

Named placeholders:
-----

$db = new pdoDB(); //create object and make connection
$query = "SELECT username FROM usrs where id = :id and grupo = :group";
$arrayParameters = [":id"=>1, ":group"=>1]; // Associative Array
$db->preparedQuery($query,$arrayParameters); // build and execute query, fixes sentences IN, LIKE and LIMIT for work properly in PDO MySQL

foreach ($db->stmt as $row)
{
    var_dump($row);	
}
$db->breakFree();// close close connection / destroy the object


Prepared statements. Multiple execution:
----------------------------------------
you can use prepared statements for the multiple execution of a prepared query

$db = new pdoDB(); //create object and make connection
$db->stmt = $db->pdo->prepare("UPDATE usrs SET log = :log  where id = :id");

for($i=1;$i<100;$i++)
{
	$db->stmt->execute([":log"=>"ok",":id"=>$i]);
}
$db->breakFree();// close close connection / destroy the object


Get the number of affected rows:
-----------------------------

$db = new pdoDB();
$db->preparedQuery("UPDATE usrs SET log = :log",[":log"=>"ok"]); 

echo $db->stmt->rowCount();


Getting data foreach()
-----------------------

$db = new pdoDB();
$db->preparedQuery("SELECT username FROM usrs");

foreach ($db->stmt as $row)
{
    echo $row['username'] . "<br/>";
}


Getting data fetch()
----------------------
PDO::FETCH_NUM returns enumerated array
PDO::FETCH_ASSOC returns associative array
PDO::FETCH_BOTH - both of the above
PDO::FETCH_OBJ returns object
PDO::FETCH_LAZY allows all three (numeric associative and object) methods without memory overhead.


$db = new pdoDB();

$db->preparedQuery("SELECT * FROM usrs");
while ($row = $db->stmt->fetch())
{
    echo $row['username'] . "<br>";
}
echo "<br><br>";


$db->preparedQuery("SELECT * FROM usrs WHERE id = :id", [":id"=>1]);
$row = $db->stmt->fetch(PDO::FETCH_NUM);
var_dump($row);
echo "<br><br>";

$db->preparedQuery("SELECT * FROM usrs WHERE id = :id", [":id"=>1]);
$row = $db->stmt->fetch(PDO::FETCH_ASSOC);
var_dump($row);
echo "<br><br>";


$db->preparedQuery("SELECT * FROM usrs WHERE id = :id", [":id"=>1]);
$row = $db->stmt->fetch(PDO::FETCH_BOTH);
var_dump($row);
echo "<br><br>";


$db->preparedQuery("SELECT * FROM usrs WHERE id = :id", [":id"=>1]);
$row = $db->stmt->fetch(PDO::FETCH_OBJ);
var_dump($row);
echo "<br><br>";

$db->preparedQuery("SELECT * FROM usrs WHERE id = :id", [":id"=>1]);
$row = $db->stmt->fetch(PDO::FETCH_LAZY);
var_dump($row);
echo "<br><br>";



Getting data fetchAll()
----------------------
Returns an array that contains all rows in the result set, This function should not be used if many rows has been selected "Many" means more than it is suitable to be shown on the average web page.

$db = new pdoDB();

$db->preparedQuery("SELECT * FROM usrs");

$result = $db->stmt->fetchAll(); // default PDO::FETCH_BOTH
var_dump($result);


Getting data fetchAll() PDO::FETCH_CLASS
----------------------------------------
Produce an array filled with objects the class we want, setting class properties from returned values.

Notes:
-properties are set before constructor call.
-for all undefined properties __set magic method will be called.
-if there is no __set method in the class, then new property will be created.
-private properties will be filled as well.

class usrTest{
	var $id =0;
	var $username="";
	var $email="";
	var $log="";
	var $data= array();
	
	//for all undefined properties __set magic method will be called
	//if there is no __set method in the class, then new property will be created
	function __set($name, $value)
    {
        echo "Setting '$name' a '$value'<br>";
        $this->data[$name] = $value;
    }
	
	
}

$db = new pdoDB();

$db->preparedQuery("SELECT * FROM usrs WHERE id = :id", [":id"=>1]);
$usr = $db->stmt->fetchAll(PDO::FETCH_CLASS, 'usrTest');
var_dump($usr);





getSingleValue()
----------------------------------------
A helper function that returns value of the singe field of returned row

// using colum index
$db = new pdoDB();
$columNumber =1; // 0 index value
$result = $db->getSingleValue("SELECT * FROM usrs WHERE id = :id", [":id"=>1],$columNumber);
var_dump($result);

// using colum name
$db = new pdoDB();
$columName ="username";
$result = $db->getSingleValue("SELECT * FROM usrs WHERE id = :id", [":id"=>1],$columName);
var_dump($result);



getArrayColumn()
--------------------
It allows to fetch all values of a particular column in one-dimensional array, you can pass the name of the column or the position of the column 0-indexed

$db = new pdoDB();
$columName ="username";
$result = $db->getArrayColumn("SELECT * FROM usrs ",null,$columName);
var_dump($result);



getKeyValuePairs()
-------------------
It allows to get an array key-value pairs indexed by the first field. 
fetch mode requires the result set to contain extactly 2 columns

$db = new pdoDB();
$result = $db->getKeyValuePairs("SELECT id,username FROM usrs");
var_dump($result);



getIndexedUnique()
------------------
Same as getKeyValuePairs, but getting not one column but full row  indexed by unique field

$db = new pdoDB();
$result = $db->getIndexedUnique("SELECT * FROM usrs");
var_dump($result);



getGroupedByFirstField()
-------------------------
will group rows into a nested array, where indexes will be unique values from the first columns. e.g. 'SELECT sex, name, car FROM users'

$db = new pdoDB();
$result = $db->getGroupedByFirstField("SELECT grupo,username,email FROM usrs");
var_dump($result);



Prepared statements and LIKE clause
-----------------------------------
In PDO library you need to be careful using placeholders with LIKE SQL clause. One would think that a query can be:

"SELECT * FROM table WHERE name LIKE '%?%'" but will produce an error. put attention on [ LIKE '%?%' ]

it must be more simple:

"SELECT * FROM table WHERE name LIKE ?" -> put attention on [ LIKE ? ]

and parameters must be:

$search = "%$search%"; -> betweens '%'



The function in this class preparedQuery() try to fix this error, so you can make your sql in other forms.
applies for Named placeholders and Positional placeholders.



-the esay way
-----

$db = new pdoDB(); 
$query = "SELECT username FROM usrs where id > ? and username Like ? or username Like ? and grupo > ?";
$arrayParameters = [0,'admin','David',0]; 

$db->preparedQuery($query,$arrayParameters); // internally fix the parameters to work ok

foreach ($db->stmt as $row)
{
    var_dump($row);	
}



-the PDO way
-----
$db = new pdoDB(); 
$query = "SELECT username FROM usrs where id > ? and username Like ? or username Like ? and grupo > ?";
$arrayParameters = [0,'%admin%','%David%',0]; 

$db->preparedQuery($query,$arrayParameters); // no internally fix is necessary, because the parameters are like PDO needs, betweens '%' and query is ok for PDO

foreach ($db->stmt as $row)
{
    var_dump($row);	
}




-the mistake form for PDO, but fixed by the library
-----
// in this case we use Named placeholders but is the same for Positional placeholders

$db = new pdoDB(); 
$query = "SELECT username FROM usrs where id > :id and username Like '%:username1%' or username Like %:username2% and grupo > :grupo";
$arrayParameters = [
	":id"=>0,
	":username1"=>'admin',
	":username2"=>'David',
	":grupo"=>0
]; 
$db->preparedQuery($query,$arrayParameters); // internally fix the parameters and the query to work ok.

foreach ($db->stmt as $row)
{
    var_dump($row);	
}



Note the Query: [Like '%:username1%'] and [Like %:username2%] if fixes to [Like :username1] and [Like :username2] internally.
Note the Parameters: ":username1"=>'admin',  if fixes to ":username1"=>'%admin%' internally.

if the sentence fail yu can tray use the the "PDO way" that is the correct and no fix is necessary





Prepared statements and IN clause
----------------------------------

in PDO is not possible to substitute an arbitrary query part with a placeholder for example, a string '1,2,3' will be bound as a string, resulting in:
SELECT * FROM table WHERE column IN ('1,2,3') that search one value -> '1,2,3'


by these reason in PDO we need to put something like this [ IN (?,?,?) ]


but using the function preparedQuery() in this class we only need to put one placeholder and put the parameter as array:



$db = new pdoDB(); 
$query = "SELECT username FROM usrs where id IN (:ids)";
$arrayParameters = [
	":ids"=>[1,50,30,45]
]; 
$db->preparedQuery($query,$arrayParameters); // internally fix the parameters and the query to work ok.

foreach ($db->stmt as $row)
{
    var_dump($row);	
}



in this example the query ends like:

SELECT username FROM usrs where id IN (:ids0,:ids1,:ids2,:ids3)

and the parameters change to:

$arrayParameters = [
	":ids0"=>1,
	":ids0"=>50,
	":ids0"=>30,
	":ids0"=>45
];



Prepared statements with LIMIT clause
------------------------------------

When in emulation mode (which is on by default) PDO treats every parameter as a string. As a result, the prepared [ LIMIT ?,?]  query becomes LIMIT '10', '10' which is invalid syntax in MySQL that causes query to fail.


but using the function preparedQuery() in this class we only need to put the placeholders and put the parameters array and it fixes this issue:

$db = new pdoDB(); 
$query = "SELECT username FROM usrs LIMIT :offset,:limit";
$arrayParameters = [
	":offset"=>5,
	":limit"=>10
]; 
$db->preparedQuery($query,$arrayParameters); // internally fix the parameters to work ok. also makes a cast if the parameter come as string

foreach ($db->stmt as $row)
{
    var_dump($row);	
}




Prepared statements table and field names
------------------------------------
PDO has no placeholder for identifiers (table and field names), a developer must manually format them.


For mysql to format an identifier, follow these 2 rules:

Enclose identifier in backticks.
Escape backticks inside by doubling them.


in this case we can use the function formatIdentifier() in this class to do the job when for some reason we insert dynamically the table and field names


$db = new pdoDB(); 

$table = $db->formatIdentifier("usrs");
$field1 = $db->formatIdentifier("username");
$field2 = $db->formatIdentifier("id");

$db->preparedQuery("SELECT ".$field1." FROM ".$table." where ".$field2." = :id",[":id"=>1]); 

foreach ($db->stmt as $row)
{
    var_dump($row);	
}



*/

?>