// Use current date for initialization
let today = new Date();
let currentMonth = today.getMonth(); // Get current month 
let currentYear = today.getFullYear(); // Get current year

let currentEditingWorkoutId = null;
let categoriesData = []; // Store fetched categories for reuse

const monthNames = ["January", "February", "March", "April", "May", "June", 
                    "July", "August", "September", "October", "November", "December"];
const daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];


// Leap year check for February
function isLeapYear(year) {
    return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
}

function updateCalendar() {
    const month = currentMonth;
    const year = currentYear;
    const monthDays = (month === 1 && isLeapYear(year)) ? 29 : daysInMonth[month];
    const plannerBody = document.getElementById('planner-body');
    let rows = '<tr>';
    let dayOfWeek = new Date(year, month, 1).getDay(); 
    dayOfWeek = (dayOfWeek === 0) ? 6 : dayOfWeek - 1;

    // Add empty cells for days before the start of the month
    for (let i = 0; i < dayOfWeek; i++) {
        rows += `<td></td>`;
    }

    // Fill in days for the current month
    for (let day = 1; day <= monthDays; day++) {
        rows += 
            `<td>
                ${day}<br> 
                <div class="activity-box">
                    <span>No Activities</span>
                </div>
            </td>`;

        // After each Sunday (7th day), close the row and start a new one
        if ((day + dayOfWeek) % 7 === 0) {
            rows += '</tr><tr>';
        }
    }

    // Close the last row
    rows += '</tr>';
    plannerBody.innerHTML = rows;

    // Update the calendar header with the correct month and year
    document.getElementById('month-year').textContent = `${monthNames[month]} ${year}`;
}

function nextMonth() {
    if (currentMonth === 11) {
        currentMonth = 0;
        currentYear++;
    } else {
        currentMonth++;
    }
    updateCalendar();
    fetchPlannerData();

    
    const searchInput = document.getElementById('searchUser').value.trim();
    if (searchInput) {
        searchUserWorkouts();
    }
}

function prevMonth() {
    if (currentMonth === 0) {
        currentMonth = 11;
        currentYear--;
    } else {
        currentMonth--;
    }
    updateCalendar();
    fetchPlannerData();

    
    const searchInput = document.getElementById('searchUser').value.trim();
    if (searchInput) {
        searchUserWorkouts();
    }
}

// Redirect to add workout page when button is clicked
function goToAddSchedule() {
    window.location.href = 'submit_schedule.php';
}

function goToAddWorkout(){
    window.location.href = 'submit_workout.php';
}
// Function to fetch and render workout planner data
function fetchPlannerData() {
    fetch('fetch_planner_data.php')
        .then(response => response.json())
        .then(data => {
            const plannerBody = document.getElementById('planner-body');
            let rows = '<tr>';
            let dayOfWeek = new Date(currentYear, currentMonth, 1).getDay();
            let daysInCurrentMonth = (currentMonth === 1 && isLeapYear(currentYear)) ? 29 : daysInMonth[currentMonth];

            dayOfWeek = (dayOfWeek === 0) ? 6 : dayOfWeek - 1;

            for (let i = 0; i < dayOfWeek; i++) {
                rows += `<td></td>`;
            }

            for (let day = 1; day <= daysInCurrentMonth; day++) {
                let dayWorkouts = data.filter(d => {
                    let workoutDate = new Date(d.date);
                    return workoutDate.getDate() === day && workoutDate.getMonth() === currentMonth && workoutDate.getFullYear() === currentYear;
                });

                dayWorkouts.sort((a, b) => a.time_from.localeCompare(b.time_from));

                rows += `<td>${day}<br><div class="activity-box">`;

                if (dayWorkouts.length > 0) {
                    dayWorkouts.forEach((foundDay) => {
                        rows += `
                            <div class="activity-item">
                                <span class="activity ${foundDay.activity_class}">${foundDay.activity_name}</span><br>
                                <span class="category-tag">${foundDay.category}</span><br>
                                <span class="time-tag">${formatTime(foundDay.time_from)} - ${formatTime(foundDay.time_to)}</span><br>
                                <span class="duration-tag">${foundDay.duration}</span><br>
                                <span class="location-tag">${foundDay.location}</span><br>
                                <span class="notes-tag">${foundDay.notes}</span>
                                <div class="action-icons">
                                    <img src="../images/edit.jpg" class="edit-icons" onclick='openEditModal(${JSON.stringify(foundDay)})'>
                                    <img src="../images/delete.png" class="delete-icon" onclick='deleteSchedule(${JSON.stringify(foundDay)})'>
                                </div>
                                <br>
                            </div>
                        `;
                    });
                } else {
                    rows += `<span>No Activities</span>`;
                }

                rows += `</div></td>`;

                if ((day + dayOfWeek) % 7 === 0) {
                    rows += '</tr><tr>';
                }
            }

            rows += '</tr>';
            plannerBody.innerHTML = rows;
        });
}

