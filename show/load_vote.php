<?php
header("Content-type:text/html;charset=utf-8");

//bulid database.php yourself
require_once "../database.php";

$db_mysqli = new mysqli($db_hostname, $db_user, $db_password, $db_database);
$result = $db_mysqli->query("select * from Vote order by tid");

if(!isset($result) || $result->num_rows <= 0) {
    echo json_encode(array("status" => false, "msg" => "No team information"));
}

$returner = array();
$i = 0;
$max = 0;
while($row = $result->fetch_object()) {
    $returner[$i] = array();
    $returner[$i]["tid"] = $row->tid;
    $returner[$i]["voted"] = $row->voted;

    if($_POST["first"] == true) {
        $returner[$i]["name"] = $row->name;
        $returner[$i]["work"] = $row->work;
    }

    if($max < $row->voted)
        $max = $row->voted;

    ++$i;
}

$len = count($returner);
for($i = 0; $i < $len; ++$i) {
    if($max == 0) {
        $returner[$i]["percent"] = 50;
    } else {
        $returner[$i]["percent"] = ((float)$returner[$i]["voted"] / (float)$max) * 100;
    }
}

echo json_encode(array("status" => true, "msg" => $returner));
