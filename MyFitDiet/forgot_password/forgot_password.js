function checkEmail() {
    let email = document.getElementById("email").value;

    fetch("forgot_password.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "action=check_email&email=" + encodeURIComponent(email)
    })
    .then(response => response.text())
    .then(data => {
        if (data === "exists") {
            document.getElementById("step1").style.display = "none";
            document.getElementById("step2").style.display = "block";
            document.getElementById("hiddenEmail").value = email;
        } else {
            document.getElementById("emailError").textContent = "Email not found!";
        }
    });
}

function verifySecurity() {
    let email = document.getElementById("hiddenEmail").value;
    let parent_name = document.getElementById("parent_name").value;
    let birthday = document.getElementById("birthday").value;
    let favorite_food = document.getElementById("favorite_food").value;

    fetch("forgot_password.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=verify_security&email=${encodeURIComponent(email)}&parent_name=${encodeURIComponent(parent_name)}&birthday=${encodeURIComponent(birthday)}&favorite_food=${encodeURIComponent(favorite_food)}`
    })
    .then(response => response.text())
    .then(data => {
        if (data === "verified") {
            document.getElementById("step2").style.display = "none";
            document.getElementById("step3").style.display = "block";
            document.getElementById("hiddenEmail2").value = email;
        } else {
            document.getElementById("securityError").textContent = "Security answers incorrect!";
        }
    });
}

function resetPassword() {
    let email = document.getElementById("hiddenEmail2").value;
    let new_password = document.getElementById("new_password").value;

    fetch("forgot_password.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `action=reset_password&email=${encodeURIComponent(email)}&new_password=${encodeURIComponent(new_password)}`
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("resetMessage").textContent = data === "success" ? "Password reset successfully!" : "Error resetting password.";
    });
}
