<?php
if(isset($_GET['edit'])) {
    if($_GET['edit'] == "log") {
        $id = mysqli_real_escape_string($link, $_GET['id']);
        $description = mysqli_real_escape_string($link, $_GET['editLogDescription']);
        $kcal = mysqli_real_escape_string($link, $_GET['editLogKcal']);

        mysqli_query($link, "UPDATE `history` SET description = '$description', kcal = '$kcal' WHERE ID=$id");

        header("Location: ./?t=log");
    } elseif($_GET['edit'] == "meal") {
        $id = mysqli_real_escape_string($link, $_GET['id']);
        $name = mysqli_real_escape_string($link, $_GET['editMealIngredientName']);
        $kcal = mysqli_real_escape_string($link, $_GET['editMealIngredientKcal']);

        mysqli_query($link, "UPDATE `meals` SET name = '$name', kcal = '$kcal' WHERE ID=$id");

        header("Location: ./?t=meals");
    } elseif($_GET['edit'] == "ingredient") {
        $id = mysqli_real_escape_string($link, $_GET['id']);
        $name = mysqli_real_escape_string($link, $_GET['editMealIngredientName']);
        $kcal = mysqli_real_escape_string($link, $_GET['editMealIngredientKcal']);

        mysqli_query($link, "UPDATE `ingredients` SET name = '$name', kcalPer100 = '$kcal' WHERE ID=$id");

        header("Location: ./?t=ingredients");
    } else {
        die("Malformed request");
    }
}
?>