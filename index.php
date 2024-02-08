<?php
// ===================================================================================
// Calorific -  Dead simple self-hosted calorie tracker
// Copyright ©️ 2023 xdpirate

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
require("./php/addmeal.php");
require("./php/addsavedmeal.php");
require("./php/addsavedingredient.php");
require("./php/edit.php");
require("./php/delete.php");

$resMeals = mysqli_query($link, "SELECT * FROM `meals` ORDER BY `name` ASC;");
$resIngredients = mysqli_query($link, "SELECT * FROM `ingredients` ORDER BY `name` ASC;");
$resToday = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) = CAST(NOW() AS DATE) ORDER BY `time` ASC;");
$resYesterday = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) = CAST(DATE_ADD(NOW(), INTERVAL -1 DAY) AS DATE) ORDER BY `time` ASC");
$resHistory = "";

if(isset($_GET['all'])) {
    $resHistory = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) < CAST(DATE_ADD(NOW(), INTERVAL -1 DAY) AS DATE) ORDER BY `time` DESC;");
} else {
    $resHistory = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) < CAST(DATE_ADD(NOW(), INTERVAL -1 DAY) AS DATE) ORDER BY `time` DESC LIMIT 20;");
}

?><!DOCTYPE html>

<html>
    <head>
        <title>Calorific</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            html,body,input,select,dialog {
                background-color: #2e3440;
                color: #e5e9f0;
                font-family: Arial, Helvetica, sans-serif;
            }

            a, a:visited {
                color: #e5e9f0;
                text-decoration: none;
            }

            h1 {
                margin-bottom: 1em;
            }

            #everything {
                width: 50%;
                margin: auto;
                margin-top: 50px;
            }

            #updateWrapper {
                border: 1px solid #e5e9f0;;
                border-radius: 10px;
                padding: 10px;
                margin-bottom: 20px;
            }

            div.miniboxwrapper {
                text-align: center;
            }

            div.minibox {
                display: inline-block;
                padding: 1em;
                border: 1px solid #e5e9f0;
                border-radius: 1em;
                width: fit-content;
                margin: auto;
                margin-top: 1em;
                margin-left: 0.5em;
                margin-right: 0.5em;
                line-height: 1.8em;
                text-align: center;
                vertical-align: middle;
            }
            
            summary {
                list-style: none;
                cursor: pointer;
                display: inline;
                user-select: none;
            }

            div.wide {
                width: 90%;
                line-height: 1em;
            }

            div.wide > table {
                text-align: left;
            }

            div.tab, div#tabcontents {
                padding: 0.8em;
                border-left: 1px solid #e5e9f0;
                border-right: 1px solid #e5e9f0;
                border-top: 1px solid #e5e9f0;
            }
            
            div.tab {
                margin-right: 0.8em;
                z-index: 5;
                user-select: none;
                cursor: pointer;
                display: inline-block;
            }

            span.delBtn, span.closeBtn, span.editBtn, span.cloneBtn {
                cursor: pointer;
            }

            span.closeBtn {
                float: right;
            }

            span.closeBtn, input#clearLogFieldsBtn {
                margin-left: 1em;
            }

            div#tabcontents {
                border-bottom: 1px solid #e5e9f0;
            }

            div#mealstoday, div#mealhistory {
                margin-top: 0.75em;
            }

            div#tabcontents {
                margin-top: -0.06em;
            }

            div.tab.selected, div#tabcontents {
                background-color: #434c5e;
            }

            .hidden {
                display: none;
            }

            select {
                max-width: 20em;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
            }
            
            td, th {
                padding: 0.4em;
            }

            .tableFooter {
                width: 100%;
                text-align: right;
            }

            input[type=submit]:not(#updateSubmitBtn) {
                width: 100%;
                height: 3em;
                margin-top: 1em;
            }

            input[type=number] {
                width: 5em;
            }

            th {
                background-color: #4c566a;
                text-align: left;
            }

            tr:nth-child(odd) {
                background: #434c5e;
            }

            tr:nth-child(even) {
                background: #4c566a;
            }

            td:first-child, th:first-child, td:nth-child(2), th:nth-child(2) {
                width: 1%;
                white-space: nowrap;
            }

            td:last-child, th:last-child {
                text-align: right;
            }

            dialog {
                border: 1px solid #e5e9f0;
            }

            dialog::backdrop {
                backdrop-filter: blur(5px);
            }

            #footer {
                width: 98%;
                margin-top: 20px;
                margin-bottom: 20px;
                font-size: smaller;
                text-align: center;
            }

            #footer a {
                text-decoration: underline;
            }

            /* Phone styles */
            @media all and (max-width: 1000px) {
                #everything {
                    width: 100%;
                    margin: auto;
                }
            }
        </style>
        <script src="./js/startscripts.js"></script>
        <link href="./favicon.png" rel="icon" type="image/png" />
    </head>

    <body>
        <?php if(!isset($_GET['update'])) { ?>
        <dialog id="editLogDialog">
            <form method="GET" action="./">
                <input type="hidden" name="edit" id="hiddenEditLogField" value="log">
                <input type="hidden" name="id" id="hiddenEditLogIDField" value="">
                <span class="closeBtn" title="Close" onclick="document.getElementById('editLogDialog').close()">❌</span>
                <b>Edit log entry</b><br /><br />
                
                <label for="editLogDate">Date and time:</label><br />
                <input type="date" name="editLogDate" id="editLogDate">
                <input type="time" name="editLogTime" id="editLogTime"><br /><br />

                <label for="editLogDescription">Description:</label><br />
                <input type="text" placeholder="Description" name="editLogDescription" id="editLogDescription"><br /><br />
                
                <label for="editLogKcal">Kcal:</label><br />
                <input type="number" name="editLogKcal" id="editLogKcal"><br /><br />

                <input type="submit" value="OK">
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
                                                $name = mysqli_result($resIngredients,$i,"name");
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

                        <div class="minibox wide">
                            <b>🍽️ Saved meals</b><hr>
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

                        <div class="minibox wide">
                            <b>🥔 Saved ingredients</b><hr>
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
                            
                            print("
                                <tr>
                                    <td colspan='4' class='tableFooter'><b>Total:</b> $dailyTotal</td>
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

                            print("
                                <tr>
                                    <td colspan='4' class='tableFooter'><b>Total:</b> $dailyTotal</td>
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
