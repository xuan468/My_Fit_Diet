document.addEventListener('DOMContentLoaded', function() {
    fetch('fetch_weekly.php')
        .then(response => response.json())
        .then(data => {
            const weeklyProgramTable = document.getElementById('weeklyProgramTable');
            const daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            const workoutMap = {};

            // Populate workoutMap with data from the server
            data.forEach(workout => {
                const workoutDate = new Date(workout.workout_date);
                const dayOfWeek = workoutDate.toLocaleString('en-US', { weekday: 'long' });

                if (!workoutMap[dayOfWeek]) {
                    workoutMap[dayOfWeek] = [];
                }
                workoutMap[dayOfWeek].push(workout);
            });

            Object.keys(workoutMap).forEach(day => {
                workoutMap[day].sort((a, b) => a.workout_time_from.localeCompare(b.workout_time_from));
            });

            daysOfWeek.forEach(day => {
                if (workoutMap[day] && workoutMap[day].length > 0) {
                    workoutMap[day].forEach((workout, index) => {
                        const row = document.createElement('tr');

                        if (index === 0) {
                            row.innerHTML = `
                                <td rowspan="${workoutMap[day].length}">${day}</td>
                                <td><span class="workout-type workout">${workout.workout_name}</span></td>
                                <td><span class="workout-category">${workout.workout_type}</span></td>
                                <td>${workout.workout_time_from} - ${workout.workout_time_to}</td>
                                <td>${workout.workout_notes}</td>
                            `;
                        } else {
                            row.innerHTML = `
                                <td><span class="workout-type workout">${workout.workout_name}</span></td>
                                <td><span class="workout-category">${workout.workout_type}</span></td>
                                <td>${workout.workout_time_from} - ${workout.workout_time_to}</td>
                                <td>${workout.workout_notes}</td>
                            `;
                        }
                        weeklyProgramTable.appendChild(row);
                    });
                } else {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${day}</td>
                        <td><span class="workout-type rest">Rest</span></td>
                        <td><span class="workout-category">Relaxation</span></td>
                        <td>-</td>
                        <td>No workout planned.</td>
                    `;
                    weeklyProgramTable.appendChild(row);
                }
            });
        })
        .catch(error => {
            console.error('Error fetching workout data:', error);
        });
});




function goToAddSchedule() {
    window.location.href = 'submit_schedule.php';
}

function goToAddWorkout() {
    window.location.href = 'submit_workout.php';
}

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