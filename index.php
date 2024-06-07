<?php
// ===================================================================================
// Calorific -  Dead simple self-hosted calorie tracker
// Copyright ©️ 2023-2024 xdpirate

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.
// ===================================================================================

// These credentials are for the Docker image. If you want to run Calorific
// locally without using Docker, don't change these; add them to credentials.php!
$mysqlHost = "db";
$mysqlUser = "php_docker";
$mysqlPassword = "password123";

error_reporting(E_ERROR); // Silence the next line so it doesn't cry when running in Docker
include("./credentials.php");

error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set("display_errors", 1);

require("./php/functions.php");

$link = mysqli_connect($mysqlHost, $mysqlUser, $mysqlPassword);
if(!$link) {
    die("Couldn't connect: " . mysqli_error($link));
}

require("./php/dbsetup.php");

$resSettings = mysqli_query($link, "SELECT * FROM `settings`;");
$calorieGoal = 0;
$hourOffset = 0;

for($i = 0; $i < mysqli_num_rows($resSettings); $i++) {
    if(mysqli_result($resSettings, $i, "key") == "calorieGoal") {
        $calorieGoal = mysqli_result($resSettings, $i, "value");
    } elseif(mysqli_result($resSettings, $i, "key") == "hourOffset") {
        $hourOffset = mysqli_result($resSettings, $i, "value");
    }
}

require("./php/addmeal.php");
require("./php/addsavedmeal.php");
require("./php/addsavedingredient.php");
require("./php/edit.php");
require("./php/delete.php");
require("./php/savesettings.php");

$resMeals = mysqli_query($link, "SELECT * FROM `meals` ORDER BY `name` ASC;");
$resIngredients = mysqli_query($link, "SELECT * FROM `ingredients` ORDER BY `name` ASC;");
$resToday = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) = CAST(DATE_ADD(NOW(), INTERVAL $hourOffset HOUR) AS DATE) ORDER BY `time` ASC;");
$resYesterday = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) = CAST(DATE_ADD(DATE_ADD(NOW(), INTERVAL $hourOffset HOUR), INTERVAL -1 DAY) AS DATE) ORDER BY `time` ASC");
$resHistory = "";

if(isset($_GET['all'])) {
    $resHistory = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) < CAST(DATE_ADD(DATE_ADD(NOW(), INTERVAL $hourOffset HOUR), INTERVAL -1 DAY) AS DATE) ORDER BY `time` DESC;");
} else {
    $resHistory = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) < CAST(DATE_ADD(DATE_ADD(NOW(), INTERVAL $hourOffset HOUR), INTERVAL -1 DAY) AS DATE) ORDER BY `time` DESC LIMIT 10;");
}

?><!DOCTYPE html>

