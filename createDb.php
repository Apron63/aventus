<?php

require_once 'db.php';

if (null === $dbcon) {
    exit(1);
}

// Категории сериалов
$sql = 'CREATE TABLE IF NOT EXISTS `category` ( 
            `id` INT NOT NULL AUTO_INCREMENT, 
            `name` VARCHAR(255) NOT NULL, 
            `url` VARCHAR(255) NOT NULL, 
            `anchor` INT, 
            PRIMARY KEY (`ID`))
        ';
$dbcon->exec($sql);
echo 'Table CATEGORY has been created.' . PHP_EOL;

$sql = 'INSERT INTO `category` (`name`, `url`, `anchor`) VALUES
            ("Полнометражные фильмы", "http://www.world-art.ru/cinema/rating_top.php", NULL),
            ("Западные сериалы", "http://www.world-art.ru/cinema/rating_tv_top.php", 1),
            ("Японские дорамы", "http://www.world-art.ru/cinema/rating_tv_top.php", 2),
            ("Корейские дорамы", "http://www.world-art.ru/cinema/rating_tv_top.php", 4),
            ("Российские сериалы", "http://www.world-art.ru/cinema/rating_tv_top.php", 3)
        ';
$dbcon->exec($sql);

$sql = 'CREATE TABLE IF NOT EXISTS `rating` ( 
            `id` INT NOT NULL AUTO_INCREMENT, 
            `categoryId` INT NOT NULL, 
            `position` INT NOT NULL, 
            `estimateRating` FLOAT NOT NULL, 
            `votes` INT NOT NULL, 
            `averageRating` FLOAT NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `year` INT NOT NULL, 
            `description` VARCHAR(1000) NOT NULL,
            `image` VARCHAR(255) NOT NULL,
            `parseDate` DATE NOT NULL,
            PRIMARY KEY (`ID`),
            INDEX `date_idx` (`parseDate`)) 
        ';
$dbcon->exec($sql);
echo 'Table RATING has been created.' . PHP_EOL;