<?php
if (defined('DATABASE_TYPE') && DATABASE_TYPE == 'mysql') {
	include('dbmysql.php');
} elseif (defined('DATABASE_TYPE') && DATABASE_TYPE == 'mysqli') {
	include('dbmysqli.php');
}