function openEditModal(activity) {
    currentEditingWorkoutId = activity.workout_id;

    const editWorkoutNameSelect = document.getElementById("editWorkoutName");

    editWorkoutNameSelect.value = activity.activity_name;
    document.getElementById("editWorkoutTimeFrom").value = activity.time_from;
    document.getElementById("editWorkoutTimeTo").value = activity.time_to;
    document.getElementById("editWorkoutDuration").value = activity.duration;
    document.getElementById("editWorkoutLocation").value = activity.location;
    document.getElementById("editWorkoutNotes").value = activity.notes;

    editWorkoutNameSelect.dispatchEvent(new Event("change"));

    document.getElementById("editWorkoutModal").style.display = "block";
}

function closeEditModals() {
    document.getElementById("editWorkoutModal").style.display = "none";
}

function updateWorkout() {
    const timeFrom = document.getElementById("editWorkoutTimeFrom").value;
    const timeTo = document.getElementById("editWorkoutTimeTo").value;
    
    const duration = calculateDuration(timeFrom, timeTo);
    document.getElementById("editWorkoutDuration").value = duration;

    const workoutData = {
        workout_id: currentEditingWorkoutId,
        activity_name: document.getElementById("editWorkoutName").value,
        category: document.getElementById("editWorkoutType").value,
        time_from: timeFrom,
        time_to: timeTo,
        duration: duration, 
        location: document.getElementById("editWorkoutLocation").value,
        notes: document.getElementById("editWorkoutNotes").value
    };

    fetch('update_workout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(workoutData)
    })
    .then(response => response.text())
    .then(data => {
        alert(data);
        closeEditModals();
        location.reload();
    });
}

function calculateDuration(timeFrom, timeTo) {
    const from = new Date("1970-01-01T" + timeFrom + "Z");
    const to = new Date("1970-01-01T" + timeTo + "Z");
    
    let totalMinutes = Math.max((to - from) / (1000 * 60), 0);
    let hours = Math.floor(totalMinutes / 60);
    let minutes = totalMinutes % 60;

    return `${hours} hr ${minutes} min`;
}

document.addEventListener("DOMContentLoaded", function () {
    const editWorkoutNameSelect = document.getElementById("editWorkoutName");
    const editWorkoutTypeSelect = document.getElementById("editWorkoutType");

    editWorkoutNameSelect.addEventListener("change", function () {
        const selectedWorkout = this.value;

        if (selectedWorkout) {
            fetch(`fetch_category_type.php?category=${encodeURIComponent(selectedWorkout)}`)
                .then(response => response.json())
                .then(data => {
                    editWorkoutTypeSelect.innerHTML = ""; 

                    data.category_types.forEach(type => {
                        const option = document.createElement("option");
                        option.value = type;
                        option.textContent = type;
                        editWorkoutTypeSelect.appendChild(option);
                    });

                    if (data.category_types.length === 1) {
                        editWorkoutTypeSelect.value = data.category_types[0];
                    }
                })
                .catch(error => console.error("Error fetching category type:", error));
        }
    });
});


