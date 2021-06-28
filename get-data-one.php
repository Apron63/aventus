<?php

require_once 'db.php';

$connection = new db();
if (null === $connection) {
    return null;
}
$id = (int)$_GET['id'];

if (!isset($id)) {
    echo '';
    return;
}

$stmt = $connection->dbcon->prepare('SELECT name, image, description FROM movie WHERE movie_id = :movieId');
$stmt->bindParam(':movieId', $id);
if ($res = $stmt->execute()) {
    $row = $stmt->fetch();
}

echo json_encode($row);