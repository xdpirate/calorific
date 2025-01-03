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

// Default settings values, will be overwritten by values found in db
$calorieGoal = 0;
$hourOffset = 0;
$filterBoxState = 1;

for($i = 0; $i < mysqli_num_rows($resSettings); $i++) {
    $key = mysqli_result($resSettings, $i, "key");
    $value = mysqli_result($resSettings, $i, "value");
    
    if($key == "calorieGoal") {
        $calorieGoal = $value;
    } elseif($key == "hourOffset") {
        $hourOffset = $value;
    } elseif($key == "filterBoxState") {
        $filterBoxState = $value;
    }
}

require("./php/dbclean.php");
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
$resFutureMeals = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) > CAST(DATE_ADD(NOW(), INTERVAL $hourOffset HOUR) AS DATE) ORDER BY `time` ASC");

$resHistoryQuery = "SELECT * FROM `history` WHERE CAST(`time` AS DATE) < CAST(DATE_ADD(DATE_ADD(NOW(), INTERVAL $hourOffset HOUR), INTERVAL -1 DAY) AS DATE) ORDER BY `time` DESC";
if(isset($_GET['all'])) {
    $resHistory = mysqli_query($link, $resHistoryQuery);
} else {
    $resHistory = mysqli_query($link, $resHistoryQuery . " LIMIT 10;");
}

?><!DOCTYPE html>

