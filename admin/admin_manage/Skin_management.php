<?php
session_start();
include("../../db.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.html");
    exit();
}

$user_id = $_SESSION['user_id'];

$action = "Visited Skin management page";
require 'audit.php';


// Handle adding skin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_skin'])) {
    $game_id = $_POST['game_id'];
    $skin_name = $_POST['skin_name'];
    $rarity = $_POST['rarity'];

    // Check if the skin already exists in the database
    $checkSql = "SELECT * FROM skins WHERE skin_name = '$skin_name'";
    $checkResult = $conn->query($checkSql);

    if ($checkResult && $checkResult->num_rows > 0) {
        echo "Skin with this name already exists!";
        exit();
    }

    // Handle skin image upload
    $skin_image = null;
    if (isset($_FILES['skin_image']) && $_FILES['skin_image']['error'] == 0) {
        $target_dir = "uploads/skins/";
        $target_file = $target_dir . basename($_FILES["skin_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow only image file types
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo "Only JPG, JPEG, PNG & GIF files are allowed."; exit();
        }

        // Move the uploaded file to the server directory
        if (move_uploaded_file($_FILES["skin_image"]["tmp_name"], $target_file)) {
            $skin_image = $target_file;
        } else {
            echo "Error uploading skin image."; exit();
        }
    }

    // Add skin to the skins table
    $sql = "INSERT INTO skins (skin_name, skin_image, rarity) VALUES ('$skin_name', '$skin_image', '$rarity')";
    if ($conn->query($sql) === TRUE) {
        $skin_id = $conn->insert_id; // Get the last inserted skin_id

        // Insert into the game_skin table
        $sqlGameSkin = "INSERT INTO game_skin (skin_id, game_id) VALUES ('$skin_id', '$game_id')";
        if ($conn->query($sqlGameSkin) === TRUE) {
            echo "Skin added successfully.";

            $action = "Added skin $skin_name (ID: $skin_id) to game ID $game_id";
            require 'audit.php';
            
        } else {
            echo "Error adding skin to game_skin: " . $conn->error;
        }
    } else {
        echo "Error adding skin: " . $conn->error;
    }
}

// Handle editing skin
if (isset($_GET['edit_skin'])) {
    $edit_id = $_GET['edit_skin'];
    $sql = "SELECT * FROM skins WHERE skin_id = '$edit_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $editSkin = $result->fetch_assoc();
    }
}

// Handle updating skin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_skin'])) {
    $skin_id = $_POST['skin_id'];
    $skin_name = $_POST['skin_name'];
    $rarity = $_POST['rarity'];

    // Handle image upload
    $skin_image = $editSkin['skin_image']; // Keep the existing skin image
    if (isset($_FILES['skin_image']) && $_FILES['skin_image']['error'] == 0) {
        $target_dir = "uploads/skins/";
        $target_file = $target_dir . basename($_FILES["skin_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow only image file types
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif' ])) {
            echo "Only JPG, JPEG, PNG & GIF files are allowed."; exit();
        }

        // Move the uploaded file to the server directory
        if (move_uploaded_file($_FILES["skin_image"]["tmp_name"], $target_file)) {
            $skin_image = $target_file;
        } else {
            echo "Error uploading skin image."; exit();
        }
    }

    // Update skin
    $sql = "UPDATE skins SET skin_name = '$skin_name', rarity = '$rarity', skin_image = '$skin_image' WHERE skin_id = '$skin_id'";
    if ($conn->query($sql) === TRUE) {
        echo "Skin updated successfully.";

        $action = "Updated skin $skin_name (ID: $skin_id)";
        require 'audit.php';

    } else {
        echo "Error updating skin: " . $conn->error;
    }
}

// Handle deleting skin
if (isset($_GET['delete_skin'])) {
    $delete_id = $_GET['delete_skin'];

    // 🔹 Fetch skin name BEFORE deleting it
    $skinQuery = $conn->query("SELECT skin_name FROM skins WHERE skin_id = '$delete_id'");
    $skinData = $skinQuery->fetch_assoc();
    $skin_name = $skinData ? $skinData['skin_name'] : "Unknown Skin";

    // Delete from game_skin table first to maintain referential integrity
    $sqlGameSkinDelete = "DELETE FROM game_skin WHERE skin_id = '$delete_id'";
    if ($conn->query($sqlGameSkinDelete) === TRUE) {

        // Now delete from skins table
        $sqlDeleteSkin = "DELETE FROM skins WHERE skin_id = '$delete_id'";
        if ($conn->query($sqlDeleteSkin) === TRUE) {

            echo "Skin deleted successfully.";

            // 🔹 Audit log with skin name included
            $action = "Deleted skin '$skin_name' (ID: $delete_id)";
            require 'audit.php';
        } else {
            echo "Error deleting skin: " . $conn->error;
        }
    } else {
        echo "Error deleting skin from game_skin: " . $conn->error;
    }
}


// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql = "SELECT * FROM skins WHERE skin_name LIKE '%$search%'";
    $action = "Searched skins with query: $search";
    require 'audit.php';

} else {
    $sql = "SELECT * FROM skins";
}

$result = $conn->query($sql);
$skins = $result->fetch_all(MYSQLI_ASSOC);

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skin Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: 30px auto;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2c3e50;
            text-align: center;
            font-size: 28px;
        }

        .search-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .search-container input[type="text"] {
            width: 50%;
            padding: 10px;
            border-radius: 25px;
            border: 1px solid #ccc;
            margin-right: 10px;
            font-size: 16px;
        }

        .search-container button {
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border-radius: 25px;
            border: none;
            cursor: pointer;
        }

        .search-container button:hover {
            background-color: #0056b3;
        }

        .form-container {
            background-color: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-container input,
        .form-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-container button {
            width: 100%;
            padding: 15px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #0056b3;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
            text-align: center;
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: scale(1.05);
        }

        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }

        .card h3 {
            font-size: 18px;
            color: #333;
            margin-top: 15px;
        }

        .card p {
            font-size: 14px;
            color: #7f8c8d;
        }

        .action-buttons {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }

        .action-buttons a {
            background-color: #007BFF;
            color: white;
            padding: 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 14px;
        }

        .action-buttons a:hover {
            background-color: #0056b3;
        }

        .image-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Skin Management</h2>

        <!-- Search Bar -->
        <div class="search-container">
            <form method="GET" action="">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by skin name">
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Add/Edit Skin Form -->
        <div class="form-container">
            <h3><?php echo isset($editSkin) ? 'Edit Skin' : 'Add New Skin'; ?></h3>

            <form method="POST" enctype="multipart/form-data">
                <!-- Game Selection -->
                <label>Game Title:</label>
                <select name="game_id" required>
                    <option value="">Select Game</option>
                    <?php
                    $gameResult = $conn->query("SELECT game_id, title FROM games");
                    while ($gameRow = $gameResult->fetch_assoc()) {
                        $selected = isset($editSkin) && $editSkin['game_id'] == $gameRow['game_id'] ? 'selected' : '';
                        echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
                    }
                    ?>
                </select>

                <!-- Skin Name -->
                <label>Skin Name:</label>
                <input type="text" name="skin_name" required value="<?php echo isset($editSkin) ? $editSkin['skin_name'] : ''; ?>">

                <!-- Rarity -->
                <label>Rarity:</label>
                <input type="text" name="rarity" required value="<?php echo isset($editSkin) ? $editSkin['rarity'] : ''; ?>">

                <!-- Skin Image Upload -->
                <label>Skin Image (Optional):</label>
                <input type="file" name="skin_image" accept="image/*" onchange="previewImage(event)">

                <!-- Image Preview -->
                <div id="imagePreview">
                    <?php if (isset($editSkin) && $editSkin['skin_image']): ?>
                        <img src="<?php echo $editSkin['skin_image']; ?>" class="image-preview" alt="Current Skin Image">
                    <?php endif; ?>
                </div>

                <button type="submit" name="<?php echo isset($editSkin) ? 'edit_skin' : 'add_skin'; ?>">
                    <?php echo isset($editSkin) ? 'Update Skin' : 'Add Skin'; ?>
                </button>

                <?php if (isset($editSkin)): ?>
                    <input type="hidden" name="skin_id" value="<?php echo $editSkin['skin_id']; ?>">
                <?php endif; ?>
            </form>
        </div>


        <!-- Skin Display -->
        <div class="skin-card">
            <?php if (count($skins) > 0): ?>
                <div class="grid-container">
                    <?php foreach ($skins as $skin): ?>
                        <div class="card">
                            <img src="<?php echo $skin['skin_image']; ?>" alt="Skin Image">
                            <h3><?php echo $skin['skin_name']; ?></h3>
                            <p>Rarity: <?php echo $skin['rarity']; ?></p>

                            <div class="action-buttons">
                                <a href="?edit_skin=<?php echo $skin['skin_id']; ?>">Edit</a>
                                <a href="?delete_skin=<?php echo $skin['skin_id']; ?>" onclick="return confirm('Are you sure you want to delete this skin?');">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No skins found.</p>
            <?php endif; ?>
        </div>
        <br>
        <!-- Back Button -->
        <a href="management.php" class="back-button" style="text-decoration: none; padding: 10px 20px; background-color:rgb(41, 125, 208); color: white; border-radius: 8px; display: inline-block;">
            Back
        </a>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('imagePreview');
                output.innerHTML = "<img src='" + reader.result + "' class='image-preview'>";
            }
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>

</body>

</html>

<?php $conn->close(); ?>