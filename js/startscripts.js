let toastTimeout;

function registerTabs() {
    let tabspans = document.querySelectorAll("div.tab");
    
    let tabToggler = function() {
        for(let i = 0; i < tabspans.length; i++) {
            if(this == tabspans[i]) {
                tabspans[i].classList.add("selected");

                if(tabspans[i].getAttribute("data-div") == "none") {
                    toggleControlPanel(false);
                } else {
                    toggleControlPanel(true);
                    document.querySelector("#" + tabspans[i].getAttribute("data-div")).classList.remove("hidden");
                }

                const url = new URL(window.location);
                if(this.id == "logMealTab") {
                    url.searchParams.set("t", "log");
                } else if(this.id == "savedMealsTab") {
                    url.searchParams.set("t", "meals");
                } else if(this.id == "savedIngredientsTab") {
                    url.searchParams.set("t", "ingredients");
                } else if(this.id == "settingsTab") {
                    url.searchParams.set("t", "settings");
                } else if(this.id == "collapseTab") {
                    url.searchParams.set("t", "collapse");
                }

                window.history.replaceState({}, "", url);
            } else {
                tabspans[i].classList.remove("selected");
                
                if(tabspans[i].getAttribute("data-div") != "none") {
                    document.querySelector("#" + tabspans[i].getAttribute("data-div")).classList.add("hidden");
                }
            }
        }
    };
    
    for(let i = 0; i < tabspans.length; i++) {
        tabspans[i].addEventListener("click", tabToggler);
    }    
}

function toggleControlPanel(shown) {
    localStorage.calorificControlPanelShown = shown;
    if(shown) {
        document.querySelector("#tabcontents").classList.remove("hidden");

        document.querySelectorAll(".tab").forEach(tab => {
            tab.classList.remove("tabUnderline");
        });
    } else {
        document.querySelector("#tabcontents").classList.add("hidden");

        document.querySelectorAll(".tab").forEach(tab => {
            tab.classList.add("tabUnderline");
        });
    }
}

