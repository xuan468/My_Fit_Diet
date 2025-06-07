<?php
ob_start();
session_start();
include '../general/dbconn.php';
include '../general/manager-nav.php';

if (isset($_POST['submit'])) {
    if (!is_dir('homepage_ads')) {
        mkdir('homepage_ads', 0777, true);
    }

    $file_name = $_FILES['image']['name'];
    $tempname = $_FILES['image']['tmp_name'];
    $folder = __DIR__ . "/images/" . $file_name;

    if (move_uploaded_file($tempname, $folder)) {
        $query = mysqli_query($connection, "INSERT INTO homepage_ads (file, title) VALUES ('$file_name', '$file_name')");
        $_SESSION['notification'] = "File uploaded successfully";
    } else {
        $_SESSION['notification'] = "File upload failed!";
    }
}

if (isset($_POST['delete'])) {
    if (empty($_POST['imageToDelete'])) {
        $_SESSION['notification'] = "No picture selected to delete!";
    } else {
        $fileToDelete = mysqli_real_escape_string($connection, $_POST['imageToDelete']);
        $checkQuery = mysqli_query($connection, "SELECT * FROM homepage_ads WHERE file='$fileToDelete'");
        if (!$checkQuery) {
            $_SESSION['notification'] = "Database error: " . mysqli_error($connection);
        } elseif (mysqli_num_rows($checkQuery) == 0) {
            $_SESSION['notification'] = "File does not exist in the database!";
        } else {
            $deleteQuery = mysqli_query($connection, "DELETE FROM homepage_ads WHERE file='$fileToDelete'");
            if (!$deleteQuery) {
                $_SESSION['notification'] = "Database error: " . mysqli_error($connection);
            } else {
                $filePath = __DIR__ . "/images/" . basename($fileToDelete);
                if (file_exists($filePath)) {
                    if (unlink($filePath)) {
                        $_SESSION['notification'] = "File deleted successfully";
                    } else {
                        $_SESSION['notification'] = "File deletion failed!";
                    }
                } else {
                    $_SESSION['notification'] = "File does not exist in the directory!";
                }
            }
        }
    }
}

if (isset($_POST['update_title'])) {
    $fileToUpdate = $_POST['imageToUpdate'];
    $newTitle = $_POST['new_title'];
    $updateQuery = mysqli_query($connection, "UPDATE homepage_ads SET title='$newTitle' WHERE file='$fileToUpdate'");
    if ($updateQuery) {
        $_SESSION['notification'] = "Title updated successfully";
    } else {
        $_SESSION['notification'] = "Title update failed!";
    }
}

$widgetQuery = mysqli_query($connection, "SELECT * FROM homepage_widget");
if (!$widgetQuery) {
    die("Database error: " . mysqli_error($connection));
}

