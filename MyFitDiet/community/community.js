document.addEventListener("DOMContentLoaded", function() {
    // Tab switching function
    document.querySelectorAll(".tab").forEach(tab => {
        tab.addEventListener("click", function() {
            document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
            this.classList.add("active");

            document.querySelectorAll(".post-feed").forEach(feed => feed.classList.remove("active"));
            document.getElementById(this.getAttribute("data-tab")).classList.add("active");
        });
    });

    // Avatar click event
    document.querySelectorAll(".avatar").forEach(avatar => {
        avatar.addEventListener("click", function() {
            const userid = this.getAttribute("data-userid");
            if (confirm("Do you want to view this user's profile?")) {
                window.location.href = `/MyFitDiet/profile/member/view-member.php?profileUserID=${userid}`;
            }
        });
    });

    // Expand/Collapse Comments
    document.querySelectorAll(".show-comments").forEach(button => {
        button.addEventListener("click", function() {
            const postId = this.getAttribute("data-postid");
            const commentsDiv = document.getElementById(`comments-${postId}`);
            if (commentsDiv.style.display === "none") {
                commentsDiv.style.display = "block";
                this.textContent = "Hide comments";
            } else {
                commentsDiv.style.display = "none";
                this.textContent = "Show all comments";
            }
        });
    });

    // File upload preview function
    document.getElementById('file-input').addEventListener('change', function() {
        const fileInput = this;
        const previewImage = document.getElementById('preview-image');
        const fileNameDisplay = document.querySelector('.file-name');
        const cancelButton = document.getElementById('cancel-upload');

        if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                cancelButton.style.display = 'block';
            };

            reader.readAsDataURL(fileInput.files[0]);
            fileNameDisplay.textContent = fileInput.files[0].name;
        } else {
            previewImage.src = '#';
            previewImage.style.display = 'none';
            fileNameDisplay.textContent = 'No file chosen';
            cancelButton.style.display = 'none';
        }
    });

    // Cancel button click event
    document.getElementById('cancel-upload').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('file-input').value = '';
        document.getElementById('preview-image').src = '#';
        document.getElementById('preview-image').style.display = 'none';
        document.querySelector('.file-name').textContent = 'No file chosen';
        this.style.display = 'none';
    });

    // Like function
    document.querySelectorAll(".like-btn").forEach(button => {
        button.addEventListener("click", function() {
            const postId = this.getAttribute("data-postid");
            const likeCountSpan = this.querySelector(".like-count");
            const isLiked = this.classList.contains("liked");
            const action = isLiked ? "unlike" : "like";
            const buttonElement = this; // Save Button Reference
        
            $.ajax({
                url: "like_post.php",
                type: "POST",
                data: { postid: postId, action: action },
                dataType: "json",
                success: function(response) {
                    console.log("AJAX Success:", response); // Add log to check return value
                    if (response.success) {
                        buttonElement.classList.toggle("liked");
                        likeCountSpan.textContent = response.like_count;
                    } else {
                        alert("Failed: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", xhr.responseText); // Debugging AJAX Errors
                    alert("An error occurred. Check console for details.");
                }
            });
        });
    });
    
});