<?php
if(isset($_GET['delete']) && isset($_GET['from'])) {
    $ID = mysqli_real_escape_string($link, $_GET['delete']);
    
    $table = "";
    if($_GET['from'] == "log") {
        $table = "history";
    } elseif($_GET['from'] == "meals") {
        $table = "meals";
    } elseif($_GET['from'] == "ingredients") {
        $table = "ingredients";
    } else {
        die("Malformed request.");
    }

    mysqli_query($link, "DELETE FROM $table WHERE ID=$ID");
    mysqli_close($link);

    header("Location: ./?t=$_GET[from]&deleted=1");
}
