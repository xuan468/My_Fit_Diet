<?php
$role = $_SESSION['userrole'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .footer-container {
            font-family: Arial, sans-serif;
            width: 100%;
            color: black;
            background-color: #f8f8f8;
            position: relative;
        }

        .footer-container.stick-to-bottom {
            position: absolute;
            bottom: 0;
            left: 0;
        }

        .footer {
            padding: 0px 50px;
            display: flex;
            justify-content: center;
            gap: 50px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .footer h3 {
            font-size: 16px;
            margin-bottom: 10px;
            text-align: left;
        }

        .footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer ul li {
            margin-bottom: 5px;
        }

        .footer ul li a {
            text-decoration: none;
            color: black;
        }

        .footer ul li a:hover {
            text-decoration: underline;
        }

        .links {
            flex: 0;
            text-align: left;
        }

        .contact-info {
            flex: 1;
            margin-left: 100px;
        }

        .contact {
            display: flex;
            flex-direction: row;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 80px;
            margin-top: 10px;
        }

        .contact-section {
            text-align: left;
        }

        .contact-info p {
            margin: 5px 0;
        }

        .footer-bottom {
            text-align: center;
            padding: 10px 0;
            background-color: #f8f8f8;
            font-size: 12px;
            border-top: 1px solid #ddd;
        }

        .footer-bottom a {
            text-decoration: none;
            color: black;
            margin: 0 10px;
        }

        .footer-bottom a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<footer class="footer-container">
    <div class="footer">
        <div class="links">
            <h3>LINKS</h3>
            <ul>
                <?php if ($role == 'admin') { ?>
                    <li><a href="">HOMEPAGE</a></li>
                    <li><a href="">DASHBOARD</a></li>
                    <li><a href="">DIET PLAN</a></li>
                    <li><a href="">CHALLENGES</a></li>
                <?php } elseif ($role == 'user') { ?>
                    <li><a href="">HOMEPAGE</a></li>
                    <li><a href="">DASHBOARD</a></li>
                    <li><a href="">DIET PLAN</a></li>
                    <li><a href="">CHALLENGES</a></li>
                <?php } else { ?>
                    <li><a href="">HOMEPAGE</a></li>
                    <li><a href="">DASHBOARD</a></li>
                    <li><a href="">DIET PLAN</a></li>
                    <li><a href="">CHALLENGES</a></li>
                <?php } ?>
            </ul>
        </div>
        <div class="contact-info">
            <h3>CONTACT US</h3>
            <div class="contact">
                <div class="contact-us contact-section">
                    <strong>ADDRESS:</strong>
                    <p>123 Jalan Delima, <br>Taman Bukit Indah, <br>55100 Kuala Lumpur, <br>Malaysia</p>
                </div>
                <div class="business-hour contact-section">
                    <strong>BUSINESS HOUR:</strong>
                    <p>Monday to Friday: 9:00 AM - 6:00 PM<br>
                       Saturday: 10:00 AM - 4:00 PM<br>
                       Sunday: Closed</p>
                </div>
                <div class="email-phone contact-section">
                    <strong>EMAIL:</strong>
                    <p>support@myfitdiet.my</p>
                    <strong>PHONE:</strong>
                    <p>+60 3-1234 5678</p>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        © 2025 My Fit Diet. All rights reserved. |
        <a href="#">Privacy Policy</a> |
        <a href="#">Terms of Service</a>
    </div>
</footer>

<script>
    function checkFooterPosition() {
        const footer = document.querySelector('.footer-container');
        const bodyHeight = document.body.scrollHeight;
        const windowHeight = window.innerHeight;

        if (bodyHeight < windowHeight) {
            footer.classList.add('stick-to-bottom');
        } else {
            footer.classList.remove('stick-to-bottom');
        }
    }
    window.addEventListener('load', checkFooterPosition);
    window.addEventListener('resize', checkFooterPosition);
</script>

</body>
</html>