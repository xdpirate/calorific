let toastTimeout;

function registerTabs() {
    let tabspans = document.querySelectorAll("div.tab");
    
    let tabToggler = function() {
        for(let i = 0; i < tabspans.length; i++) {
            if(this == tabspans[i]) {
                tabspans[i].classList.add("selected");
                document.querySelector("#" + tabspans[i].getAttribute("data-div")).classList.remove("hidden");

                const url = new URL(window.location);
                if(this.id == "logMealTab") {
                    url.searchParams.set("t", "log");
                } else if(this.id == "savedMealsTab") {
                    url.searchParams.set("t", "meals");
                } else if(this.id == "savedIngredientsTab") {
                    url.searchParams.set("t", "ingredients");
                } else if(this.id == "settingsTab") {
                    url.searchParams.set("t", "settings");
                }

                window.history.replaceState({}, "", url);
            } else {
                tabspans[i].classList.remove("selected");
                document.querySelector("#" + tabspans[i].getAttribute("data-div")).classList.add("hidden");
            }
        }
    };
    
    for(let i = 0; i < tabspans.length; i++) {
        tabspans[i].addEventListener("click", tabToggler);
    }    
}

function registerButtons() {
    let addMealAddSavedMeal = function() {
        let selectedMeal = document.querySelector("select#addMealSavedMeals");
        let amount = Number(document.querySelector("select#addMealSavedMealsNum").value);
        let kcal = Number(selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-kcal")) * amount;
        let name = selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-name");
        document.querySelector("#addMealDescription").value += amount + "x " + name + ", ";
        document.querySelector("#addMealTotalKcal").value = Number(document.querySelector("#addMealTotalKcal").value) + kcal;
        showToastNotification(`✓ ${amount}x ${name} added to current meal`);
    };

    document.getElementById("addMealAddSavedMealBtn").addEventListener("click", addMealAddSavedMeal);

    let addMealAddSavedIngredient = function() {
        let selectedMeal = document.querySelector("#addMealSavedIngredients");
        let kcal = Number(selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-kcal"));
        let amount = Number(document.querySelector("#addMealAddSavedIngredientAmount").value);
        kcal = Math.ceil((amount / 100) * kcal);

        let name = selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-name");
        document.querySelector("#addMealDescription").value += `${name} (${amount}g/ml), `;
        document.querySelector("#addMealTotalKcal").value = Number(document.querySelector("#addMealTotalKcal").value) + kcal;
        showToastNotification(`✓ ${amount}g/ml ${name} added to current meal`);
    };

    document.getElementById("addMealAddSavedIngredientBtn").addEventListener("click", addMealAddSavedIngredient);

    let addSavedMealAddSavedIngredient = function() {
        let selectedMeal = document.querySelector("#addSavedMealFromIngr");
        let kcal = Number(selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-kcal"));
        let amount = Number(document.querySelector("#addSavedMealAddSavedIngredientAmount").value);
        kcal = Math.ceil((amount / 100) * kcal);

        let name = selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-name");
        document.querySelector("#addSavedMealFromIngrName").value += `${name} (${amount}g/ml), `;
        document.querySelector("#addSavedMealFromIngrTotalKcal").value = Number(document.querySelector("#addSavedMealFromIngrTotalKcal").value) + kcal;
    };

    document.getElementById("addSavedMealAddSavedIngredientBtn").addEventListener("click", addSavedMealAddSavedIngredient);

    let deleteEntry = function() {
        let src = this.getAttribute("data-src");
        let id = this.getAttribute("data-id");
        let name = this.getAttribute("data-name");

        let noun = "log entry";
        if(src == "meals") {
            noun = "meal";
        } else if(src == "ingredients") {
            noun = "ingredient";
        }

        if(confirm(`Are you sure you want to delete the ${noun} "${name}"?`)) {
            document.location.href = `./?delete=${id}&from=${src}`;
        }
    };

    let deleteButtons = document.querySelectorAll("span.delBtn");
    for(let i = 0; i < deleteButtons.length; i++) {
        deleteButtons[i].addEventListener("click", deleteEntry);
    }

    let editEntry = function() {
        let src = this.getAttribute("data-src");
        let id = this.getAttribute("data-id");
        let date = this.getAttribute("data-date");
        let time = this.getAttribute("data-time");
        let name = this.getAttribute("data-name");
        let kcal = Number(this.getAttribute("data-kcal"));

        if(src == "log") {
            document.querySelector("#hiddenEditLogIDField").value = id;
            document.querySelector("#editLogDate").value = date;
            document.querySelector("#editLogTime").value = time;
            document.querySelector("#editLogDescription").value = name;
            document.querySelector("#editLogKcal").value = kcal;

            document.querySelector("#editLogDialog").showModal();
        } else {
            let noun = "";
            if(src == "meals") {
                noun = "meal";
                document.querySelector("#editMealIngredientKcalLabel").innerText = "Kcal:";
            } else if(src == "ingredients") {
                noun = "ingredient";
                document.querySelector("#editMealIngredientKcalLabel").innerText = "Kcal per 100g/ml:";
            }

            document.querySelector("#hiddenEditField").value = noun;
            document.querySelector("#hiddenEditIDField").value = id;
            
            document.querySelector("#editMealIngredientDialogHeader").innerText = "Edit " + noun;
            document.querySelector("#editMealIngredientDescriptionLabel").innerText = noun.charAt(0).toUpperCase() + noun.slice(1) + " name:";
            
            document.querySelector("#editMealIngredientName").value = name;
            document.querySelector("#editMealIngredientName").placeholder = noun.charAt(0).toUpperCase() + noun.slice(1) + " name";
            document.querySelector("#editMealIngredientKcal").value = kcal;

            document.querySelector("#editMealIngredientDialog").showModal();
        }
    };

    let editButtons = document.querySelectorAll("span.editBtn");
    for(let i = 0; i < editButtons.length; i++) {
        editButtons[i].addEventListener("click", editEntry);
    }

    let cloneEntry = function() {
        let name = this.getAttribute("data-name");
        let kcal = Number(this.getAttribute("data-kcal"));

        document.querySelector("div#logMealTab").click();

        document.querySelector("input#addMealDescription").value = name;
        document.querySelector("input#addMealTotalKcal").value = kcal;

        document.querySelector("div#logMealTab").scrollIntoView();
    };

    let cloneButtons = document.querySelectorAll("span.cloneBtn");
    for(let i = 0; i < cloneButtons.length; i++) {
        cloneButtons[i].addEventListener("click", cloneEntry);
    }

    document.querySelector("#clearLogFieldsBtn").addEventListener("click", function() {
        document.querySelector("#addMealDescription").value = "";
        document.querySelector("#addMealTotalKcal").value = 0;
        showToastNotification("✓ Meal cleared");
    });

    document.querySelector("#editLogTimestampNow").addEventListener("click", function() {
        let now = new Date();
        now.setHours(now.getHours()+hourOffset);
        
        document.querySelector("#editLogDate").value = now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0") + "-" + String(now.getDate()).padStart(2, "0");
        document.querySelector("#editLogTime").value = String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0");
    });

    document.querySelector("#calorieGoalExplanationToggler").addEventListener("click", function() {
        document.querySelector("#calorieGoalExplanation").classList.toggle("hidden");
    });

    document.querySelector("#hourOffsetExplanationToggler").addEventListener("click", function() {
        document.querySelector("#hourOffsetExplanation").classList.toggle("hidden");
    });
}

function initialChangeTab() {
    let params = new URLSearchParams(document.location.search);

    if(params.get("t")) {
        if(params.get("t") == "log") {
            document.querySelector("#logMealTab").click();
        } else if(params.get("t") == "meals") {
            document.querySelector("#savedMealsTab").click();
        } else if(params.get("t") == "ingredients") {
            document.querySelector("#savedIngredientsTab").click();
        } else if(params.get("t") == "settings") {
            document.querySelector("#settingsTab").click();
        }
    }
}

function showToastNotification(text) {
    clearTimeout(toastTimeout);

    let toastNotification = document.getElementById("toastNotification");
    toastNotification.innerHTML = text;
    toastNotification.classList.add("show");
    
    toastTimeout = setTimeout(function(){ 
        toastNotification.classList.remove("show"); 
    }, 3000);
}
