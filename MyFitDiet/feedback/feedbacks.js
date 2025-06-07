// Function to select emoji
function selectEmoji(emojiElement) {
    document.querySelectorAll('.emojis img').forEach(img => {
        img.classList.remove('selected');
    });
    emojiElement.classList.add('selected');
    document.getElementById('emojiInput').value = emojiElement.getAttribute('data-emoji'); // Set selected emoji value
}

// Function to toggle category selection
function toggleCategory(buttonElement) {
    buttonElement.classList.toggle('selected');
    updateCategoryInput();
}

// Function to update the category input based on selected categories
function updateCategoryInput() {
    const selectedCategories = Array.from(document.querySelectorAll('.category-buttons button.selected'))
        .map(button => button.getAttribute('data-category'));
    document.getElementById('categoryInput').value = selectedCategories.join(', ');
}

// Attach event listener to form submission
document.getElementById('feedbackForm').onsubmit = function(event) {
    const selectedEmoji = document.getElementById('emojiInput').value;
    const selectedCategories = document.getElementById('categoryInput').value;

    if (!selectedEmoji && !selectedCategories) {
        event.preventDefault();
        alert('Please select at least one emoji or category.');
        return;
    }

    alert('Feedback added successfully!');
};

// Function to handle star rating selection
const stars = document.querySelectorAll('.star');

stars.forEach(star => {
    star.addEventListener('click', function() {
        const ratingValue = this.getAttribute('data-value'); // Save in data-value if click

        // Set selected stars
        stars.forEach(s => {
            s.classList.remove('selected');
        });
        for (let i = 0; i < ratingValue; i++) {
            stars[i].classList.add('selected');
        }

        // Set the hidden rating input value
        document.getElementById('ratingInput').value = ratingValue;
    });
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

    fetch("upload_images.php", {
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

    fetch("upload_images.php", {
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

function submitReply() {
        alert("Submit Successfully！");
}
