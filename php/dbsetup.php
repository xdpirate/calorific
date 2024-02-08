<?php
// Setup DB if it's the first run
mysqli_query($link, "CREATE DATABASE IF NOT EXISTS calorific");
mysqli_select_db($link, "calorific");
mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS `meals` (
        `ID` int NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `kcal` int NOT NULL,
        PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;");

mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS `ingredients` (
        `ID` int NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `kcalPer100` int NOT NULL,
        PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;");


mysqli_query($link, "
    CREATE TABLE IF NOT EXISTS `history` (
        `ID` int NOT NULL AUTO_INCREMENT,
        `description` varchar(255) NOT NULL,
        `kcal` int NOT NULL,
        `time` datetime NOT NULL,
        PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;");
