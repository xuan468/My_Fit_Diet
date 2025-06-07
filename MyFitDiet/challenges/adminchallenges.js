document.addEventListener('DOMContentLoaded', () => {
    const editModal = document.getElementById('editModal');
    const deleteModal = document.getElementById('deleteModal');
    const addModal = document.getElementById('addModal');
    const editLevelModal = document.getElementById('editLevelModal');
    const addLevelModal = document.getElementById('addLevelModal');
    const deleteLevelModal = document.getElementById('deleteLevelModal');
    const closeButtons = document.querySelectorAll('.close');
    const editForm = document.getElementById('editForm');
    const deleteForm = document.getElementById('deleteForm');
    const addForm = document.getElementById('addForm');
    const editLevelForm = document.getElementById('editLevelForm');
    const addLevelForm = document.getElementById('addLevelForm');
    const editButtons = document.querySelectorAll('.edit-btn');
    const deleteButtons = document.querySelectorAll('.delete-btn');
    const addChallengeCard = document.getElementById('addChallengeCard');
    const editLevelButtons = document.querySelectorAll('.edit-level-btn');
    const deleteLevelButtons = document.querySelectorAll('.delete-level-btn');
    const addLevelBtn = document.getElementById('addLevelBtn');

    // Open edit modal for challenges - UPDATED TO INCLUDE ALL FIELDS
    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const challengeCard = button.closest('.challenge-card');
            const challengeId = challengeCard.dataset.challengeId;
            
            // Get all values from the challenge card
            const challengeName = challengeCard.querySelector('h3').textContent;
            const description = challengeCard.querySelector('p').textContent;
            const type = challengeCard.querySelector('p:nth-child(3)').textContent.split(': ')[1];
            const difficulty = challengeCard.querySelector('p:nth-child(4)').textContent.split(': ')[1];
            const score = challengeCard.querySelector('p:nth-child(5)').textContent.split(': ')[1];

            // Populate ALL form fields
            document.getElementById('editChallengeId').value = challengeId;
            document.getElementById('challengeName').value = challengeName;
            document.getElementById('description').value = description;
            document.getElementById('type').value = type;
            document.getElementById('difficulty').value = difficulty.toLowerCase();
            document.getElementById('score').value = score;

            // Note: Can't display existing image in file input due to browser security
            // The existing image will be retained if no new image is selected
            
            // Show modal
            editModal.style.display = 'block';
        });
    });

    // Open delete modal for challenges
    deleteButtons.forEach(button => {
        button.addEventListener('click', () => {
            const challengeId = button.dataset.challengeId;
            document.getElementById('deleteChallengeId').value = challengeId;
            deleteModal.style.display = 'block';
        });
    });

    // Open add modal for challenges
    addChallengeCard.addEventListener('click', () => {
        addModal.style.display = 'block';
    });

    // Open edit modal for levels
    editLevelButtons.forEach(button => {
        button.addEventListener('click', () => {
            const levelRow = button.closest('tr');
            const levelId = button.dataset.levelId;
            const startPoint = levelRow.querySelector('td:nth-child(2)').textContent;
            const endPoint = levelRow.querySelector('td:nth-child(3)').textContent;

            // Populate form
            document.getElementById('editLevelId').value = levelId;
            document.getElementById('startPoint').value = startPoint;
            document.getElementById('endPoint').value = endPoint;

            // Show modal
            editLevelModal.style.display = 'block';
        });
    });

    // Open delete level modal
    deleteLevelButtons.forEach(button => {
        button.addEventListener('click', () => {
            const levelRow = button.closest('tr');
            const levelId = button.dataset.levelId;
            const startPoint = levelRow.querySelector('td:nth-child(2)').textContent;
            const endPoint = levelRow.querySelector('td:nth-child(3)').textContent;

            // Populate form
            document.getElementById('deleteLevelId').value = levelId;
            document.getElementById('startPoint').value = startPoint;
            document.getElementById('endPoint').value = endPoint;

            // Show modal
            deleteLevelModal.style.display = 'block';
        });
    });

    // Open add modal for levels
    addLevelBtn.addEventListener('click', () => {
        addLevelModal.style.display = 'block';
    });

    // Close modals
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            editModal.style.display = 'none';
            deleteModal.style.display = 'none';
            addModal.style.display = 'none';
            editLevelModal.style.display = 'none';
            addLevelModal.style.display = 'none';
            deleteLevelModal.style.display = 'none';
        });
    });

    // Close modals if clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === editModal || event.target === deleteModal || event.target === addModal || 
            event.target === editLevelModal || event.target === addLevelModal || event.target === deleteLevelModal) {
            editModal.style.display = 'none';
            deleteModal.style.display = 'none';
            addModal.style.display = 'none';
            editLevelModal.style.display = 'none';
            addLevelModal.style.display = 'none';
            deleteLevelModal.style.display = 'none';
        }
    });

    // Cancel delete
    document.querySelector('.cancel-delete').addEventListener('click', () => {
        deleteModal.style.display = 'none';
    });
});