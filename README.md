# mysqldb
The MySqlDB Class Library is a high level wrapper around the MySql.

## Install
Copy the files under `src/` to your program

OR

```bash
composer require websvc/php-mysql-db 1.0.2
```


## Usage

```php
use Mrpck\Mysqldb\MySqlDB;

// connecting to db
$db = new MySqlDB("localhost", "mydb");
if (!$db->db_connect_id) exit;

$sql = "SELECT id, name, surname, phone, email FROM `users` ";
$result = $db->sql_query($sql);		
$num_records = $result ? $db->get_num_rows($result) : 0;

// check if row inserted or not
if ($num_records > 0) 
{
	while ($row = $db->sql_fetchrow( $result )) 
	{
		echo "<br/>";
		print_r($row);
	}
	$db->sql_freeresult($result);
}

```
