<?php
if(isset($_GET['settingsSubmitted']) && $_GET['settingsSubmitted'] == "1") {
    if(isset($_GET['calorieGoalNum'])) {
        $calorieGoalNum = mysqli_real_escape_string($link, trim($_GET["calorieGoalNum"]));
        mysqli_query($link, "REPLACE INTO settings VALUES ('calorieGoal','$calorieGoalNum')");
    }

    if(isset($_GET['hourOffsetNum'])) {
        $hourOffsetNum = mysqli_real_escape_string($link, trim($_GET["hourOffsetNum"]));
        mysqli_query($link, "REPLACE INTO settings VALUES ('hourOffset','$hourOffsetNum')");
    }

    $filterBoxState = 0;
    
    if(isset($_GET['filterBoxState']) && trim($_GET["filterBoxState"]) == "on") {
        $filterBoxState = 1;
    }

    mysqli_query($link, "REPLACE INTO settings VALUES ('filterBoxState','$filterBoxState')");

    mysqli_close($link);
    header("Location: ./?t=settings&saved=1");
}