function addLocationPrefix() {
    let inputField = document.getElementById("editWorkoutLocation");
    if (!inputField.value.startsWith("📍")) {
        inputField.value = "📍" + inputField.value;
    }
}

function deleteSchedule(activity) {
    if (confirm("Are you sure you want to delete this schedule?")) {
        fetch('delete_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ workout_id: activity.workout_id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === "success") {
                alert(data.message);
                
                updateCategoryDescription(activity.category);

                fetchPlannerData();
                location.reload();
            } else {
                alert(data.message);
            }
        });
    }
}

function updateCategoryDescription(categoryName) {
    const categoryElements = document.querySelectorAll('.category-tag');
    
    categoryElements.forEach(categoryElement => {
        if (categoryElement.innerText === categoryName) {
            const descriptionElement = categoryElement.closest('.activity-box')
                .querySelector('.category-description');

            if (descriptionElement) {
                descriptionElement.innerText = "No registered workouts";
            }
        }
    });
}


// Fetch workout categories and display them
function fetchCategories() {
    fetch('fetch_categories.php')
        .then(response => response.json())
        .then(data => {
            sessionStorage.setItem('userrole', data.userrole);
            categoriesData = data.categories; 
            displayCategories('all');
            setActiveTab('all');
        });
}

function displayCategories(filter) {
    const categoriesContainer = document.querySelector('.workout-categories');

    let sortedCategories = categoriesData.slice();
    let categoriesHTML = '';

    let userrole = (sessionStorage.getItem('userrole')).toLowerCase();

    sortedCategories.forEach(category => {
        if (filter === 'registered' && !category.category_description.includes('✅')) {
            return;
        }

        let actionButtons = '';

        if (category.is_system == 0 ) {
            actionButtons = `
                <span class="category-actions">
                    <img src="../images/edit.jpg" class="edit-icons" onclick='openEditModals(${JSON.stringify(category)})'>
                    <img src="../images/delete.png" class="delete-icon" onclick='deleteCategory(${category.category_id})'>
                </span>
            `;
            categoriesHTML += `
                <div class="category-box" data-category-id="${category.category_id}">
                    <img src="${category.category_image}" alt="${category.category_name}">
                    <div class="category-title">
                        <p class="category-name">${category.category_name}</p>
                        ${actionButtons}
                    </div>
                    <span>${category.category_description}</span>
                </div>
            `;
        } 
        else if (userrole.toLowerCase() !== 'member') {
            actionButtons = `
                <span class="category-actions">
                    <img src="../images/edit.jpg" class="edit-icons" onclick='openEditModals(${JSON.stringify(category)})'>
                    <img src="../images/delete.png" class="delete-icon" onclick='deleteCategory(${category.category_id})'>
                </span>
            `;
            categoriesHTML += `
                <div class="category-box" data-category-id="${category.category_id}">
                    <img src="${category.category_image}" alt="${category.category_name}">
                    <div class="category-title">
                        <p class="category-name">${category.category_name}</p>
                        ${actionButtons}
                    </div>
                    <span>${category.category_description}</span>
                </div>
            `;
        } else  {
            categoriesHTML += `
                <div class="category-box" data-category-id="${category.category_id}">
                    <img src="${category.category_image}" alt="${category.category_name}">
                    <div class="category-title">
                        <p class="category-name">${category.category_name}</p>
                    </div>
                    <span>${category.category_description}</span>
                </div>
            `;
        }
    });

    categoriesContainer.innerHTML = categoriesHTML;
}


function deleteCategory(category_id) {
    if (confirm("Are you sure you want to delete this category?")) {
        fetch("delete_category.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `category_id=${category_id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Category deleted successfully.");
                location.reload();
            } else {
                alert("Failed to delete category.");
            }
        })
        .catch(error => console.error("Error:", error));
    }
}