<html>
    <head>
        <title>Calorific</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="./styles.css" rel="stylesheet" />
        <link href="./favicon.png" rel="icon" type="image/png" />
        <script src="./js/startscripts.js"></script>
    </head>

    <body>
        <script>
            let hourOffset = <?php print($hourOffset); ?>;
        </script>
        
        <?php if(!isset($_GET['update'])) { ?>
        <dialog id="editLogDialog">
            <form method="GET" action="./">
                <input type="hidden" name="edit" id="hiddenEditLogField" value="log">
                <input type="hidden" name="id" id="hiddenEditLogIDField" value="">
                <span class="closeBtn" title="Close" onclick="document.getElementById('editLogDialog').close()">❌</span>
                <b>Edit log entry</b><br /><br />
                
                <label for="editLogDate">Date and time:</label><br />
                <input type="date" name="editLogDate" id="editLogDate">
                <input type="time" name="editLogTime" id="editLogTime">
                <input type="button" name="editLogTimestampNow" id="editLogTimestampNow" value="Now">
                <br /><br />

                <label for="editLogDescription">Description:</label><br />
                <input type="text" placeholder="Description" name="editLogDescription" id="editLogDescription"><br /><br />
                
                <label for="editLogKcal">Kcal:</label><br />
                <div class="flexbox">
                    <input type="number" name="editLogKcal" id="editLogKcal"> <input type="submit" class="flexgrow" value="OK" id="editLogSubmitBtn" name="editLogSubmitBtn">
                </div>
            </form>
        </dialog><?php } ?>
        <dialog id="editMealIngredientDialog">
            <form method="GET" action="./">
                <input type="hidden" name="edit" id="hiddenEditField" value="">
                <input type="hidden" name="id" id="hiddenEditIDField" value="">
                <span class="closeBtn" title="Close" onclick="document.getElementById('editMealIngredientDialog').close()">❌</span>
                <b id="editMealIngredientDialogHeader">Edit meal/ingredient</b><br /><br />
                
                <label for="editMealIngredientName" id="editMealIngredientDescriptionLabel">Name:</label><br />
                <input type="text" placeholder="Name" name="editMealIngredientName" id="editMealIngredientName"><br /><br />
                
                <label for="editMealIngredientKcal" id="editMealIngredientKcalLabel">Kcal per 100g/ml:</label><br />
                <input type="number" name="editMealIngredientKcal" id="editMealIngredientKcal"><br /><br />

                <input type="submit" value="OK">
            </form>
        </dialog>

        <div id="everything">
            <h1><a href="./"><img src="./favicon.png" width="32" height="32" /> Calorific</a></h1>

            <?php if(!isset($_GET['update'])) { ?>

            <div id="tabbar">
                <div id="logMealTab" data-div="logMealDiv" class="tab selected">📑 Log</div> 
                <div id="savedMealsTab" data-div="savedMealsDiv" class="tab">🍲 Meals</div> 
                <div id="savedIngredientsTab" data-div="savedIngredientsDiv" class="tab">🥔 Ingredients</div> 
                <div id="settingsTab" data-div="settingsDiv" class="tab">⚙️ Settings</div> 
            </div>

            <div id="tabcontents">
                <div id="logMealDiv" class="contentDiv">
                    <form method="GET" action=".">
                        <input type="hidden" name="newMealSubmitted" id="newMealSubmitted" value="1">

                        <div>
                            Meal description (optional): <input type="text" name="addMealDescription" id="addMealDescription" placeholder="Meal description"></input> Total kcal: <input id="addMealTotalKcal" name="addMealTotalKcal" type="number" min="0" value="0"></input> <input type="button" value="Clear" id="clearLogFieldsBtn" name="clearLogFieldsBtn"></input>
                        </div>

                        <div class="miniboxwrapper">
                            <div class="minibox">
                                <b>🍲 Add a saved meal to this log entry</b><hr>

                                <select name="addMealSavedMealsNum" id="addMealSavedMealsNum">
                                    <option value="1">1x</option>
                                    <option value="2">2x</option>
                                    <option value="3">3x</option>
                                    <option value="4">4x</option>
                                    <option value="5">5x</option>
                                    <option value="6">6x</option>
                                    <option value="7">7x</option>
                                    <option value="8">8x</option>
                                    <option value="9">9x</option>
                                    <option value="10">10x</option>
                                </select>

                                <select name="addMealSavedMeals" id="addMealSavedMeals">
                                    <?php
                                        $numrows = mysqli_num_rows($resMeals);

                                        if($numrows == 0) {
                                            print("<option disabled>No saved meals</option>");
                                        } else {
                                            for($i = 0; $i < $numrows; $i++) {
                                                $name = str_replace("'", "&apos;", mysqli_result($resMeals,$i,"name"));
                                                $kcal = mysqli_result($resMeals,$i,"kcal");
                
                                                print("
                                                    <option data-kcal='$kcal' data-name='$name'>
                                                        $name ($kcal kcal)
                                                    </option>
                                                ");
                                            }
                                        }
                                    ?>
                                </select>

                                <input id="addMealAddSavedMealBtn" type="button" value="+ Add"<?php if($numrows == 0) { ?> disabled<?php }?>>
                            </div>

                            <div class="minibox">
                                <b>🥔 Add a saved ingredient to this log entry</b><hr>

                                <select name="addMealSavedIngredients" id="addMealSavedIngredients">
                                    <?php
                                        $numrows = mysqli_num_rows($resIngredients); 
                                        if($numrows == 0) {
                                            print("<option disabled>No saved ingredients</option>");
                                        } else {
                                            for($i = 0; $i < $numrows; $i++) {
                                                $name = str_replace("'", "&apos;", mysqli_result($resIngredients,$i,"name"));
                                                $kcalPer100 = mysqli_result($resIngredients,$i,"kcalPer100");

                                                print("
                                                    <option data-kcal='$kcalPer100' data-name='$name'>
                                                        $name ($kcalPer100 kcal pr. 100g/ml)
                                                    </option>
                                                ");
                                            }
                                        }
                                        
                                    ?>
                                </select> <br />

                                Amount: <input id="addMealAddSavedIngredientAmount" type="number" min="1" value="100"> g/ml
                                <input id="addMealAddSavedIngredientBtn" type="button" value="+ Add"<?php if($numrows == 0) { ?> disabled<?php }?>>
                            </div>
                        </div>

                        <input id="submitMealBtn" type="submit" value="Log meal">
                    </form>
                </div>

                <div id="savedMealsDiv" class="contentDiv hidden">
                    <div class="miniboxwrapper">
                        <div class="minibox">
                            <form method="GET" action=".">
                                <b>🥗 Save a simple meal</b><hr>
                                <input type="hidden" name="newSavedMealSubmitted" id="newSavedMealSubmitted" value="1">
                                
                                <div>
                                    Meal name (required): <input type="text" name="addSavedMealName" id="addSavedMealName" placeholder="Meal name"></input><br />Total kcal: <input id="addSavedMealTotalKcal" name="addSavedMealTotalKcal" type="number" min="0" value="0"></input>
                                </div>

                                <input id="submitSavedMealBtn" type="submit" value="Save simple meal">
                            </form>
                        </div>

                        <div class="minibox">
                            <form method="GET" action=".">
                                <b>🥔 Build meal from saved ingredients</b><hr>
                                <input type="hidden" name="newSavedMealFromIngrSubmitted" id="newSavedMealFromIngrSubmitted" value="1">
                                
                                <div>
                                    Meal name (required): <input type="text" name="addSavedMealFromIngrName" id="addSavedMealFromIngrName" placeholder="Meal name"></input><br />Total kcal: <input id="addSavedMealFromIngrTotalKcal" name="addSavedMealFromIngrTotalKcal" type="number" min="0" value="0"></input><br />

                                    <select name="addSavedMealFromIngr" id="addSavedMealFromIngr">
                                        <?php
                                            $numrows = mysqli_num_rows($resIngredients); 
                                            if($numrows == 0) {
                                                print("<option disabled>No saved meals</option>");
                                            } else {
                                                for($i = 0; $i < $numrows; $i++) {
                                                    $name = str_replace("'", "&apos;", mysqli_result($resIngredients,$i,"name"));
                                                    $kcalPer100 = mysqli_result($resIngredients,$i,"kcalPer100");

                                                    print("
                                                        <option data-kcal='$kcalPer100' data-name='$name'>
                                                            $name ($kcalPer100 kcal pr. 100g/ml)
                                                        </option>
                                                    ");
                                                }
                                            }
                                        ?>
                                    </select> <br />

                                    Amount: <input id="addSavedMealAddSavedIngredientAmount" type="number" min="1" value="100"> g/ml
                                    <input id="addSavedMealAddSavedIngredientBtn" type="button" value="+ Add"<?php if($numrows == 0) { ?> disabled<?php }?>>
                                </div>

                                <input id="submitSavedMealFromIngrBtn" type="submit" value="Save built meal"<?php if($numrows == 0) { ?> disabled<?php }?>>
                            </form>
                        </div>
                    </div>
                    
                    <div class="foodlist wide">
                        <div style="width: 100%; text-align: center;"><b>🍽️ Saved meals</b></div><hr>
                        <div style="max-height: 12em; overflow-y: auto;">
                            <table>
                                <thead>
                                    <th>⚙️</th>
                                    <th>Meal name</th>
                                    <th>kcal</th>
                                </thead>
                                <tbody>
                                    <?php
                                        $numrows = mysqli_num_rows($resMeals); 
                                        for($i = 0; $i < $numrows; $i++) {
                                            $id = mysqli_result($resMeals,$i,"ID");
                                            $name = str_replace("'", "&apos;", mysqli_result($resMeals,$i,"name"));
                                            $kcal = mysqli_result($resMeals,$i,"kcal");
            
                                            print("
                                                <tr>
                                                    <td><span class='delBtn' data-src='meals' data-id='$id' data-name='$name'>❌</span><span class='editBtn' data-src='meals' data-id='$id' data-name='$name' data-kcal='$kcal'>✏️</span></td>
                                                    <td>$name</td>
                                                    <td>$kcal</td>
                                                </tr>
                                            ");
                                        }
                                        
                                        print("
                                            <tr>
                                                <td colspan='3' class='tableFooter'><b>Total saved meals:</b> $numrows</td>
                                            </tr>
                                        ");
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="savedIngredientsDiv" class="contentDiv hidden">
                    <div class="miniboxwrapper">
                        <div class="minibox">
                            <form method="GET" action=".">
                                <b>🥗 Save an ingredient</b><hr>
                                <input type="hidden" name="newSavedIngrSubmitted" id="newSavedIngrSubmitted" value="1">
                                
                                <div>
                                    Ingredient name (required): <input type="text" name="addSavedIngrName" id="addSavedIngrName" placeholder="Ingredient name"></input><br />Kcal pr. 100g or ml: <input id="addSavedIngrTotalKcal" name="addSavedIngrTotalKcal" type="number" min="0" value="0"></input>
                                </div>

                                <input id="submitSavedIngrBtn" type="submit" value="Save ingredient">
                            </form>
                        </div>
                    </div>

                    <div class="foodlist wide">
                        <div style="width: 100%; text-align: center;"><b>🥔 Saved ingredients</b></div><hr>
                        <div style="max-height: 12em; overflow-y: auto;">
                            <table>
                                <thead>
                                    <th>⚙️</th>
                                    <th>Ingredient name</th>
                                    <th>kcal pr. 100 g/ml</th>
                                </thead>
                                <tbody>
                                    <?php
                                        $numrows = mysqli_num_rows($resIngredients); 
                                        for($i = 0; $i < $numrows; $i++) {
                                            $id = mysqli_result($resIngredients,$i,"ID");
                                            $name = str_replace("'", "&apos;", mysqli_result($resIngredients,$i,"name"));
                                            $kcalPer100 = mysqli_result($resIngredients,$i,"kcalPer100");
            
                                            print("
                                                <tr>
                                                    <td><span class='delBtn' data-src='ingredients' data-id='$id' data-name='$name'>❌</span><span class='editBtn' data-src='ingredients' data-id='$id' data-name='$name' data-kcal='$kcal'>✏️</span></td>
                                                    <td>$name</td>
                                                    <td>$kcalPer100</td>
                                                </tr>
                                            ");
                                        }
                                        
                                        print("
                                            <tr>
                                                <td colspan='3' class='tableFooter'><b>Total saved ingredients:</b> $numrows</td>
                                            </tr>
                                        ");
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="settingsDiv" class="contentDiv hidden">
                    <form method="GET" action=".">
                        <div class="miniboxwrapper">
                            <div class="minibox">
                                <b>🏆 Daily calorie goal</b> <sup><span id="calorieGoalExplanationToggler" class="explanationToggler" title="Toggle explanation">[?]</span></sup><hr />
                                <div class="optionExplanation hidden" id="calorieGoalExplanation">
                                    Enabling a daily calorie goal will show how much under/over you are in relation to your goal each day, next to the daily calorie total. Being <i>under</i> your goal will show the difference in <span class="calorieGoalNeutral">orange</span>. Being <i>over</i> your goal will show the difference in <span class="calorieGoalNegative">red</span>. Being within 10% of your goal in either direction will show the difference in <span class="calorieGoalPositive">green</span>. Set to 0 to disable this function.
                                </div>
                                Daily calorie goal: <input type="number" name="calorieGoalNum" id="calorieGoalNum" min="0" value="<?php print($calorieGoal); ?>">
                            </div>
                            <div class="minibox">
                                <b>🕔 Hour offset</b> <sup><span id="hourOffsetExplanationToggler" class="explanationToggler" title="Toggle explanation">[?]</span></sup><hr />
                                <div class="optionExplanation hidden" id="hourOffsetExplanation">
                                    If your log entries are saved with the wrong hour in the database, and you can't change the time on the server, you can set an hour offset here. The offset will apply to the entire application. Negative values will set application time before server time, and positive values will set application time ahead of server time. Valid values are -24 to +24.
                                </div>
                                Hour offset: <input type="number" name="hourOffsetNum" id="hourOffsetNum" min="-24" max="24" value="<?php print($hourOffset); ?>">
                            </div>
                        </div>
                        <input type="hidden" name="settingsSubmitted" id="settingsSubmitted" value="1">
                        <input type="submit" value="Save settings">
                    </form>
                </div>
            </div>

            <h2 id="todayHeader">Today</h2>

            <div id="mealstoday">
                <table>
                    <thead>
                        <th>⚙️</th>
                        <th>🕔</th>
                        <th>Description</th>
                        <th>kcal consumed</th>
                    </thead>
                    <tbody>
                        <?php
                            $numrows = mysqli_num_rows($resToday); 
                            $dailyTotal = 0;
                            for($i = 0; $i < $numrows; $i++) {
                                $id = mysqli_result($resToday,$i,"ID");
                                $timestamp = mysqli_result($resToday,$i,"time");
                                $date = date("Y-m-d", strtotime($timestamp));
                                $time = date("H:i", strtotime($timestamp));
                                $description = mysqli_result($resToday,$i,"description");
                                $kcal = mysqli_result($resToday,$i,"kcal");
                                $dailyTotal += $kcal;

                                print("
                                    <tr>
                                        <td>
                                            <details>
                                            <summary>⚙️</summary>
                                                <span class='delBtn' data-src='log' data-id='$id' data-name='$description' title='Delete'>❌</span>
                                                <span class='editBtn' data-src='log' data-id='$id' data-name='$description' data-kcal='$kcal' data-date='$date' data-time='$time' title='Edit'>✏️</span>
                                                <span class='cloneBtn' data-src='meals' data-id='$id' data-name='$description' data-kcal='$kcal' title='Log again'>📑</span>
                                            </details>
                                        </td>
                                        <td>$time</td>
                                        <td>$description</td>
                                        <td>$kcal</td>
                                    </tr>
                                ");
                            }

                            $calorieGoalInfo = "";
                            if($calorieGoal > 0) {
                                $goalRangeMin = round($calorieGoal * 0.9);
                                $goalRangeMax = round($calorieGoal * 1.1);

                                $difference = $dailyTotal - $calorieGoal;
                                if($difference > -1) {
                                    $difference = "+" . $difference;
                                }

                                if($dailyTotal <= $goalRangeMin) {
                                    $calorieGoalInfo = " (<span class='calorieGoalNeutral' title='Not reached the calorie goal'>$difference</span>)";
                                } elseif($dailyTotal > $goalRangeMin && $dailyTotal < $goalRangeMax) {
                                    $calorieGoalInfo = " (<span class='calorieGoalPositive' title='Within 10% of the calorie goal'>$difference</span>)";
                                } elseif($dailyTotal >= $goalRangeMax) {
                                    $calorieGoalInfo = " (<span class='calorieGoalNegative' title='Over the calorie goal'>$difference</span>)";
                                }
                            }
                            
                            print("
                                <tr>
                                    <td colspan='4' class='tableFooter'><b>Total:</b> $dailyTotal$calorieGoalInfo</td>
                                </tr>
                            ");
                        ?>
                    </tbody>
                </table>
            </div>

            <h2 id="yesterdayHeader">Yesterday</h2>

            <div id="mealsyesterday">
            <table>
                    <thead>
                        <th>⚙️</th>
                        <th>🕔</th>
                        <th>Description</th>
                        <th>kcal consumed</th>
                    </thead>
                    <tbody>
                        <?php
                            $numrows = mysqli_num_rows($resYesterday); 
                            $dailyTotal = 0;
                            for($i = 0; $i < $numrows; $i++) {
                                $id = mysqli_result($resYesterday,$i,"ID");
                                $timestamp = mysqli_result($resYesterday,$i,"time");
                                $date = date("Y-m-d", strtotime($timestamp));
                                $time = date("H:i", strtotime($timestamp));
                                $description = mysqli_result($resYesterday,$i,"description");
                                $kcal = mysqli_result($resYesterday,$i,"kcal");
                                $dailyTotal += $kcal;

                                print("
                                    <tr>
                                        <td>
                                            <details>
                                            <summary>⚙️</summary>
                                                <span class='delBtn' data-src='log' data-id='$id' data-name='$description' title='Delete'>❌</span>
                                                <span class='editBtn' data-src='log' data-id='$id' data-name='$description' data-kcal='$kcal' data-date='$date' data-time='$time' title='Edit'>✏️</span>
                                                <span class='cloneBtn' data-src='meals' data-id='$id' data-name='$description' data-kcal='$kcal' title='Log again'>📑</span>
                                            </details>
                                        </td>
                                        <td>$time</td>
                                        <td>$description</td>
                                        <td>$kcal</td>
                                    </tr>
                                ");
                            }

                            $calorieGoalInfo = "";
                            if($calorieGoal > 0) {
                                $goalRangeMin = round($calorieGoal * 0.9);
                                $goalRangeMax = round($calorieGoal * 1.1);

                                $difference = $dailyTotal - $calorieGoal;
                                if($difference > -1) {
                                    $difference = "+" . $difference;
                                }

                                if($dailyTotal <= $goalRangeMin) {
                                    $calorieGoalInfo = " (<span class='calorieGoalNeutral' title='Not reached the calorie goal'>$difference</span>)";
                                } elseif($dailyTotal > $goalRangeMin && $dailyTotal < $goalRangeMax) {
                                    $calorieGoalInfo = " (<span class='calorieGoalPositive' title='Within 10% of the calorie goal'>$difference</span>)";
                                } elseif($dailyTotal >= $goalRangeMax) {
                                    $calorieGoalInfo = " (<span class='calorieGoalNegative' title='Over the calorie goal'>$difference</span>)";
                                }
                            }

                            print("
                                <tr>
                                    <td colspan='4' class='tableFooter'><b>Total:</b> $dailyTotal$calorieGoalInfo</td>
                                </tr>
                            ");
                        ?>
                    </tbody>
                </table>
            </div>

            <?php
                if(isset($_GET['all'])) {
            ?>
            <h2 id="historyHeader">All meals <sup><small><small><a href="./">[show recent]</a></small></small></sup></h2>
            <?php        
                } else {
            ?>
            <h2 id="historyHeader">Previous <sup><small><small><a href="./?all">[show all]</a></small></small></sup></h2>
            <?php
                }
            ?>

            <div id="mealhistory">
            <table>
                    <thead>
                        <th>⚙️</th>
                        <th>🕔</th>
                        <th>Description</th>
                        <th>kcal consumed</th>
                    </thead>
                    <tbody>
                        <?php
                            $numrows = mysqli_num_rows($resHistory); 
                            $dailyTotal = 0;
                            for($i = 0; $i < $numrows; $i++) {
                                $id = mysqli_result($resHistory,$i,"ID");
                                $timestamp = mysqli_result($resHistory,$i,"time");
                                $date = date("Y-m-d", strtotime($timestamp));
                                $time = date("H:i", strtotime($timestamp));
                                $description = mysqli_result($resHistory,$i,"description");
                                $kcal = mysqli_result($resHistory,$i,"kcal");
                                $dailyTotal += $kcal;

                                print("
                                    <tr>
                                        <td>
                                            <details>
                                            <summary>⚙️</summary>
                                                <span class='delBtn' data-src='log' data-id='$id' data-name='$description' title='Delete'>❌</span>
                                                <span class='editBtn' data-src='log' data-id='$id' data-name='$description' data-kcal='$kcal' data-date='$date' data-time='$time' title='Edit'>✏️</span>
                                                <span class='cloneBtn' data-src='meals' data-id='$id' data-name='$description' data-kcal='$kcal' title='Log again'>📑</span>
                                            </details>
                                        </td>
                                        <td>$date $time</td>
                                        <td>$description</td>
                                        <td>$kcal</td>
                                    </tr>
                                ");
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div><?php } else { ?>

        <div id="updateWrapper">
            <?php require("./php/update.php"); ?>
        </div>

        <?php } ?>

        <div id="footer">
            Calorific <?php $commitHash = substr(file_get_contents('.git/refs/heads/main'),0,7); print("(ver. <a href='https://github.com/xdpirate/calorific/commit/$commitHash'>$commitHash</a>)"); ?> &copy; 2023 xdpirate. Licensed under the <a href="https://github.com/xdpirate/calorific/blob/main/LICENSE.md" target="_blank">GNU General Public License v3.0</a>. <a href="https://github.com/xdpirate/calorific" target="_blank">Github</a> <?php if($updaterEnabled == true) { ?><a href="./?update" title="Click to update this installation of Calorific. Requires git on the server.">Update</a><?php } ?>
        </div>
        <script src="./js/endscripts.js"></script>
    </body>
</html>
<?php mysqli_close($link); ?>
