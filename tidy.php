<?php
$configs = parse_ini_file('config.ini');
$host = $configs['database'];
$user = $configs['user'];
$pass = $configs['password'];
$db = new PDO("mysql:host=$host;dbname=Analysis;charset=utf8", $user, $pass);
$result = $db->query('SELECT path,row_id from Top10');
while( $row = $result->fetch(PDO::FETCH_ASSOC))
{
    $id = $row['row_id'];
    $path = $row['path'];
    $pathArray = explode('/', $path);
    $tail = $pathArray[count($pathArray)-1];
    if( $tail == '' )
        $tail = $pathArray[count($pathArray)-2];

    if( $tail == "slideshow" ) {
        $db->exec("DELETE from Top10 where row_id=$id");
    }

    if( $tail != "" )
        $db->exec("UPDATE Top10 set pathTail = '$tail' where row_id=$id");
}

$db->exec("DELETE FROM Top10 where pathTail IS NULL");
