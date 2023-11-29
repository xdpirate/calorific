<?php
require("credentials.php");

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
require("./php/delete.php");

$resMeals = mysqli_query($link, "SELECT * FROM `meals` ORDER BY `name` ASC;");
$resIngredients = mysqli_query($link, "SELECT * FROM `ingredients` ORDER BY `name` ASC;");
$resToday = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) = CAST(NOW() AS DATE) ORDER BY `time` ASC;");
$resHistory = mysqli_query($link, "SELECT * FROM `history` WHERE CAST(`time` AS DATE) != CAST(NOW() AS DATE) ORDER BY `time` DESC LIMIT 20;");

?><!DOCTYPE html>

<html>
    <head>
        <title>Calorific</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            html,body,input {
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

            div.wide {
                width: 90%;
                line-height: 1em;
            }

            div.wide > table {
                text-align: left;
            }

            span.tab, div#tabcontents {
                padding: 0.8em;
                border-left: 1px solid #e5e9f0;
                border-right: 1px solid #e5e9f0;
                border-top: 1px solid #e5e9f0;
            }
            
            span.tab {
                margin-right: 0.8em;
                z-index: 5;
                user-select: none;
                cursor: pointer;
            }

            span.delBtn {
                cursor: pointer;
            }

            div#tabcontents {
                border-bottom: 1px solid #e5e9f0;
            }

            div#tabcontents, div#mealstoday, div#mealhistory {
                margin-top: 0.75em;
            }

            span.tab.selected, div#tabcontents {
                background-color: #434c5e;
            }

            .hidden {
                display: none;
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

            input[type=submit] {
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
        <div id="everything">
            <h1><a href="./"><img src="./favicon.png" width="32" height="32" /> Calorific</a></h1>

            <div id="tabbar">
                <span id="logMealTab" data-div="logMealDiv" class="tab selected">📑 Log a meal</span> 
                <span id="savedMealsTab" data-div="savedMealsDiv" class="tab">🍲 Saved meals</span> 
                <span id="savedIngredientsTab" data-div="savedIngredientsDiv" class="tab">🥔 Saved ingredients</span> 
            </div>

            <div id="tabcontents">
                <div id="logMealDiv" class="contentDiv">
                    <form method="GET" action=".">
                        <input type="hidden" name="newMealSubmitted" id="newMealSubmitted" value="1">

                        <div>
                            Meal description (optional): <input type="text" name="addMealDescription" id="addMealDescription" placeholder="Meal description"></input> Total kcal: <input id="addMealTotalKcal" name="addMealTotalKcal" type="number" min="0" value="0"></input>
                        </div>

                        <div class="miniboxwrapper">
                            <div class="minibox">
                                <b>🍲 Add a saved meal to this log entry</b><hr>

                                <select name="addMealSavedMeals" id="addMealSavedMeals">
                                    <?php
                                        $numrows = mysqli_num_rows($resMeals); 
                                        for($i = 0; $i < $numrows; $i++) {
                                            $name = mysqli_result($resMeals,$i,"name");
                                            $kcal = mysqli_result($resMeals,$i,"kcal");
            
                                            print("
                                                <option data-kcal='$kcal' data-name='$name'>
                                                    $name ($kcal kcal)
                                                </option>
                                            ");
                                        }
                                    ?>
                                </select>

                                <input id="addMealAddSavedMealBtn" type="button" value="+ Add">
                            </div>

                            <div class="minibox">
                                <b>🥔 Add a saved ingredient to this log entry</b><hr>

                                <select name="addMealSavedIngredients" id="addMealSavedIngredients">
                                    <option value="potato" data-kcal="80" data-name="Potato">Potato (80 kcal pr 100g/ml)</option>
                                    <?php
                                        $numrows = mysqli_num_rows($resIngredients); 
                                        for($i = 0; $i < $numrows; $i++) {
                                            $name = mysqli_result($resIngredients,$i,"name");
                                            $kcalPer100 = mysqli_result($resIngredients,$i,"kcalPer100");

                                            print("
                                                <option data-kcal='$kcalPer100' data-name='$name'>
                                                    $name ($kcalPer100 kcal pr. 100g/ml)
                                                </option>
                                            ");
                                        }
                                    ?>
                                </select> <br />

                                Amount: <input id="addMealAddSavedIngredientAmount" type="number" min="1" value="100"> g/ml
                                <input id="addMealAddSavedIngredientBtn" type="button" value="+ Add">
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
                                            for($i = 0; $i < $numrows; $i++) {
                                                $name = mysqli_result($resIngredients,$i,"name");
                                                $kcalPer100 = mysqli_result($resIngredients,$i,"kcalPer100");

                                                print("
                                                    <option data-kcal='$kcalPer100' data-name='$name'>
                                                        $name ($kcalPer100 kcal pr. 100g/ml)
                                                    </option>
                                                ");
                                            }
                                        ?>
                                    </select> <br />

                                    Amount: <input id="addSavedMealAddSavedIngredientAmount" type="number" min="1" value="100"> g/ml
                                    <input id="addSavedMealAddSavedIngredientBtn" type="button" value="+ Add">
                                </div>

                                <input id="submitSavedMealFromIngrBtn" type="submit" value="Save built meal">
                            </form>
                        </div>

                        <div class="minibox wide">
                            <b>🍽️ Saved meals</b><hr>
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
                                            $name = mysqli_result($resMeals,$i,"name");
                                            $kcal = mysqli_result($resMeals,$i,"kcal");
            
                                            print("
                                                <tr>
                                                    <td><span class='delBtn' data-src='meals' data-id='$id' data-name='$name'>❌</span></td>
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

                        <div class="minibox wide">
                            <b>🥔 Saved ingredients</b><hr>
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
                                            $name = mysqli_result($resIngredients,$i,"name");
                                            $kcalPer100 = mysqli_result($resIngredients,$i,"kcalPer100");
            
                                            print("
                                                <tr>
                                                    <td><span class='delBtn' data-src='ingredients' data-id='$id' data-name='$name'>❌</span></td>
                                                    <td>$name</td>
                                                    <td>$kcalPer100</td>
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

            <h2>Today</h2>

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
                                $timestamp = date("H:i", strtotime($timestamp));
                                $description = mysqli_result($resToday,$i,"description");
                                $kcal = mysqli_result($resToday,$i,"kcal");
                                $dailyTotal += $kcal;

                                print("
                                    <tr>
                                        <td><span class='delBtn' data-src='log' data-id='$id' data-name='$description'>❌</span></td>
                                        <td>$timestamp</td>
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

            <h2>Previous 20 meals</h2>

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
                                $description = mysqli_result($resHistory,$i,"description");
                                $kcal = mysqli_result($resHistory,$i,"kcal");
                                $dailyTotal += $kcal;

                                print("
                                    <tr>
                                        <td><span class='delBtn' data-src='log' data-id='$id' data-name='$description'>❌</span></td>
                                        <td>$timestamp</td>
                                        <td>$description</td>
                                        <td>$kcal</td>
                                    </tr>
                                ");
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <script src="./js/endscripts.js"></script>
    </body>
</html>