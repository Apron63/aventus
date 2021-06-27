<?php

require_once 'db.php';

$connection = new db();
if (null === $connection) {
    return null;
}
$date = isset($_GET['date']) ? new DateTime($_GET['date']) : new DateTime();
$sort = $_GET['sort'] ?? 1;

$data = [];
$sql = 'SELECT m.name, m.movie_id, m.image 
        FROM movie m
        LEFT JOIN  rating r ON m.id = r.movie_id
        ORDER BY m.name
        LIMIT 10
        ';
$stmt = $connection->dbcon->query($sql);
while ($row = $stmt->fetch()) {
    $data[] = [
        'name' => $row['name'],
        'movieId' => $row['movie_id'] ?? '',
        'image' => $row['image'],
    ];
}

echo json_encode($data);