function showObject(objectId, button) {
    document.querySelectorAll('.object').forEach(obj => {
        obj.classList.remove('active');
    });

    document.getElementById(objectId).classList.add('active');

    document.querySelectorAll('button').forEach(btn => {
        btn.classList.remove('selected');
    });

    if (button) {
        button.classList.add('selected');
    }
}
function showObjects(objectIds, button) {
    document.querySelectorAll('.object').forEach(obj => {
        obj.classList.remove('active');
    });


    objectIds.forEach(objectId => {
        const object = document.getElementById(objectId);
        if (object) {
            object.classList.add('active');
        }
    });

    document.querySelectorAll('button').forEach(btn => {
        btn.classList.remove('selected');
    });

    if (button) {
        button.classList.add('selected');
    }
}

function showAllObjects(button) {

    document.querySelectorAll('.object').forEach(obj => {
        obj.classList.add('active');
    });

    document.querySelectorAll('button').forEach(btn => {
        btn.classList.remove('selected');
    });

    if (button) {
        button.classList.add('selected');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    showAllObjects(document.querySelector('button[data-action="showAll"]'));
});

function showObject(objectId, button) {
    document.querySelectorAll('.object').forEach(obj => {
        obj.classList.remove('active');
    });

    document.getElementById(objectId).classList.add('active');

    document.querySelectorAll('button').forEach(btn => {
        btn.classList.remove('selected');
    });

    if (button) {
        button.classList.add('selected');
    }
}

function showObjects(objectIds, button) {
    document.querySelectorAll('.object').forEach(obj => {
        obj.classList.remove('active');
    });

    objectIds.forEach(objectId => {
        const object = document.getElementById(objectId);
        if (object) {
            object.classList.add('active');
        }
    });

    document.querySelectorAll('button').forEach(btn => {
        btn.classList.remove('selected');
    });

    if (button) {
        button.classList.add('selected');
    }
}

function showAllObjects(button) {
    document.querySelectorAll('.object').forEach(obj => {
        obj.classList.add('active');
    });

    document.querySelectorAll('button').forEach(btn => {
        btn.classList.remove('selected');
    });

    if (button) {
        button.classList.add('selected');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    showAllObjects(document.querySelector('button[data-action="showAll"]'));
});