if (isset($_POST['add_widget'])) {
    $title = mysqli_real_escape_string($connection, $_POST['widget_title']);
    $page = mysqli_real_escape_string($connection, $_POST['widget_page']);
    $image = $_FILES['widget_image']['name'];
    $tempname = $_FILES['widget_image']['tmp_name'];
    $folder = __DIR__ . "/images/" . $image;

    if (move_uploaded_file($tempname, $folder)) {
        $query = mysqli_query($connection, "INSERT INTO homepage_widget (title, image, page) VALUES ('$title', '$image', '$page')");
        $_SESSION['notification'] = "Widget added successfully";
    } else {
        $_SESSION['notification'] = "Widget addition failed!";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['delete_widget'])) {
    $titleToDelete = mysqli_real_escape_string($connection, $_POST['widgetToDelete']);
    $checkQuery = mysqli_query($connection, "SELECT * FROM homepage_widget WHERE title='$titleToDelete'");
    if (mysqli_num_rows($checkQuery) > 0) {
        $deleteQuery = mysqli_query($connection, "DELETE FROM homepage_widget WHERE title='$titleToDelete'");
        if ($deleteQuery) {
            $_SESSION['notification'] = "Widget deleted successfully";
        } else {
            $_SESSION['notification'] = "Widget deletion failed!";
        }
    } else {
        $_SESSION['notification'] = "Widget does not exist!";
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Homepage</title>
    <link rel="stylesheet" href="css/homepage1.css?v=<?php echo time(); ?>">
    <style>
        .notification {
            display: none;
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            text-align: center;
        }

        .notification.error {
            background-color: #f44336;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            max-height: 80vh;
            overflow-y: auto;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.2);
        }
        .close {
            color: red;
            float: right;
            font-size: 28px;
            cursor: pointer;
        }
        #imagePreview {
            display: none;
            margin-top: 10px;
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div id="notificationPopup" class="notification"></div>

    <header>
        <h1>
        Manager Homepage
        </h1>
    </header>


    <!-- <nav>
        <ul>
            <li><a href="manager_homepage.php">Home</a></li>
            <li><a href="../report/manager_report.php">Report Summary</a></li>
            <li><a href="#">Manage Member</a></li>
            <li><a href="#">Manage Staff</a></li>
            <li><a href="#">Manage Reviewer</a></li>
        </ul>
    </nav> -->

    <button id="editads">Edit Ads</button>
    <button id="manageWidgets">Manage Widgets</button>

    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            
            <form method="POST" enctype="multipart/form-data">
                <h3>Submit Image</h3>
                <input type="file" name="image" id="fileInput" onchange="previewImage(event)">
                <img id="imagePreview">
                <button type="submit" name="submit">Submit</button>
            </form>

            <form method="POST">
                <h3>Delete Image</h3>
                <select name="imageToDelete">
                    <?php
                    $res = mysqli_query($connection, "SELECT * FROM homepage_ads");
                    while ($row = mysqli_fetch_assoc($res)) {
                        echo "<option value='" . $row['file'] . "'>" . $row['file'] . "</option>";
                    }
                    ?>
                </select>
                <button type="submit" name="delete">Delete</button>
            </form>

            <form method="POST">
                <h3>Update Title</h3>
                <select name="imageToUpdate">
                    <?php
                    $res = mysqli_query($connection, "SELECT * FROM homepage_ads");
                    while ($row = mysqli_fetch_assoc($res)) {
                        echo "<option value='" . $row['file'] . "'>" . $row['file'] . "</option>";
                    }
                    ?>
                </select>
                <input type="text" name="new_title" placeholder="New Title" required>
                <button type="submit" name="update_title">Update Title</button>
            </form>
        </div>
    </div>

    <script>
        var modal = document.getElementById("uploadModal");
        var btn = document.getElementById("editads");
        var span = document.getElementsByClassName("close")[0];
        var fileInput = document.getElementById("fileInput");
        var imagePreview = document.getElementById("imagePreview");
        var notificationPopup = document.getElementById("notificationPopup");

        btn.onclick = function() {
            modal.style.display = "block";
        }

        span.onclick = function() {
            modal.style.display = "none";
            imagePreview.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
                imagePreview.style.display = "none";
            }
        }
        
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                imagePreview.src = reader.result;
                imagePreview.style.display = "block";
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        <?php if (isset($_SESSION['notification'])): ?>
            var message = "<?php echo $_SESSION['notification']; ?>";
            notificationPopup.textContent = message;
            notificationPopup.style.display = "block";

            if (message.includes("failed") || message.includes("No picture") || message.includes("does not exist")) {
                notificationPopup.classList.add("error");
            }

            setTimeout(function() {
                notificationPopup.style.display = "none";
            }, 1500);

            <?php unset($_SESSION['notification']); ?>
        <?php endif; ?>
    </script>

    <div class="container">
        <div class="wrapper">
            <?php
            $res = mysqli_query($connection, "SELECT * FROM homepage_ads");
            while($row = mysqli_fetch_assoc($res)) {
            ?>
            <div class="image-container">
                <img src="images/<?php echo $row['file']?>" alt="<?php echo $row['file']?>">
                <div class="image-title">
                    <span><?php echo $row['title'] ? $row['title'] : $row['file']; ?></span>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <div id="widgetModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            
            <form method="POST" enctype="multipart/form-data">
                <h3>Add Widget</h3>
                <input type="text" name="widget_title" placeholder="Widget Title" required>
                <input type="file" name="widget_image" id="widgetImageInput" onchange="previewWidgetImage(event)" required>
                <img id="widgetImagePreview" style="display: none; margin-top: 10px; max-width: 100%; height: auto; border-radius: 5px;">
                <input type="text" name="widget_page" placeholder="(Page ID).php" required>
                <button type="submit" name="add_widget">Add Widget</button>
            </form>

            <form method="POST">
                <h3>Delete Widget</h3>
                <select name="widgetToDelete">
                    <?php
                    $widgets = mysqli_query($connection, "SELECT * FROM homepage_widget");
                    while ($widget = mysqli_fetch_assoc($widgets)) {
                        echo "<option value='" . $widget['title'] . "'>" . $widget['title'] . "</option>";
                    }
                    ?>
                </select>
                <button type="submit" name="delete_widget">Delete Widget</button>
            </form>
        </div>
    </div>

    <script>
        var widgetModal = document.getElementById("widgetModal");
        var manageWidgetsBtn = document.getElementById("manageWidgets");
        var closeWidgetModal = document.getElementsByClassName("close")[1];

        manageWidgetsBtn.onclick = function() {
            widgetModal.style.display = "block";
        }

        closeWidgetModal.onclick = function() {
            widgetModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == widgetModal) {
                widgetModal.style.display = "none";
            }
        }
        
        function previewWidgetImage(event) {
            var reader = new FileReader();
            var widgetImagePreview = document.getElementById("widgetImagePreview");

            reader.onload = function() {
                widgetImagePreview.src = reader.result;
                widgetImagePreview.style.display = "block";
            }

            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            } else {
                widgetImagePreview.style.display = "none";
            }
        }
    </script>

    <div class="quick-access">
        <h2>Quick Access</h2>
    </div>

    <div class="widgets-container">
        <?php
        while ($widget = mysqli_fetch_assoc($widgetQuery)) {
            $widgetpageID = $widget['page'];
            $targetPage = $widgetpageID . ".php"; 
        ?>
        <div class="widget">
            <img src="images/<?php echo $widget['image']; ?>" alt="<?php echo $widget['title']; ?>" class="widget-image">
            <h3><?php echo $widget['title']; ?></h3>
                <button class="widget-button">Click Me</button>
            </a>
        </div>
        <?php } ?>
    </div>

</body>
</html>
<?php
include '../general/footer.php';
?>