<?php

require_once 'db.php';

$connection = new db();
if (null === $connection) {
    return null;
}
$date = isset($_GET['date']) ? new DateTime($_GET['date']) : new DateTime();
$sort = $_GET['sort'] ?? 1;

$data = [];
$sql = "SELECT DISTINCT m.name, m.movie_id, m.image, 
            (SELECT round(avg(rate),2) FROM rating r1 WHERE r1.movie_id = m.movie_id 
                AND r1.vote_date BETWEEN STR_TO_DATE ('0000-08-00 00:00:00', '%Y-%m-%d %H:%i:%s') 
                    AND STR_TO_DATE ('{$date->format('Y-m-d')}', '%Y-%m-%d %H:%i:%s')) AS avg 
        FROM movie m
        LEFT JOIN  rating r ON m.id = r.movie_id
        ORDER BY avg DESC, m.name ASC
        LIMIT 10
        ";
$stmt = $connection->dbcon->query($sql);
while ($row = $stmt->fetch()) {
    $data[] = [
        'name' => $row['name'],
        'movieId' => $row['movie_id'] ?? '',
        'image' => $row['image'],
        'avg' => $row['avg'] ?? '',
    ];
}

echo json_encode($data);