function registerEvents() {
    let addMealAddSavedMeal = function() {
        let selectedMeal = document.querySelector("select#addMealSavedMeals");
        
        let amount;
        if(document.querySelector("#addMealSavedMealsNum").value === "x") {
            amount = Number(document.querySelector("#addMealSavedMealsNumArbitrary").value);
        } else {
            amount = Number(document.querySelector("#addMealSavedMealsNum").value);
        }

        let kcal = Number(selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-kcal")) * amount;
        let name = selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-name");
        document.querySelector("#addMealDescription").value += amount + "x " + name + ", ";
        document.querySelector("#addMealTotalKcal").value = Number(document.querySelector("#addMealTotalKcal").value) + kcal;
        
        showToastNotification(`✓ ${amount}x ${name} (${kcal} kcal) added to log entry`);
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
        
        previewMealKcalVal();
        previewIngredientKcalVal();

        showToastNotification(`✓ ${amount}g/ml ${name} (${kcal} kcal) added to log entry`);
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

        previewIngredientKcalVal(false);
        showToastNotification(`✓ ${amount}g/ml ${name} (${kcal} kcal) added to current meal`);
    };

    document.getElementById("addSavedMealAddSavedIngredientBtn").addEventListener("click", addSavedMealAddSavedIngredient);

    document.getElementById("addSavedMealClearBtn").addEventListener("click", function() {
        document.querySelector("#addSavedMealFromIngrName").value = "";
        document.querySelector("#addSavedMealFromIngrTotalKcal").value = 0;
        document.querySelector("#addSavedMealAddSavedIngredientAmount").value = 0;
        previewIngredientKcalVal(false);
    });

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

    document.querySelectorAll("span.delBtn").forEach(deleteButton =>
        deleteButton.addEventListener("click", deleteEntry)
    );

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

    document.querySelectorAll("span.editBtn").forEach(editButton =>
        editButton.addEventListener("click", editEntry)
    );

    let cloneEntry = function() {
        let name = this.getAttribute("data-name");
        let kcal = Number(this.getAttribute("data-kcal"));

        document.querySelector("div#logMealTab").click();

        document.querySelector("input#addMealDescription").value = name;
        document.querySelector("input#addMealTotalKcal").value = kcal;

        document.querySelector("div#logMealTab").scrollIntoView();

        previewIngredientKcalVal();
        previewMealKcalVal();
        
        showToastNotification("✓ Previous meal copied to current log entry");
    };

    document.querySelectorAll("span.cloneBtn").forEach(cloneButton =>
        cloneButton.addEventListener("click", cloneEntry)
    );

    document.querySelector("#clearLogFieldsBtn").addEventListener("click", function() {
        document.querySelector("#addMealDescription").value = "";
        document.querySelector("#addMealTotalKcal").value = 0;
        previewMealKcalVal();
        previewIngredientKcalVal();
        showToastNotification("✓ Meal cleared");
    });

    document.querySelector("#addMealAddSavedIngredientAmount").addEventListener("change", function() {
        previewIngredientKcalVal();
    });
    
    document.querySelector("#addMealAddSavedIngredientAmount").addEventListener("input", function() {
        previewIngredientKcalVal();
    });

    document.querySelector("#addMealSavedIngredients").addEventListener("change", function() {
        previewIngredientKcalVal();
    });

    document.querySelector("#addSavedMealAddSavedIngredientAmount").addEventListener("change", function() {
        previewIngredientKcalVal(false);
    });
    
    document.querySelector("#addSavedMealAddSavedIngredientAmount").addEventListener("input", function() {
        previewIngredientKcalVal(false);
    });

    document.querySelector("#addSavedMealFromIngr").addEventListener("change", function() {
        previewIngredientKcalVal(false);
    });

    document.querySelector("#addMealTotalKcal").addEventListener("change", function() {
        previewMealKcalVal();
        previewIngredientKcalVal();
    });
    
    document.querySelector("#addSavedMealFromIngrTotalKcal").addEventListener("change", function() {
        previewIngredientKcalVal(false);
    });
    
    let addMealSavedMealsNumHandler = function() {
        if(document.querySelector("#addMealSavedMealsNum").value === "x") {
            document.querySelector("#addMealSavedMealsNum").classList.add("hidden");
            document.querySelector("#addMealSavedMealsNumArbitraryContainer").classList.remove("hidden");
            document.querySelector("#addMealSavedMealsNumArbitraryContainer").classList.add("inlineBlock");
        }

        previewMealKcalVal();
    };

    document.querySelector("#addMealSavedMealsNum").addEventListener("change", addMealSavedMealsNumHandler);

    // Also run right away to update when page is loaded
    addMealSavedMealsNumHandler();
    
    document.querySelector("#addMealSavedMeals").addEventListener("change", function() {
        previewMealKcalVal();
    });

    document.querySelector("#addMealSavedMealsNumArbitrary").addEventListener("change", function() {
        previewMealKcalVal();
    });
    
    document.querySelector("#addMealAddSavedMealBtn").addEventListener("click", function() {
        previewMealKcalVal();
        previewIngredientKcalVal();
    });

    let setDateTimeElemToNow = function(dateElem, timeElem) {
        let now = new Date();
        now.setHours(now.getHours()+hourOffset);
        
        document.querySelector(`#${dateElem}`).value = now.getFullYear() + "-" + String(now.getMonth() + 1).padStart(2, "0") + "-" + String(now.getDate()).padStart(2, "0");
        document.querySelector(`#${timeElem}`).value = String(now.getHours()).padStart(2, "0") + ":" + String(now.getMinutes()).padStart(2, "0");
    };

    document.querySelector("#editLogTimestampNow").addEventListener("click", function() {
        setDateTimeElemToNow("editLogDate", "editLogTime"); }
    );

    setDateTimeElemToNow("logCustomDate", "logCustomTime");

    document.querySelector("#calorieGoalExplanationToggler").addEventListener("click", function() {
        document.querySelector("#calorieGoalExplanation").classList.toggle("hidden");
    });

    document.querySelector("#hourOffsetExplanationToggler").addEventListener("click", function() {
        document.querySelector("#hourOffsetExplanation").classList.toggle("hidden");
    });
    
    document.querySelector("#filterBoxExplanationToggler").addEventListener("click", function() {
        document.querySelector("#filterBoxExplanation").classList.toggle("hidden");
    });
    
    document.querySelector("#logCleanupExplanationToggler").addEventListener("click", function() {
        document.querySelector("#logCleanupExplanation").classList.toggle("hidden");
    });
    
    document.querySelector("#logCleanupForm").addEventListener("submit",  function(event) {
        if(!confirm("Are you sure you want to clean the selected log entries? This cannot be undone.")) {
            event.preventDefault();
        }
    });

    document.querySelectorAll("dialog").forEach(dialog => dialog.addEventListener("click", function(event) {
        let rect = this.getBoundingClientRect();
        let isInDialog = (rect.top <= event.clientY && 
                          event.clientY <= rect.top + rect.height && 
                          rect.left <= event.clientX && 
                          event.clientX <= rect.left + rect.width);
        
        if(!isInDialog) {
            this.close();
        }
    }));

    document.querySelector("#logNow").addEventListener("change", function() {
        if(this.checked) {
            document.querySelector("#logCustomDate").disabled = true;
            document.querySelector("#logCustomTime").disabled = true;
        } else {
            document.querySelector("#logCustomDate").disabled = false;
            document.querySelector("#logCustomTime").disabled = false;
        }
    });

    document.querySelector("#logNotNow").addEventListener("change", function() {
        if(this.checked) {
            document.querySelector("#logCustomDate").disabled = false;
            document.querySelector("#logCustomTime").disabled = false;
        } else {
            document.querySelector("#logCustomDate").disabled = true;
            document.querySelector("#logCustomTime").disabled = true;
        }
    });

    document.querySelector("#savedMealsFilterBox").addEventListener("input", function() { 
        filterSelects("savedMealsFilterBox", "addMealSavedMeals");
        previewMealKcalVal();
    });

    document.querySelector("#savedIngredientsFilterBox").addEventListener("input", function() { 
        filterSelects("savedIngredientsFilterBox", "addMealSavedIngredients");
        previewIngredientKcalVal();
    });
    
    document.querySelector("#mealBuilderIngredientsFilterBox").addEventListener("input", function() { 
        filterSelects("mealBuilderIngredientsFilterBox", "addSavedMealFromIngr");
        previewIngredientKcalVal(false);
    });
}

function previewIngredientKcalVal(isLog = true) {
    let selectedIngredient = document.querySelector(isLog ? "#addMealSavedIngredients" : "#addSavedMealFromIngr");
    let amount = Number(document.querySelector(isLog ? "#addMealAddSavedIngredientAmount" : "#addSavedMealAddSavedIngredientAmount").value);
    let mealPreviewer = document.querySelector(isLog ? "#addIngrToMealKcalPreview" : "#addIngrToSavedMealKcalPreview");        
    let kcalPerHundred = Number(selectedIngredient.options[selectedIngredient.selectedIndex].getAttribute("data-kcal"));
    let mealKcalCounter = document.querySelector(isLog ? "#addMealTotalKcal" : "#addSavedMealFromIngrTotalKcal");

    let kcal = Math.ceil((kcalPerHundred / 100) * amount);
    let runningTotal = kcal + Number(mealKcalCounter.value);

    mealPreviewer.innerHTML = `(Adds <b>${kcal}</b> kcal, making the ${isLog ? "log entry" : "meal"} total <b>${runningTotal}</b> kcal)`;
}

function previewMealKcalVal() {
    let selectedMeal = document.querySelector("#addMealSavedMeals");

    let amount = 0;
    
    if(document.querySelector("#addMealSavedMealsNum").value === "x") {
        amount = Number(document.querySelector("#addMealSavedMealsNumArbitrary").value);
    } else {
        amount = Number(document.querySelector("#addMealSavedMealsNum").value);
    }

    let mealPreviewer = document.querySelector("#addMealKcalPreview");        
    let kcal = Number(selectedMeal.options[selectedMeal.selectedIndex].getAttribute("data-kcal"));
    kcal = Math.ceil(kcal * amount);

    let runningTotal = kcal + Number(document.querySelector("#addMealTotalKcal").value);

    mealPreviewer.innerHTML = `(Adds <b>${kcal}</b> kcal, making the log entry total <b>${runningTotal}</b> kcal)`;
}

function filterSelects(filterBox, dropDown) {
    filterBox = document.querySelector(`#${filterBox}`);
    dropDown = document.querySelector(`#${dropDown}`);
    let options = dropDown.querySelectorAll(`option`);
    let firstHit = true;

    if(filterBox.value == "") {
        options.forEach(option => {
            option.disabled = false;
            option.classList.remove("hidden");
        });
        
        dropDown.selectedIndex = 0;
    } else {
        options.forEach(option => {
            option.disabled = !option.getAttribute("data-name").toLowerCase().trim().includes(filterBox.value.toLowerCase());
            
            if(option.disabled) {
                option.classList.add("hidden");
            } else {
                // change selected item to first found so we don't get zombie elements sticking around,
                // plus it's convenient to auto-select when searching
                if(firstHit) {
                    dropDown.value = option.innerText.trim();
                    firstHit = false;
                } else {
                    option.classList.remove("hidden");
                }
            }
        });
    }
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
        } else if(params.get("t") == "collapse") {
            document.querySelector("#collapseTab").click();
        }
    } else {
        if(localStorage.calorificControlPanelShown == "false") { // This feels wrong, but localStorage is a string
            document.querySelector("#collapseTab").click();
        }
    }  
}

function notifyDBChange() {
    let params = new URLSearchParams(document.location.search);

    let component = "";
    if(params.get("t") == "settings") {
        component = "Settings";
    } else if(params.get("t") == "log") {
        component = "Log entry";
    } else if(params.get("t") == "meals") {
        component = "Meal";
    } else if(params.get("t") == "ingredients") {
        component = "Ingredient";
    } else {
        return;
    }

    let action = "";
    if(params.get("saved") == 1) {
        action = "saved";
    } else if(params.get("edited") == 1) {
        action = "edited";
    } else if(params.get("deleted") == 1) {
        action = "deleted";
    } else if(params.get("dbcleaned") == 1) {
        component = "Log";
        action = "cleaned";
    } else {
        return;
    }
    
    showToastNotification(`✓ ${component} ${action}!`);
    history.replaceState({}, "", `?t=${params.get("t")}`);
}

function showToastNotification(text) {
    clearTimeout(toastTimeout);

    let toastNotification = document.getElementById("toastNotification");
    toastNotification.innerHTML = text;
    toastNotification.classList.add("show");
    
    toastTimeout = setTimeout(function(){ 
        toastNotification.classList.remove("show"); 
    }, 5000);
}
