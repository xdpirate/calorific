<?php
if(isset($_GET['newMealSubmitted']) && $_GET['newMealSubmitted'] == "1") {
    $mealDescription = mysqli_real_escape_string($link, trim($_GET["addMealDescription"]));

    $mealDescription = trim($mealDescription);
    if(str_ends_with($mealDescription, ",")) {
        $mealDescription = substr($mealDescription, 0, -1);
    }
    $mealTotalKcal = mysqli_real_escape_string($link, trim($_GET["addMealTotalKcal"]));

    $query = "";
    if(isset($_GET['logWhen']) && $_GET['logWhen'] == "custom") {
        $customDate = mysqli_real_escape_string($link, trim($_GET["logCustomDate"]));
        $customTime = mysqli_real_escape_string($link, trim($_GET["logCustomTime"]));

        $query = "INSERT INTO history (`description`,`kcal`, `time`) VALUES ('$mealDescription','$mealTotalKcal','$customDate $customTime')";
    } else {
        $query = "INSERT INTO history (`description`,`kcal`, `time`) VALUES ('$mealDescription','$mealTotalKcal',DATE_ADD(NOW(), INTERVAL $hourOffset HOUR))";
    }

    mysqli_query($link, $query);
    mysqli_close($link);

    header("Location: ./?t=log&saved=1");
}
