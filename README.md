# pdoDB
###### Simple class to handle a connection to MySQL DB and work around of the extension "PHP Data Objects" (PDO)

**class benefits:**

- validations and query fixes for clasues IN, LIKE and LIMIT for work properly in PDO MySQL
- write less code
- you can access directly the PDO and PDOStatement instances for extra functions

## Properties

- db_driver_options: Options passed to PDO on conection, http://php.net/manual/es/pdo.setattribute.php
- pdo: PDO object, Represents a connection between PHP and a database server
- stmt: PDOStatement object, Represents a prepared statement and, after the statement is executed, an associated result set
**for connection**
- db_type: database management system (default mysql)
- db_host: Server host and port if any
- db_name: Database name
- db_usr: User name
- db_pss: Password
- db_dsn: Data Source Name(DSN), auto generated in base of above parameters or you can pass one directly


## Methods

- \__construct: create the DSN and establish the connection, assign the PDO database handler object to $this->pdo
- breakFree: Close the connection and destroys the object
- directQuery($query): executes the query sended as parameter, Not recommended, it is vulnerable to SQL injection. but it can be used if you do not want to parameterize the query
- preparedQuery($query,$arrayParameters): Prepare and execute a query, fixes sentences IN, LIKE and LIMIT for work properly in PDO MySQL. the sentence has to be parameterized with:
  - Positional placeholders e.g. 'SELECT * FROM users WHERE **email = ?** AND **status=?**'
  - Named placeholders e.g. 'SELECT * FROM users WHERE **email = :email** AND **status=:status**'
- getSingleValue($query,$arrayParameters,$column = 0): It allows to fetch the value of a particular column, you can pass the name of the column or the position of the column 0-indexed
- getArrayColumn($query,$arrayParameters,$column = 0): It allows to fetch all values of a particular column in one-dimensional array, you can pass the name of the column or the position of the column 0-indexed
- getKeyValuePairs($query,$arrayParameters): It allows to get an array key-value pairs indexed by the first field. e.g. 'SELECT id, name FROM users', fetch mode requires the result set to contain extactly 2 columns
- getIndexedUnique($query,$arrayParameters): Same as getKeyValuePairs, but getting not one column but full row  indexed by unique field
- getGroupedByFirstField($query,$arrayParameters): will group rows into a nested array, where indexes will be unique values from the first columns.
- formatIdentifier($identifier): PDO has no placeholder for identifiers like table and field names, so a developer must manually format them. this method do the trick.


## Examples and usage



## Running queries. PDO::query()

Not recommended, it is vulnerable to SQL injection. but it can be used if you do NOT want to parameterize the query

```
$db = new pdoDB(); //create object and make connection
$db->directQuery("SELECT * FROM usrs");

foreach ($db->stmt as $row)
{
    var_dump($row);
}
$db->breakFree();// close close connection / destroy the object
```

## Prepared statements. Protection from SQL injections. PDO::prepare()

NOTE: only string and numeric literals can be bound by PDO prepared statements. an identifier, or a comma-separated list, or a part of a quoted string literal or whatever else arbitrary query part cannot be bound using a prepared statement.

**Positional placeholders:**

```
$db = new pdoDB(); //create object and make connection
$query = "SELECT username FROM usrs where id = ? and grupo = ?";
$arrayParameters = [1,1]; //Indexed or Numeric Array
$db->preparedQuery($query,$arrayParameters); // build and execute query, fixes sentences IN, LIKE and LIMIT for work properly in PDO MySQL

foreach ($db->stmt as $row)
{
    var_dump($row);
}
$db->breakFree();// close close connection / destroy the object
```
**Named placeholders:**

```
$db = new pdoDB(); //create object and make connection
$query = "SELECT username FROM usrs where id = :id and grupo = :group";
$arrayParameters = [":id"=>1, ":group"=>1]; // Associative Array
$db->preparedQuery($query,$arrayParameters); // build and execute query, fixes sentences IN, LIKE and LIMIT for work properly in PDO MySQL

foreach ($db->stmt as $row)
{
    var_dump($row);
}
$db->breakFree();// close close connection / destroy the object
```

## Prepared statements. Multiple execution:

you can use prepared statements for the multiple execution of a prepared query
```
$db = new pdoDB(); //create object and make connection
$db->stmt = $db->pdo->prepare("UPDATE usrs SET log = :log  where id = :id");

for($i=1;$i<100;$i++)
{
	$db->stmt->execute([":log"=>"ok",":id"=>$i]);
}
$db->breakFree();// close close connection / destroy the object
```

## Get the number of affected rows:

```
$db = new pdoDB();
$db->preparedQuery("UPDATE usrs SET log = :log",[":log"=>"ok"]);

echo $db->stmt->rowCount();
```

## Getting data foreach()

```
$db = new pdoDB();
$db->preparedQuery("SELECT username FROM usrs");

foreach ($db->stmt as $row)
{
    echo $row['username'] . "<br/>";
}
```

## Getting data fetch()

- **PDO::FETCH_NUM** returns enumerated array
- **PDO::FETCH_ASSOC** returns associative array
- **PDO::FETCH_BOTH** both of the above
- **PDO::FETCH_OBJ** returns object
- **PDO::FETCH_LAZY** allows all three (numeric associative and object) methods without memory overhead.

```
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
```


## Getting data fetchAll()

