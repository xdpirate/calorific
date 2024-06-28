<?php
if(isset($_GET['logCleanupNum']) && is_numeric($_GET['logCleanupNum'])) {
    $months = $_GET['logCleanupNum'];
    if($months == 0) {
        mysqli_query($link, "TRUNCATE `history`;");
    } else if($months <= 12) {
        mysqli_query($link, "DELETE FROM `history` WHERE CAST(`time` AS DATE) < CAST(DATE_ADD(DATE_ADD(NOW(), INTERVAL $hourOffset HOUR), INTERVAL -$months MONTH) AS DATE) ORDER BY `time` DESC;");
    }
    
    header("Location: ./?t=settings&dbcleaned=1");
}
