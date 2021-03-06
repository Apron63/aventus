<?php

require_once 'db.php';

$connection = new db();
if (null === $connection->dbcon) {
    exit(1);
}

$sql = 'CREATE TABLE IF NOT EXISTS `category` ( 
            `id` INT NOT NULL AUTO_INCREMENT, 
            `name` VARCHAR(255) NOT NULL, 
            `url` VARCHAR(255) NOT NULL, 
            `anchor` INT, 
            PRIMARY KEY (`ID`))
        ';
$connection->dbcon->exec($sql);
echo 'Table CATEGORY has been created.' . PHP_EOL;

$sql = 'INSERT INTO `category` (`name`, `url`, `anchor`) VALUES
            ("Полнометражные фильмы", "http://www.world-art.ru/cinema/rating_top.php", NULL),
            ("Западные сериалы", "http://www.world-art.ru/cinema/rating_tv_top.php", 1),
            ("Японские дорамы", "http://www.world-art.ru/cinema/rating_tv_top.php", 2),
            ("Корейские дорамы", "http://www.world-art.ru/cinema/rating_tv_top.php", 4),
            ("Российские сериалы", "http://www.world-art.ru/cinema/rating_tv_top.php", 3)
        ';
$connection->dbcon->exec($sql);

$sql = 'CREATE TABLE IF NOT EXISTS `movie` ( 
            `id` INT NOT NULL AUTO_INCREMENT,
            `movie_id` INT NOT NULL,
            `category_id` INT NOT NULL, 
            `nom` INT NOT NULL, 
            `name` VARCHAR(255) NOT NULL,
            `year` INT, 
            `description` VARCHAR(10000),
            `image` VARCHAR(255),
            PRIMARY KEY (`ID`),
            INDEX `movie_id` (`movie_id`)) 
        ';
$connection->dbcon->exec($sql);
echo 'Table MOVIE has been created.' . PHP_EOL;

$sql = 'CREATE TABLE IF NOT EXISTS `rating` ( 
            `id` INT NOT NULL AUTO_INCREMENT,
            `movie_id` INT NOT NULL,
            `vote_date` DATE NOT NULL, 
            `rate` FLOAT NOT NULL, 
            PRIMARY KEY (`ID`),
            INDEX `date_idx` (`movie_id`,`vote_date`)) 
        ';
$connection->dbcon->exec($sql);
echo 'Table RATING has been created.' . PHP_EOL;

$sql = 'CREATE TABLE IF NOT EXISTS `config` ( 
            `parsing_date` DATE NULL) 
        ';
$connection->dbcon->exec($sql);
$sql = 'INSERT INTO `config` (`parsing_date`) VALUES ("0000.00.00")';
$connection->dbcon->exec($sql);
echo 'Table CONFIG has been created.' . PHP_EOL;