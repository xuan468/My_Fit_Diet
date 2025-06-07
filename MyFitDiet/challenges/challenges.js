document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality - matches new design
    const sidebar = document.querySelector(".achievements-sidebar");
    const openButton = document.getElementById("profileBtn");
    const closeButton = document.querySelector(".close-sidebar");

    // Open sidebar when profile button is clicked
    openButton.addEventListener("click", function(e) {
        e.stopPropagation();
        console.log("Profile button clicked!");
        sidebar.classList.add("active");
    });

    // Close sidebar when the close button is clicked
    closeButton.addEventListener("click", function() {
        closeSidebar();
    });

    // Close sidebar when clicking outside of it
    document.addEventListener("click", function(event) {
        if (!sidebar.contains(event.target) && event.target !== openButton) {
            closeSidebar();
        }
    });

    // Function to close the sidebar
    function closeSidebar() {
        sidebar.classList.remove("active");
    }

    // Modal functionality for leaving a challenge - matches new design
    const leaveModal = document.getElementById('leaveModal');
    const modalCancel = document.querySelector('.modal-cancel');
    const modalClose = document.querySelector('.modal-close');

    // Handle the leave button click
    document.querySelectorAll('.leave-btn').forEach(button => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const challengeCard = event.target.closest('.challenge-card');
            
            // Extract data from the clicked challenge
            const name = challengeCard.querySelector('h3').textContent;
            const description = challengeCard.querySelector('.challenge-description').textContent;
            const progressPercent = challengeCard.querySelector('.progress-percent').textContent;
            const ucid = button.getAttribute('data-ucid');
            
            // Get challenge ID and score (handle both ongoing and available challenges)
            const challengeIdInput = challengeCard.querySelector('input[name="challengeid"]');
            const challengeId = challengeIdInput ? challengeIdInput.value : '';
            
            // Extract score from points badge or form data
            let score = '0';
            const pointsBadge = challengeCard.querySelector('.points-badge');
            if (pointsBadge) {
                score = pointsBadge.textContent.match(/\d+/)[0]; // Extract numeric value
            }

            // Populate modal with data
            document.getElementById('modalChallengeName').textContent = name;
            document.querySelector('.modal-header p').textContent = description;
            document.getElementById('modalUcid').value = ucid;
            document.getElementById('modalChallengeId').value = challengeId;
            document.getElementById('modalPointsLoss').textContent = `-${score} points`;
            
            // Update progress display
            const progress = parseFloat(progressPercent.replace('%', '')) / 100;
            const modalProgress = leaveModal.querySelector('.progress-percent');
            const progressRing = leaveModal.querySelector('.progress-ring-fill');
            
            modalProgress.textContent = progressPercent;
            
            // Update the progress ring
            const radius = 36;
            const circumference = 2 * Math.PI * radius;
            progressRing.style.strokeDasharray = circumference;
            progressRing.style.strokeDashoffset = circumference * (1 - progress);

            // Display modal
            leaveModal.classList.add('active');
        });
    });

    // Close modal when buttons are clicked
    modalCancel.addEventListener('click', () => {
        leaveModal.classList.remove('active');
    });

    modalClose.addEventListener('click', () => {
        leaveModal.classList.remove('active');
    });

    // Close modal when clicking outside the modal content
    window.addEventListener('click', (event) => {
        if (event.target === leaveModal) {
            leaveModal.classList.remove('active');
        }
    });

    // Filter challenges by difficulty - matches new design
    const difficultyFilter = document.getElementById('difficultyFilter');
    const challenges = document.querySelectorAll('.challenges-grid .challenge-card:not(.active)'); // Only available challenges

    // Listen for changes to the difficulty dropdown filter
    difficultyFilter.addEventListener('change', function() {
        const selectedDifficulty = this.value;
        
        challenges.forEach(challenge => {
            if (selectedDifficulty === 'all' || challenge.getAttribute('data-difficulty') === selectedDifficulty) {
                challenge.style.display = 'flex';
            } else {
                challenge.style.display = 'none';
            }
        });
    });

    // Tab functionality for sidebar - matches new design
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    // Add event listener to each tab button
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const target = button.getAttribute('data-tab');

            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and target content
            button.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });

    // Browse challenges button functionality
    const browseBtn = document.querySelector('.browse-btn');
    if (browseBtn) {
        browseBtn.addEventListener('click', function() {
            document.querySelector('.challenges-section:nth-of-type(2)').scrollIntoView({
                behavior: 'smooth'
            });
        });
    }

    // Initialize circular progress bars
    initProgressCircles();
});

function initProgressCircles() {
    const progressRings = document.querySelectorAll('.progress-ring-fill');
    
    progressRings.forEach(ring => {
        const radius = ring.getAttribute('r');
        const circumference = 2 * Math.PI * radius;
        ring.style.strokeDasharray = circumference;
    });
}