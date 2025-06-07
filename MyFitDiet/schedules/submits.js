// Function to validate the form fields
function validateForm(event) {
    const workoutName = document.getElementById('workout-name').value.trim();
    const workoutType = document.getElementById('workout-type').value.trim();
    const workoutDate = document.getElementById('workout-date').value.trim();
    const workoutTimeFrom = document.getElementById('workout-time-from').value.trim();
    const workoutTimeTo = document.getElementById('workout-time-to').value.trim();
    const workoutLocation = document.getElementById('workout-location').value.trim();
    const durationInput = document.getElementById('workout-duration').value.trim();

    if (!workoutName || !workoutType || !workoutDate || !workoutTimeFrom || !workoutTimeTo || !workoutLocation) {
        alert('Please fill in all required fields.');
        event.preventDefault(); // Prevent form submission
        return false;
    }

    if (!durationInput) {
        alert("Please ensure a valid start and end time are selected.");
        event.preventDefault();
        return false;
    }

    alert('Workout added successfully!');
    return true;
}

// Function to calculate duration between selected times
function calculateDuration() {
    const startTimeInput = document.getElementById("workout-time-from").value;
    const endTimeInput = document.getElementById("workout-time-to").value;
    const durationInput = document.getElementById("workout-duration");

    if (startTimeInput && endTimeInput) {
        const startTime = new Date(`1970-01-01T${startTimeInput}:00`);
        const endTime = new Date(`1970-01-01T${endTimeInput}:00`);

        if (endTime > startTime) {
            let duration = (endTime - startTime) / 60000; // Convert milliseconds to minutes
            const hours = Math.floor(duration / 60);
            const minutes = duration % 60;

            if(hours > 0 && minutes > 0) {
                durationInput.value = `${hours} hr ${minutes} min`;
            } else if (hours > 0) {
            durationInput.value = `${hours} hr`;
            } else {
            durationInput.value = `${minutes} min`;
            }
        }else{
            alert("End time must be after start time.");
            durationInput.value = "";
        }
    }
}

// Attach event listeners
document.addEventListener("DOMContentLoaded", function() {
    const startTimeInput = document.getElementById("workout-time-from");
    const endTimeInput = document.getElementById("workout-time-to");

    startTimeInput.addEventListener("change", calculateDuration);
    endTimeInput.addEventListener("change", calculateDuration);

    const form = document.getElementById('workoutForm');
    if (form) {
        form.addEventListener("submit", validateForm);
    }
});

function goToAddWorkout() {
    window.location.href = 'submit_workout.php';
}

document.addEventListener("DOMContentLoaded", function () {
    const workoutNameSelect = document.getElementById("workout-name");
    const workoutTypeSelect = document.getElementById("workout-type");

    workoutNameSelect.addEventListener("change", function () {
        const selectedWorkout = this.value;

        if (selectedWorkout) {
            fetch(`fetch_category_type.php?category=${encodeURIComponent(selectedWorkout)}`)
                .then(response => response.json())
                .then(data => {
                    workoutTypeSelect.innerHTML = ""; 

                    if (data.single) {
                        workoutTypeSelect.innerHTML = `<option value="${data.category_types[0]}">${data.category_types[0]}</option>`;
                    } else {
                        workoutTypeSelect.innerHTML = '<option value="">Select workout type</option>';
                        data.category_types.forEach(type => {
                            workoutTypeSelect.innerHTML += `<option value="${type}">${type}</option>`;
                        });
                    }
                })
                .catch(error => console.error("Error fetching category type:", error));
        } else {
            workoutTypeSelect.innerHTML = '<option value="">Select workout type</option>';
            workoutTypeSelect.disabled = false;
        }
    });
});

function addLocationPrefix() {
    let inputField = document.getElementById("workout-location");
    if (!inputField.value.startsWith("📍")) {
        inputField.value = "📍" + inputField.value;
    }
}