function openEditModals(category) {
    document.getElementById('editCategoryID').value = category.category_id;
    document.getElementById('editCategoryName').value = category.category_name;

    const editCategoryTypeInput = document.getElementById('editCategoryType');
    editCategoryTypeInput.value = ""; 

    fetch(`fetch_category_type 2.php?category_id=${category.category_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.single && data.category_types.length === 1) {
                editCategoryTypeInput.value = data.category_types[0]; 
            } else {
                editCategoryTypeInput.innerHTML = data.category_types.map(type => 
                    `<option value="${type}">${type}</option>`
                ).join('');
            }
        })
        .catch(error => console.error("Error fetching category type:", error));

    document.getElementById('editCategoryModal').style.display = 'block';
}


function closeEditModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
}

function submitCategoryEdit() {
    const categoryId = document.getElementById('editCategoryID').value;
    const categoryName = document.getElementById('editCategoryName').value;
    const categoryType = document.getElementById('editCategoryType').value;
    const categoryImage = document.getElementById('editCategoryImage').files[0];

    const formData = new FormData();
    formData.append('category_id', categoryId);
    formData.append('category_name', categoryName);
    formData.append('category_type', categoryType);
    if (categoryImage) {
        formData.append('category_image', categoryImage);
    }

    fetch('update_category.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Category updated successfully!');
            closeEditModal();
            fetchCategories(); 
        } else {
            alert('Error updating category: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}


function setActiveTab(tab) {
    const allCategoriesTab = document.querySelector('.tab.all');
    const registeredTab = document.querySelector('.tab.registered');

    if (tab === 'all') {
        allCategoriesTab.classList.add('active');
        registeredTab.classList.remove('active');
    } else if (tab === 'registered') {
        registeredTab.classList.add('active');
        allCategoriesTab.classList.remove('active');
    }
}

// Function to format time (24h to 12h AM/PM format)
function formatTime(timeStr) {
    if (!timeStr) return "N/A"; // Handle missing time
    let [hour, minute] = timeStr.split(":").map(Number);
    let ampm = hour >= 12 ? "PM" : "AM";
    hour = hour % 12 || 12; // Convert 0 to 12-hour format
    return `${hour}:${minute.toString().padStart(2, "0")}${ampm}`;
}


// Function to handle tab click
function handleTabClick(tab) {
    displayCategories(tab);
    setActiveTab(tab);
}

// Fetch planner data and categories on page load
window.onload = function() {
    updateCalendar();
    fetchPlannerData();
    fetchCategories();
};

document.addEventListener("DOMContentLoaded", function() {
    console.log("Schedules.js loaded");
});

function openImageUpload(position) {
    let uploadForm = document.getElementById("uploadForm"); 
    let positionInput = document.getElementById("positionInput");

    if (uploadForm && positionInput) {
        uploadForm.style.display = "block";  
        positionInput.value = position; 
    } else {
        console.error("Upload form or input field not found!");
    }
}

function closeImageUpload() {
    document.getElementById("uploadForm").style.display = "none";
}

function uploadImage() {
    let formData = new FormData();
    let fileInput = document.getElementById("imageFile");
    let position = document.getElementById("positionInput").value;

    if (fileInput.files.length === 0) {
        alert("Please select an image.");
        return;
    }

    formData.append("image", fileInput.files[0]);
    formData.append("position", position);

    fetch("upload_image.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Image uploaded successfully!");
            closeImageUpload();
            location.reload(); 
        } else {
            alert("Upload failed: " + data.error);
        }
    })
    .catch(error => console.error("Error:", error));
}

document.getElementById("confirmButton").addEventListener("click", function () {
    let formData = new FormData();
    formData.append("image", document.getElementById("imageInput").files[0]);
    formData.append("position", selectedPosition);

    fetch("upload_image.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let imgElement = document.querySelector(`.collage-item[data-position="${data.position}"] img`);
            if (imgElement) {
                imgElement.src = data.image + "?t=" + new Date().getTime(); 
            }
        } else {
            console.error("Upload failed:", data.error);
        }
    })
    .catch(error => console.error("Error:", error));
});

function openWorkoutModal() {
    document.getElementById("workoutModal").style.display = "block";
}

function closeWorkoutModal() {
    document.getElementById("workoutModal").style.display = "none";
}

function submitWorkout() {
    let formData = new FormData();
    formData.append("workoutName", document.getElementById("workoutName").value);
    formData.append("workoutType", document.getElementById("workoutType").value);
    formData.append("workoutDescription", document.getElementById("workoutDescription").value);
    formData.append("workoutImage", document.getElementById("workoutImage").files[0]);

    fetch("add_workout.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Workout added successfully!");
            location.reload();
        } else {
            alert("Error: " + (data.message || "An unknown error occurred.")); 
        }
    })
    .catch(error => alert("Error: " + error)); 
}


function searchUserWorkouts() {
    const searchInput = document.getElementById('searchUser').value.trim();
    if (!searchInput) {
        alert("Please enter a UserID or Username.");
        return;
    }

    fetch(`search_user_workouts.php?query=${encodeURIComponent(searchInput)}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            if (!data.workouts.length && !data.categories.length) {
                alert("No workouts or categories found for this user.");
                return;
            }

            alert(`User found! Showing workouts and categories for: ${searchInput}`);

            renderUserWorkouts(data.workouts);
            renderUserCategories(data.categories, data.fav_exercise);
        })
        .catch(error => {
            console.error("Error fetching user workouts:", error);
            alert("An error occurred while fetching user data.");
        });
}



