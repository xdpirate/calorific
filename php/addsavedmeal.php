<?php
if(isset($_GET['newSavedMealSubmitted']) && $_GET['newSavedMealSubmitted'] == "1") {
    $mealName = mysqli_real_escape_string($link, trim($_GET["addSavedMealName"]));

    $mealName = trim($mealName);
    if(str_ends_with($mealName, ",")) {
        $mealName = substr($mealName, 0, -1);
    }


    $mealTotalKcal = mysqli_real_escape_string($link, trim($_GET["addSavedMealTotalKcal"]));
    mysqli_query($link, "INSERT INTO meals (`name`,`kcal`) VALUES ('$mealName','$mealTotalKcal')");
    mysqli_close($link);

    header("Location: ./?t=meals");
} elseif(isset($_GET['newSavedMealFromIngrSubmitted']) && $_GET['newSavedMealFromIngrSubmitted'] == "1") {
    $mealName = mysqli_real_escape_string($link, trim($_GET["addSavedMealFromIngrName"]));

    $mealName = trim($mealName);
    if(str_ends_with($mealName, ",")) {
        $mealName = substr($mealName, 0, -1);
    }


    $mealTotalKcal = mysqli_real_escape_string($link, trim($_GET["addSavedMealFromIngrTotalKcal"]));
    mysqli_query($link, "INSERT INTO meals (`name`,`kcal`) VALUES ('$mealName','$mealTotalKcal')");
    mysqli_close($link);

    header("Location: ./?t=meals");
}