<html>
    <head>
        <title>Calorific</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="./styles.css?<?php print time(); ?>" rel="stylesheet" />
        <link href="./favicon.png" rel="icon" type="image/png" />
        <script src="./js/startscripts.js?<?php print time(); ?>"></script>
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
                <div id="collapseTab" data-div="none" class="tab">↕️ Collapse</div> 
            </div>

            <div id="tabcontents">
                <div id="logMealDiv" class="contentDiv">
                    <form method="GET" action=".">
                        <input type="hidden" name="newMealSubmitted" id="newMealSubmitted" value="1">

                        <div>
                            <span id="mealDescriptionFloat">
                                <input id="addMealTotalKcal" name="addMealTotalKcal" type="number" min="0" value="0"></input> kcal <input type="button" value="Clear meal" id="clearLogFieldsBtn" name="clearLogFieldsBtn"></input>
                            </span>

                            <span id="mealDescriptionSpan">
                                <input type="text" name="addMealDescription" id="addMealDescription" placeholder="Meal description (optional)">
                            </span>
                        </div>

                        <div id="logTimeArea">
                            Consumed:
                            <input type="radio" name="logWhen" id="logNow" value="now" checked><label for="logNow">Now</label>
                            
                            <input type="radio" name="logWhen" id="logNotNow" value="custom"><label for="logNotNow">Specify:</label>
                            
                            <input type="date" name="logCustomDate" id="logCustomDate" disabled>
                            <input type="time" name="logCustomTime" id="logCustomTime" disabled>
                        </div>


                        <div class="miniboxwrapper">
                            <div class="minibox">
                                <div><b>🍲 Add a saved meal to this log entry</b></div><hr>
                                
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
                                        $optionElems = "";

                                        if($numrows == 0) {
                                            $optionElems .= "<option disabled>No saved meals</option>";
                                        } else {
                                            for($i = 0; $i < $numrows; $i++) {
                                                $name = str_replace("'", "&apos;", mysqli_result($resMeals,$i,"name"));
                                                $kcal = mysqli_result($resMeals,$i,"kcal");
                
                                                $optionElems .= "
                                                    <option data-kcal='$kcal' data-name='$name'>
                                                        $name ($kcal kcal)
                                                    </option>
                                                ";
                                            }

                                        }

                                        print($optionElems);
                                    ?>
                                </select><br />

                                <div class="filterBoxWrapper <?php if($filterBoxState == 0) { print("hidden"); } ?>">
                                    <input type="search" class="filterBox" id="savedMealsFilterBox" placeholder="🔎 Filter the saved meal list" autocomplete="off"> <span class="clearSearch" title="Clear filter" onclick="this.previousElementSibling.value = ''; filterSelects('savedMealsFilterBox', 'addMealSavedMeals');">x</span>
                                </div>

                                <input id="addMealAddSavedMealBtn" type="button" value="+ Add"<?php if($numrows == 0) { ?> disabled<?php }?>><br />
                                <div class="kcalPreviewer" id="addMealKcalPreview"></div>
                            </div>

                            <div class="minibox">
                                <div><b>🥔 Add a saved ingredient to this log entry</b></div><hr>

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
                                                        $name ($kcalPer100 kcal per 100g/ml)
                                                    </option>
                                                ");
                                            }
                                        }
                                        
                                    ?>
                                </select><br />

                                <div class="filterBoxWrapper  <?php if($filterBoxState == 0) { print("hidden"); } ?>">
                                    <input type="search" class="filterBox" id="savedIngredientsFilterBox" placeholder="🔎 Filter the saved ingredients list" autocomplete="off"> <span class="clearSearch" title="Clear filter" onclick="this.previousElementSibling.value = ''; filterSelects('savedIngredientsFilterBox', 'addMealSavedIngredients');">x</span>
                                </div>

                                Amount: <input id="addMealAddSavedIngredientAmount" type="number" min="1" value="100"> g/ml
                                <input id="addMealAddSavedIngredientBtn" type="button" value="+ Add"<?php if($numrows == 0) { ?> disabled<?php }?>><br />
                                <div class="kcalPreviewer" id="addIngrToMealKcalPreview"></div>
                            </div>
                        </div>

                        <input id="submitMealBtn" type="submit" value="Log meal">
                    </form>
                </div>

                <div id="savedMealsDiv" class="contentDiv hidden">
                    <div class="miniboxwrapper">
                        <div class="minibox">
                            <form method="GET" action=".">
                                <div><b>🥗 Save a simple meal</b></div><hr>
                                <input type="hidden" name="newSavedMealSubmitted" id="newSavedMealSubmitted" value="1">
                                
                                <div>
                                    <input type="text" name="addSavedMealName" id="addSavedMealName" placeholder="Meal name"> <input id="addSavedMealTotalKcal" name="addSavedMealTotalKcal" type="number" min="0" value="0"> kcal
                                </div>

                                <input id="submitSavedMealBtn" type="submit" value="Save simple meal">
                            </form>
                        </div>

                        <div class="minibox">
                            <form method="GET" action=".">
                                <div><b>🥔 Build meal from saved ingredients</b></div><hr>
                                <input type="hidden" name="newSavedMealFromIngrSubmitted" id="newSavedMealFromIngrSubmitted" value="1">
                                
                                <input type="text" name="addSavedMealFromIngrName" id="addSavedMealFromIngrName" placeholder="Meal name"> <input id="addSavedMealFromIngrTotalKcal" name="addSavedMealFromIngrTotalKcal" type="number" min="0" value="0"> kcal<br /><br />

                                <div>
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
                                                            $name ($kcalPer100 kcal per 100g/ml)
                                                        </option>
                                                    ");
                                                }
                                            }
                                        ?>
                                    </select> <br />

                                    <div class="filterBoxWrapper  <?php if($filterBoxState == 0) { print("hidden"); } ?>">
                                        <input type="search" class="filterBox" id="mealBuilderIngredientsFilterBox" placeholder="🔎 Filter the saved ingredients list" autocomplete="off"> <span class="clearSearch" title="Clear filter" onclick="this.previousElementSibling.value = ''; filterSelects('mealBuilderIngredientsFilterBox', 'addSavedMealFromIngr');">x</span>
                                    </div>

                                    Amount: <input id="addSavedMealAddSavedIngredientAmount" type="number" min="1" value="100"> g/ml
                                    <input id="addSavedMealAddSavedIngredientBtn" type="button" value="+ Add"<?php if($numrows == 0) { ?> disabled<?php }?>><br />
                                    <div class="kcalPreviewer" id="addIngrToSavedMealKcalPreview"></div>
                                </div>

                                <input id="submitSavedMealFromIngrBtn" type="submit" value="Save built meal"<?php if($numrows == 0) { ?> disabled<?php }?>> <input id="addSavedMealClearBtn" type="button" value="Clear">
                            </form>
                        </div>
                    </div>
                    
                    <div class="foodlist wide">
                        <div class="savedIngrMealHeader"><b>🍽️ Saved meals</b></div><hr>
                        <div class="savedIngrMealWrapper">
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
                                <div><b>🥗 Save an ingredient</b></div><hr>
                                <input type="hidden" name="newSavedIngrSubmitted" id="newSavedIngrSubmitted" value="1">
                                
                                <div>
                                    <input type="text" name="addSavedIngrName" id="addSavedIngrName" placeholder="Ingredient name"> <input id="addSavedIngrTotalKcal" name="addSavedIngrTotalKcal" type="number" min="0" value="0"> kcal per 100g/ml
                                </div>

                                <input id="submitSavedIngrBtn" type="submit" value="Save ingredient">
                            </form>
                        </div>
                    </div>

                    <div class="foodlist wide">
                        <div class="savedIngrMealHeader"><b>🥔 Saved ingredients</b></div><hr>
                        <div class="savedIngrMealWrapper">
                            <table>
                                <thead>
                                    <th>⚙️</th>
                                    <th>Ingredient name</th>
                                    <th>kcal per 100 g/ml</th>
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
                    <form method="GET" action="." id="logCleanupForm">
                        <div class="miniboxwrapper">
                            <div class="minibox">
                                <div><b>📃 Log cleanup</b> <sup><span id="logCleanupExplanationToggler" class="explanationToggler" title="Toggle explanation">[?]</span></sup></div><hr />
                                <div class="optionExplanation hidden" id="logCleanupExplanation">
                                    Use this setting if you want to clean up your database and remove old log entries. Saved meals and saved ingredients are <b>not</b> affected by this, only your meal log. Valid values are 0-12 months. A value of zero (0) will clear <i>all</i> historical data from the meal log.
                                </div>
                                Delete log entries older than <input type="number" name="logCleanupNum" id="logCleanupNum" min="0" max="12" value="3"> months<br />
                                <input type="submit" value="Delete log entries" id="logCleanupSubmitBtn" name="logCleanupSubmitBtn">
                            </div>
                        </div>
                    </form>

                    <form method="GET" action=".">
                        <div class="miniboxwrapper">
                            <div class="minibox">
                                <div><b>🏆 Daily calorie goal</b> <sup><span id="calorieGoalExplanationToggler" class="explanationToggler" title="Toggle explanation">[?]</span></sup></div><hr />
                                <div class="optionExplanation hidden" id="calorieGoalExplanation">
                                    Enabling a daily calorie goal will show how much under/over you are in relation to your goal each day, next to the daily calorie total. Being <i>under</i> your goal will show the difference in <span class="calorieGoalNeutral">orange</span>. Being <i>over</i> your goal will show the difference in <span class="calorieGoalNegative">red</span>. Being within 10% of your goal in either direction will show the difference in <span class="calorieGoalPositive">green</span>. Set to 0 to disable this function.
                                </div>
                                <input type="number" name="calorieGoalNum" id="calorieGoalNum" min="0" value="<?php print($calorieGoal); ?>">
                            </div>
                            <div class="minibox">
                                <div><b>🕔 Hour offset</b> <sup><span id="hourOffsetExplanationToggler" class="explanationToggler" title="Toggle explanation">[?]</span></sup></div><hr />
                                <div class="optionExplanation hidden" id="hourOffsetExplanation">
                                    If your log entries are saved with the wrong hour in the database, and you can't change the time on the server, you can set an hour offset here. The offset will apply to the entire application. Negative values will set application time before server time, and positive values will set application time ahead of server time. Valid values are -24 to +24. Note that already existing database entries will not be updated to the new offset.
                                </div>
                                <input type="number" name="hourOffsetNum" id="hourOffsetNum" min="-24" max="24" value="<?php print($hourOffset); ?>">
                            </div>
                            <div class="minibox">
                                <div><b>🔎 Filter boxes</b> <sup><span id="filterBoxExplanationToggler" class="explanationToggler" title="Toggle explanation">[?]</span></sup></div><hr />
                                <div class="optionExplanation hidden" id="filterBoxExplanation">
                                    Enable or disable the filter boxes shown under each drop down list. This makes it easier to navigate a large list of saved meals or ingredients, but may be superfluous if you keep a tidy list of elements in each category.
                                </div>
                                <input type="checkbox" name="filterBoxState" id="filterBoxState" <?php if($filterBoxState == 1) { print("checked"); } ?>><label for="filterBoxState">Enabled</label>
                            </div>
                        </div>
                        <input type="hidden" name="settingsSubmitted" id="settingsSubmitted" value="1">
                        <input type="submit" value="Save settings">
                    </form>
                </div>
            </div>

            <?php
                if(mysqli_num_rows($resFutureMeals) > 0) {
            ?>

            <h2 id="futureHeader">Time traveling meals</h2>

            <table>
                <thead>
                    <th>⚙️</th>
                    <th>🕔</th>
                    <th>Description</th>
                    <th>kcal consumed</th>
                </thead>
                <tbody>
                <?php
                    $numrows = mysqli_num_rows($resFutureMeals);
                    for($i = 0; $i < $numrows; $i++) {
                        $id = mysqli_result($resFutureMeals,$i,"ID");
                        $timestamp = mysqli_result($resFutureMeals,$i,"time");
                        $date = date("Y-m-d", strtotime($timestamp));
                        $time = date("H:i", strtotime($timestamp));
                        $description = mysqli_result($resFutureMeals,$i,"description");
                        $kcal = mysqli_result($resFutureMeals,$i,"kcal");

                        print("
                            <tr>
                                <td>
                                    <details>
                                    <summary>⚙️</summary>
                                        <span class='delBtn' data-src='log' data-id='$id' data-name='$description' title='Delete'>❌</span>
                                        <span class='editBtn' data-src='log' data-id='$id' data-name='$description' data-kcal='$kcal' data-date='$date' data-time='$time' title='Edit'>✏️</span>
                                        <span class='cloneBtn' data-name='$description' data-kcal='$kcal' title='Log again'>📑</span>
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

            <?php } ?>
            
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
                                                <span class='cloneBtn' data-name='$description' data-kcal='$kcal' title='Log again'>📑</span>
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
                                                <span class='cloneBtn' data-name='$description' data-kcal='$kcal' title='Log again'>📑</span>
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
           
            <div id="mealhistory">
                <?php
                    // Read DB contents into arrays
                    $idEntries = [];
                    $dateEntries = [];
                    $timeEntries = [];
                    $descriptionEntries = [];
                    $kcalEntries = [];
                    
                    $numrows = mysqli_num_rows($resHistory);
                    for($i = 0; $i < $numrows; $i++) {
                        $idEntries[$i] = mysqli_result($resHistory,$i,"ID");
                        $timestamp = mysqli_result($resHistory,$i,"time");
                        $dateEntries[$i] = date("Y-m-d", strtotime($timestamp));
                        $timeEntries[$i] = date("H:i", strtotime($timestamp));
                        $descriptionEntries[$i] = mysqli_result($resHistory,$i,"description");
                        $kcalEntries[$i] = mysqli_result($resHistory,$i,"kcal");
                    }

                    $uniqueDates = array_unique($dateEntries);

                    for($i = 0; $i < count($uniqueDates); $i++) {
                        $dailyTotal = 0;
                        print("<h2>".array_values($uniqueDates)[$i]."</h2>\n");
                        ?>
                        <table>
                            <thead>
                                <th>⚙️</th>
                                <th>🕔</th>
                                <th>Description</th>
                                <th>kcal consumed</th>
                            </thead>
                            <tbody>
                        <?php
                        for($j = 0; $j < count($dateEntries); $j++) {
                            if(array_values($uniqueDates)[$i] == array_values($dateEntries)[$j]) {
                                $dailyTotal += $kcalEntries[$j];
                                print("
                                    <tr>
                                        <td>
                                            <details>
                                            <summary>⚙️</summary>
                                                <span class='delBtn' data-src='log' data-id='$idEntries[$j]' data-name='$descriptionEntries[$j]' title='Delete'>❌</span>
                                                <span class='editBtn' data-src='log' data-id='$idEntries[$j]' data-name='$descriptionEntries[$j]' data-kcal='$kcalEntries[$j]' data-date='$dateEntries[$j]' data-time='$timeEntries[$j]' title='Edit'>✏️</span>
                                                <span class='cloneBtn' data-name='$descriptionEntries[$j]' data-kcal='$kcalEntries[$j]' title='Log again'>📑</span>
                                            </details>
                                        </td>
                                        <td>$timeEntries[$j]</td>
                                        <td>$descriptionEntries[$j]</td>
                                        <td>$kcalEntries[$j]</td>
                                    </tr>
                                ");
                            } // Closing inner if
                        } // Closing inner for loop (for every date)

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
                        print("</tbody>\n</table>");
                    } // Closing outer for loop (for unique dates)
                ?>
            </div>
            <?php        
                } else {
            ?>
            <h2 id="historyHeader">Recent <sup><small><small><a href="./?all#historyHeader">[show all]</a></small></small></sup></h2>
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
                    for($i = 0; $i < $numrows; $i++) {
                        $id = mysqli_result($resHistory,$i,"ID");
                        $timestamp = mysqli_result($resHistory,$i,"time");
                        $date = date("Y-m-d", strtotime($timestamp));
                        $time = date("H:i", strtotime($timestamp));
                        $description = mysqli_result($resHistory,$i,"description");
                        $kcal = mysqli_result($resHistory,$i,"kcal");

                        print("
                            <tr>
                                <td>
                                    <details>
                                    <summary>⚙️</summary>
                                        <span class='delBtn' data-src='log' data-id='$id' data-name='$description' title='Delete'>❌</span>
                                        <span class='editBtn' data-src='log' data-id='$id' data-name='$description' data-kcal='$kcal' data-date='$date' data-time='$time' title='Edit'>✏️</span>
                                        <span class='cloneBtn' data-name='$description' data-kcal='$kcal' title='Log again'>📑</span>
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
            <?php
                }
            ?>
        </div><?php } else { ?>

        <div id="updateWrapper">
            <?php require("./php/update.php"); ?>
        </div>

        <?php } ?>

        <div id="footer">
            Calorific <?php $commitHash = substr(file_get_contents('.git/refs/heads/main'),0,7); print("(ver. <a href='https://github.com/xdpirate/calorific/commit/$commitHash'>$commitHash</a>)"); ?> &copy; 2023-<?php print(date("Y")); ?> xdpirate. Licensed under the <a href="https://github.com/xdpirate/calorific/blob/main/LICENSE.md" target="_blank">GNU General Public License v3.0</a>. <a href="https://github.com/xdpirate/calorific" target="_blank">Github</a> <?php if($updaterEnabled == true) { ?><a href="./?update" title="Click to update this installation of Calorific. Requires git on the server.">Update</a><?php } ?>
        </div>
        
        <div id="toastContainer">
            <div id="toastNotification"></div>
        </div>

        <script src="./js/endscripts.js?<?php print time(); ?>"></script>
    </body>
</html>
<?php mysqli_close($link); ?>
