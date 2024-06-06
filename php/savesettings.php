<?php
if(isset($_GET['settingsSubmitted']) && $_GET['settingsSubmitted'] == "1") {
    if(isset($_GET['calorieGoalNum'])) {
        $calorieGoalNum = mysqli_real_escape_string($link, trim($_GET["calorieGoalNum"]));
        mysqli_query($link, "REPLACE INTO settings VALUES ('calorieGoal','$calorieGoalNum')");
    }
    
    // do more settings here
    mysqli_close($link);
    header("Location: ./?t=settings");
}
