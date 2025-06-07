function goToAddSchedule() {
    window.location.href = 'submit_schedule.php';
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('workoutForm');
    form.addEventListener('submit', function (event) {
        if (!validateForm(event)) {
            event.preventDefault();
        }
    });
});

// Function to validate the form fields
function validateForm(event) {
    const categoriesName = document.getElementById('workout-name').value.trim();
    const categoriesImage = document.getElementById('workout-image').value.trim();
    const categoriesDescription = document.getElementById('workout-description').value.trim();

    if (!categoriesName || !categoriesImage || !categoriesDescription) {
        alert('Please fill in all required fields.');
        event.preventDefault(); // Prevent form submission
        return false;
    }

    alert('Workout added successfully!');
    return true;
}