<?php
if(isset($_GET['edit'])) {
    if($_GET['edit'] == "log") {
        $id = mysqli_real_escape_string($link, $_GET['id']);
        $date = mysqli_real_escape_string($link, $_GET['editLogDate']);
        $time = mysqli_real_escape_string($link, $_GET['editLogTime']);
        $description = mysqli_real_escape_string($link, $_GET['editLogDescription']);
        $kcal = mysqli_real_escape_string($link, $_GET['editLogKcal']);

        mysqli_query($link, "UPDATE `history` SET time = '$date $time:00', description = '$description', kcal = '$kcal' WHERE ID=$id");

        header("Location: ./?t=log&edited=1");
    } elseif($_GET['edit'] == "meal") {
        $id = mysqli_real_escape_string($link, $_GET['id']);
        $name = mysqli_real_escape_string($link, $_GET['editMealIngredientName']);
        $kcal = mysqli_real_escape_string($link, $_GET['editMealIngredientKcal']);

        mysqli_query($link, "UPDATE `meals` SET name = '$name', kcal = '$kcal' WHERE ID=$id");

        header("Location: ./?t=meals&edited=1");
    } elseif($_GET['edit'] == "ingredient") {
        $id = mysqli_real_escape_string($link, $_GET['id']);
        $name = mysqli_real_escape_string($link, $_GET['editMealIngredientName']);
        $kcal = mysqli_real_escape_string($link, $_GET['editMealIngredientKcal']);

        mysqli_query($link, "UPDATE `ingredients` SET name = '$name', kcalPer100 = '$kcal' WHERE ID=$id");

        header("Location: ./?t=ingredients&edited=1");
    } else {
        die("Malformed request");
    }
}
