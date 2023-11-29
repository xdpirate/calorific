<?php
if(isset($_GET['newSavedIngrSubmitted']) && $_GET['newSavedIngrSubmitted'] == "1") {
    $ingrName = mysqli_real_escape_string($link, trim($_GET["addSavedIngrName"]));

    $ingrName = trim($ingrName);
    if(str_ends_with($ingrName, ",")) {
        $ingrName = substr($ingrName, 0, -1);
    }


    $ingrKcalPer100 = mysqli_real_escape_string($link, trim($_GET["addSavedIngrTotalKcal"]));
    mysqli_query($link, "INSERT INTO ingredients (`name`,`kcalPer100`) VALUES ('$ingrName','$ingrKcalPer100')");
    mysqli_close($link);

    header("Location: ./?t=ingredients");
}
?>
