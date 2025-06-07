document.addEventListener('DOMContentLoaded', () => {
    const editStaffModal = document.getElementById('editStaffModal');
    const deleteModal = document.getElementById('deleteModal');
    const closeButtons = document.querySelectorAll('.close');
    const editStaffForm = document.getElementById('editStaffForm');
    const deleteForm = document.getElementById('deleteForm');
    const editStaffButtons = document.querySelectorAll('.edit-staff-btn');
    const deleteStaffButtons = document.querySelectorAll('.delete-staff-btn');
    const cancelDeleteButton = document.querySelector('.cancel-delete');

    // Open edit modal for staff
    editStaffButtons.forEach(button => {
        button.addEventListener('click', () => {
            const staffRow = button.closest('tr');
            const staffId = button.dataset.levelId;
            const email = staffRow.querySelector('td:nth-child(2)').textContent;
            const username = staffRow.querySelector('td:nth-child(3)').textContent;
            const role = staffRow.querySelector('td:nth-child(4)').textContent;
            const status = staffRow.querySelector('td:nth-child(5)').textContent;

            // Populate form
            document.getElementById('edituserroleid').value = staffId;
            document.getElementById('email').value = email;
            document.getElementById('username').value = username;
            document.getElementById('role').value = role;
            document.getElementById('status').value = status;

            // Show modal
            editStaffModal.style.display = 'block';
        });
    });

    // Open delete modal for staff
    deleteStaffButtons.forEach(button => {
        button.addEventListener('click', () => {
            const staffId = button.dataset.levelId;
            document.getElementById('userroleid').value = staffId;
            deleteModal.style.display = 'block';
        });
    });

    // Close modals
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            editStaffModal.style.display = 'none';
            deleteModal.style.display = 'none';
        });
    });

    // Close modals if clicking outside
    window.addEventListener('click', (event) => {
        if (event.target === editStaffModal || event.target === deleteModal) {
            editStaffModal.style.display = 'none';
            deleteModal.style.display = 'none';
        }
    });

    // Cancel delete
    if (cancelDeleteButton) {
        cancelDeleteButton.addEventListener('click', () => {
            deleteModal.style.display = 'none';
        });
    }
});