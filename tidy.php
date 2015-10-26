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

$result = $db->query("
    SELECT m.reference_id, s.headline, s.teaser, t.row_id from Analysis.Top10 t
    inner join Main.Mapper m on m.path like CONCAT('%', t.pathTail, '%')
    inner join Main.StoryMain s on m.reference_id = s.story_id 
    WHERE m.site_id = 1 AND t.story_id = 0
    ");
while( $row = $result->fetch(PDO::FETCH_ASSOC))
{
    $story_id = $row['reference_id'];
    $headline = $row['headline'];
    $teaser = $row['teaser'];
    $id = $row['row_id'];

    echo "$story_id: $headline\n";

    $statement = $db->prepare("UPDATE Top10 set story_id = :story_id, headline=:headline, teaser=:teaser where row_id=:id");
    $statement->bindParam(':story_id', $story_id, PDO::PARAM_INT);
    $statement->bindParam(':headline', $headline);
    $statement->bindParam(':teaser', $teaser);
    $statement->bindParam(':id', $id);
    $statement->execute();
}

$db->exec("DELETE FROM Top10 where story_id = 0");
