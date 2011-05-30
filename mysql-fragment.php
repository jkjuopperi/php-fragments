<?php

define("DB_SERVER", "foo");
define("DB_NAME", "bar");
define("DB_USER", "baz");
define("DB_PASSWORD", "quux");

/* Connect to database */
$connection = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
if ($connection->connect_error) {
	return "FAIL: Couldn't connect to database: " + $connection->connect_error;
}

/* "global" error string for query() */
$error = '';

/* Query mysql e.g.
 * $login = "foo";
 * $result_array = query("SELECT * FROM users WHERE login = ?", $login);
 * print $result_array[0]['password']
 *
 * Will return array of rows indexed by number,
 * which are arrays of columns indexed by column name.
 * Returns FALSE on failure and leaves error message in $error.
 */
function query($query, $params) {
	global $connection, $error;
	$error = '';

	// Prepare a statement
	$stmt = $connection->prepare($query);
	if ($stmt == false) {
		$error = "Failed to prepare statement: " . $connection->error;
		return false;
	}

	/*
	 * Bind args
	 * (intermediate "container" required because mysqli wants pass by reference)
	 */
	if (func_num_args()>1) {
		$param_arr = array('');
		$container = array();
		for ($i = 1; $i<func_num_args(); $i++) {
			$param_arr[0] .= 's';
			$container[$i] = func_get_arg($i);
			$param_arr[$i] = &$container[$i];
		}
		/* Call a varargs thingy */
		if (call_user_func_array(array($stmt, 'bind_param'), $param_arr) == false) {
			$error = "Failed to bind SQL parameters: " . $stmt->error;
			return false;
		}
	}

	// Execute query
	if ($stmt->execute() == false) {
		$error = "SQL query failed: " . $stmt->error;
		return false;
	}

	// Return result as array
	$result = $stmt->get_result();
	if ($result) {
		$arr = $result->fetch_all(MYSQL_ASSOC);
		if (count($arr) == 0) {
			return true;
		}
		return $arr;
	}

	return true;
}

?>