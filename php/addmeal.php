<?php
if(isset($_GET['newMealSubmitted']) && $_GET['newMealSubmitted'] == "1") {
    $mealDescription = mysqli_real_escape_string($link, trim($_GET["addMealDescription"]));

    $mealDescription = trim($mealDescription);
    if(str_ends_with($mealDescription, ",")) {
        $mealDescription = substr($mealDescription, 0, -1);
    }


    $mealTotalKcal = mysqli_real_escape_string($link, trim($_GET["addMealTotalKcal"]));
    mysqli_query($link, "INSERT INTO history (`description`,`kcal`, `time`) VALUES ('$mealDescription','$mealTotalKcal',NOW())");
    mysqli_close($link);

    header("Location: ./");
}
