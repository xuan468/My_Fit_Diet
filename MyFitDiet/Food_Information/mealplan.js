document.addEventListener("DOMContentLoaded", function () {
    const currentWeekRangeElement = document.getElementById("currentWeekRange");
    const prevButton = document.getElementById("prevButton");
    const nextButton = document.getElementById("nextButton");
    const goToTodayButton = document.getElementById("goToToday");
    const datePicker = document.getElementById("datePicker");
    const mealPlansContainer = document.getElementById("mealPlansContainer");
    const avgCaloriesDisplay = document.getElementById("avgCaloriesDisplay");

    let currentDate = new Date();

    function formatDate(date) {
        return date.toISOString().split("T")[0]; 
    }

    function getCurrentWeekRange(date) {
        const currentDay = new Date(date);
        const dayOfWeek = currentDay.getDay();
        const diffToMonday = (dayOfWeek === 0 ? -6 : 1) - dayOfWeek;
        const startOfWeek = new Date(currentDay);
        startOfWeek.setDate(currentDay.getDate() + diffToMonday);

        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);

        return { start: startOfWeek, end: endOfWeek };
    }

    function updateWeekRange() {
        const weekRange = getCurrentWeekRange(currentDate);
        const options = { year: "numeric", month: "long", day: "numeric" };
        const startFormatted = weekRange.start.toLocaleDateString(undefined, options);
        const endFormatted = weekRange.end.toLocaleDateString(undefined, options);
    
        currentWeekRangeElement.textContent = `${startFormatted} - ${endFormatted}`;
        updateMealPlanUI(weekRange.start);

        fetchAvgCalories(formatDate(weekRange.start), formatDate(weekRange.end));
        fetchWeeklyGoal(formatDate(weekRange.start));
    }

    function fetchWeeklyGoal(startOfWeek) {
        fetch(`get_weekly_goal.php?start_date=${startOfWeek}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('target_calories').value = data.target_calories;
                document.getElementById('displayedDate').value = startOfWeek;
            })
            .catch(error => console.error("Error fetching weekly goal:", error));
    }

    function fetchAvgCalories(startOfWeek, endOfWeek) {
        fetch(`get_avg_calories.php?start_date=${startOfWeek}&end_date=${endOfWeek}`)
            .then(response => response.json())
            .then(data => {
                let avgCalories = data.success ? data.avg_calories : 0;
                let goalSet = data.goal_set; 
    
                let remainingText;
                let remainingClass = "neutral";  
                
                if (goalSet) {
                    let remainingCalories = data.remaining_calories;
                    let isPositive = remainingCalories >= 0;
                    remainingText = isPositive
                        ? `(Remaining: left ${remainingCalories.toFixed(2)} kcal)`
                        : `(Remaining: over ${(Math.abs(remainingCalories)).toFixed(2)} kcal)`;
                    remainingClass = isPositive ? "positive" : "negative";
                } else {
                    remainingText = "(Haven't set the goal)";
                }
    
                avgCaloriesDisplay.innerHTML = `
                    <div class="avg-calories-container">
                        <span>Weekly Avg Calories: ${avgCalories} kcal</span>
                        <span class="remaining-calories ${remainingClass}">
                            ${remainingText}
                        </span>
                    </div>
                `;
            })
            .catch(error => console.error("Error fetching avg_calories:", error));
    }    
    
    function generateMealCards(formattedDate, mealType) {
        let mealsHTML = "";
        if (mealPlans[formattedDate] && mealPlans[formattedDate][mealType]) {
            mealPlans[formattedDate][mealType].forEach(food => {
                mealsHTML += `
                    <div class="meal-card" data-date="${formattedDate}" data-meal-type="${mealType}" onclick="openPopup('confirm')">
                        <button type="button" class="edit0"></button>
                        <img src="${food.img}" alt="${food.recipename}">
                        <div class="meal-details">
                            <h4>${food.recipename}</h4>
                            <p>Servings: ${food.servings}</p>
                            <p>Calories: ${food.calories * food.servings} kcal</p>
                            <p>Carbs: ${food.carbs * food.servings}g</p>
                            <p>Fats: ${food.fats * food.servings}g</p> 
                            <p>Protein: ${food.protein * food.servings}g</p>
                        </div>
                    </div>`;
            });
        } else {
            mealsHTML = `<p>No meals planned.</p>`;
        }
        return mealsHTML;
    }
    
    function updateMealPlanUI(startDate) {
        mealPlansContainer.innerHTML = "";

        for (let i = 0; i < 7; i++) {
            let currentDay = new Date(startDate);
            currentDay.setDate(currentDay.getDate() + i);
            let formattedDate = formatDate(currentDay);

            let totals = dailyTotals[formattedDate] || { total_calories: 0, carbs: 0, fats: 0, protein: 0 };

            let chartId = `ring-chart-${formattedDate}`;
            mealPlansContainer.innerHTML += `
                <div class="day">
                    <div class="day-header">
                        <h2>${currentDay.toLocaleDateString(undefined, { weekday: "long" })}<br>
                        ${currentDay.toLocaleDateString(undefined, { month: "long", day: "numeric" })}</h2>
                    </div>

                    <div class="day-summary-container">
                        <div id="${chartId}" class="ring-chart"></div>
                        <div class="summary-details">
                            <p>Total Calories: ${totals.total_calories} kcal</p>
                            <p>Carbs: ${totals.carbs.toFixed(1)}%</p>
                            <p>Fats: ${totals.fats.toFixed(1)}%</p>
                            <p>Protein: ${totals.protein.toFixed(1)}%</p>
                        </div>
                    </div>

                    <div class="meals-container">
                        <div class="meal-type">
                            <h3>Breakfast</h3>
                            <div class="meal-list">${generateMealCards(formattedDate, "breakfast")}</div>
                        </div>
                        <div class="meal-type">
                            <h3>Lunch</h3>
                            <div class="meal-list">${generateMealCards(formattedDate, "lunch")}</div>
                        </div>
                        <div class="meal-type">
                            <h3>Dinner</h3>
                            <div class="meal-list">${generateMealCards(formattedDate, "dinner")}</div>
                        </div>
                    </div>
                </div>`;

            setTimeout(() => updateRingChart(chartId, totals), 10);
        }
    }

    function updateRingChart(chartId, totals) {
        let chartElement = document.getElementById(chartId);
        if (!chartElement) return;
    
        let totalSum = totals.carbs + totals.fats + totals.protein;
    
        if (totalSum === 0) {
            chartElement.style.background = "#32cd32";
            chartElement.removeAttribute("data-has-data");
        } else {
            let carbsPercent = (totals.carbs / totalSum) * 100;
            let fatsPercent = (totals.fats / totalSum) * 100;
            let proteinPercent = (totals.protein / totalSum) * 100;
    
            chartElement.style.setProperty("--carbs", `${carbsPercent}%`);
            chartElement.style.setProperty("--fats", `${fatsPercent}%`);
            chartElement.style.setProperty("--protein", `${proteinPercent}%`);
            chartElement.setAttribute("data-has-data", "true");
        }
    }

    prevButton.addEventListener("click", function () {
        currentDate.setDate(currentDate.getDate() - 7);
        updateWeekRange();
        datePicker.value = ""; 
        updateURLParam(currentDate);
    });

    nextButton.addEventListener("click", function () {
        currentDate.setDate(currentDate.getDate() + 7);
        updateWeekRange();
        datePicker.value = ""; 
        updateURLParam(currentDate);
    });

    goToTodayButton.addEventListener("click", function () {
        currentDate = new Date();
        updateWeekRange();
        datePicker.value = ""; 
        updateURLParam(currentDate);
    });

    datePicker.addEventListener("change", function () {
        const selectedDate = new Date(this.value);
        if (!isNaN(selectedDate)) {
            currentDate = selectedDate;
            updateWeekRange();
            updateURLParam(currentDate);
        }
    });

    function updateURLParam(date) {
        const params = new URLSearchParams(window.location.search);
        params.set('date', formatDate(date));
        window.history.replaceState({}, '', `${window.location.pathname}?${params}`);
    }

    updateWeekRange();
});