Returns an array that contains all rows in the result set, This function should not be used if many rows has been selected "Many" means more than it is suitable to be shown on the average web page.
```
$db = new pdoDB();

$db->preparedQuery("SELECT * FROM usrs");

$result = $db->stmt->fetchAll(); // default PDO::FETCH_BOTH
var_dump($result);
```

## Getting data fetchAll() PDO::FETCH_CLASS

Produce an array filled with objects the class we want, setting class properties from returned values.

Notes:
- properties are set before constructor call.
- for all undefined properties \__set magic method will be called.
- if there is no \__set method in the class, then new property will be created.
- private properties will be filled as well.

```
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
```




## getSingleValue()

A helper function that returns value of the singe field of returned row

```
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
```


## getArrayColumn()

It allows to fetch all values of a particular column in one-dimensional array, you can pass the name of the column or the position of the column 0-indexed

```
$db = new pdoDB();
$columName ="username";
$result = $db->getArrayColumn("SELECT * FROM usrs ",null,$columName);
var_dump($result);
```


## getKeyValuePairs()

It allows to get an array key-value pairs indexed by the first field.
fetch mode requires the result set to contain extactly 2 columns

```
$db = new pdoDB();
$result = $db->getKeyValuePairs("SELECT id,username FROM usrs");
var_dump($result);
```


## getIndexedUnique()

Same as getKeyValuePairs, but getting not one column but full row  indexed by unique field
```
$db = new pdoDB();
$result = $db->getIndexedUnique("SELECT * FROM usrs");
var_dump($result);
```


## getGroupedByFirstField()

will group rows into a nested array, where indexes will be unique values from the first columns. e.g. 'SELECT sex, name, car FROM users'

```
$db = new pdoDB();
$result = $db->getIndexedUnique("SELECT grupo,username,email FROM usrs");
var_dump($result);
```


## Prepared statements and LIKE clause

In PDO library you need to be careful using placeholders with LIKE SQL clause. One would think that a query can be:

`"SELECT * FROM table WHERE name LIKE '%?%'"` but will produce an error.
put attention on [ **LIKE '%?%'** ]

it must be more simple:

''"SELECT * FROM table WHERE name LIKE ?"'  put attention on [ **LIKE ?** ]

and parameters must be:

`$search = "%$search%";` -> betweens **'%'**



The function in this class **preparedQuery()** try to fix this error, so you can make your sql in other forms.
applies for **Named placeholders** and **Positional placeholders**.



### the easy way

```
$db = new pdoDB();
$query = "SELECT username FROM usrs where id > ? and username Like ? or username Like ? and grupo > ?";
$arrayParameters = [0,'admin','David',0];

$db->preparedQuery($query,$arrayParameters); // internally fix the parameters to work ok

foreach ($db->stmt as $row)
{
    var_dump($row);
}
```


### the PDO way

```
$db = new pdoDB();
$query = "SELECT username FROM usrs where id > ? and username Like ? or username Like ? and grupo > ?";
$arrayParameters = [0,'%admin%','%David%',0];

$db->preparedQuery($query,$arrayParameters); // no internally fix is necessary, because the parameters are like PDO needs, betweens '%' and query is ok for PDO

foreach ($db->stmt as $row)
{
    var_dump($row);
}
```



### the mistake form for PDO, but fixed by the class

```
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
```


Note the Query: [**Like '%:username1%'**] and [**Like %:username2%**] if fixes to [**Like :username1**] and [**Like :username2**] internally.
Note the Parameters: **":username1"=>'admin'**,  if fixes to **":username1"=>'%admin%'** internally.

if the sentence fail yu can tray use the the **"PDO way"** that is the correct and no fix is necessary




## Prepared statements and IN clause


in PDO is not possible to substitute an arbitrary query part with a placeholder for example, a **string '1,2,3'** will be bound as a string, resulting in:
`SELECT * FROM table WHERE column IN ('1,2,3')` that search only one value -> **'1,2,3'**


by these reason in PDO we need to put something like this [ **IN (?,?,?)** ]


but using the function **preparedQuery()** in this class we only need to put one placeholder and put the parameter as array:


```
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
```

in this example the query ends like:

`SELECT username FROM usrs where id IN (:ids0,:ids1,:ids2,:ids3)`

and the parameters change to:

```
$arrayParameters = [
	":ids0"=>1,
	":ids1"=>50,
	":ids2"=>30,
	":ids3"=>45
];
```


## Prepared statements with LIMIT clause


When in emulation mode (which is on by default) PDO treats every parameter as a string. As a result, the prepared [ **LIMIT ?,?** ]  query becomes **LIMIT '10', '10'** which is invalid syntax in MySQL that causes query to fail.


but using the function **preparedQuery()** in this class we only need to put the placeholders and put the parameters in the array and it fixes this issue:

```
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
```



## Prepared statements table and field names

PDO has no placeholder for identifiers (table and field names), a developer must manually format them.


For mysql to format an identifier, follow these 2 rules:
- Enclose identifier in backticks.
- Escape backticks inside by doubling them.


We can use the function **formatIdentifier()** in this class to do the job when for some reason we insert dynamically the table and field names

```
$db = new pdoDB();

$table = $db->formatIdentifier("usrs");
$field1 = $db->formatIdentifier("username");
$field2 = $db->formatIdentifier("id");

$db->preparedQuery("SELECT ".$field1." FROM ".$table." where ".$field2." = :id",[":id"=>1]);

foreach ($db->stmt as $row)
{
    var_dump($row);
}
```