function renderUserWorkouts(workouts) {
    const plannerBody = document.getElementById('planner-body');
    plannerBody.innerHTML = ''; 
    let rows = '<tr>';
    let daysInCurrentMonth = (currentMonth === 1 && isLeapYear(currentYear)) ? 29 : daysInMonth[currentMonth];
    let dayOfWeek = new Date(currentYear, currentMonth, 1).getDay();
    dayOfWeek = (dayOfWeek === 0) ? 6 : dayOfWeek - 1;

    for (let i = 0; i < dayOfWeek; i++) {
        rows += `<td></td>`;
    }

    for (let day = 1; day <= daysInCurrentMonth; day++) {
        let dayWorkouts = workouts.filter(d => {
            let workoutDate = new Date(d.workout_date);
            return workoutDate.getDate() === day && workoutDate.getMonth() === currentMonth && workoutDate.getFullYear() === currentYear;
        });

        dayWorkouts.sort((a, b) => a.time_from.localeCompare(b.time_from));

        rows += `<td>${day}<br><div class="activity-box">`;

        if (dayWorkouts.length > 0) {
            dayWorkouts.forEach((foundDay) => {
                rows += `
                    <div class="activity-item">
                        <span class="activity ${foundDay.activity_class}">${foundDay.activity_name}</span><br>
                        <span class="category-tag">${foundDay.category}</span><br>
                        <span class="time-tag">${formatTime(foundDay.time_from)} - ${formatTime(foundDay.time_to)}</span><br>
                        <span class="duration-tag">${foundDay.duration}</span><br>
                        <span class="location-tag">${foundDay.location}</span><br>
                        <span class="notes-tag">${foundDay.notes}</span>
                    </div>
                `;
            });
        } else {
            rows += `<span>No Activities</span>`;
        }

        rows += `</div></td>`;

        if ((day + dayOfWeek) % 7 === 0) {
            rows += '</tr><tr>';
        }
    }

    rows += '</tr>';
    plannerBody.innerHTML = rows;
}

function renderUserCategories(categories) {
    const categoriesContainer = document.querySelector('.workout-categories');
    categoriesContainer.innerHTML = ''; 

    if (categories.length === 0) {
        categoriesContainer.innerHTML = '<p>No workout categories found.</p>';
        return;
    }

    categories.forEach(category => {
        const categoryDiv = document.createElement('div');
        categoryDiv.classList.add('category-box');

        categoryDiv.innerHTML = `
            <img src="${category.category_image}" alt="${category.category_name}">
            <p>${category.category_name}</p>
            <span>${category.category_description}</span>
        `;
        categoriesContainer.appendChild(categoryDiv);
    });
}






