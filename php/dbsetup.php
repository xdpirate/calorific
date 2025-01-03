<?php
// Setup DB if it's the first run
if(!isset($mysqlDB)) {
    $mysqlDB = "calorific";
    mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `$mysqlDB`");
    mysqli_select_db($link, $mysqlDB);
}

if (!isset($mysqlCollation)) {
    $mysqlCollation = "utf8mb4_0900_ai_ci";
}

mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS `meals` (
        `ID` int NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `kcal` int NOT NULL,
        PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$mysqlCollation;");

mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS `ingredients` (
        `ID` int NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `kcalPer100` int NOT NULL,
        PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$mysqlCollation;");

mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS `history` (
        `ID` int NOT NULL AUTO_INCREMENT,
        `description` varchar(255) NOT NULL,
        `kcal` int NOT NULL,
        `time` datetime NOT NULL,
        PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$mysqlCollation;");

mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS `settings` (
        `key` varchar(255) NOT NULL,
        `value` varchar(255) NOT NULL,
        PRIMARY KEY (`key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$mysqlCollation;");
