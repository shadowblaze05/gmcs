<?php
session_start();
include("../../db.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.html");
    exit();
}
$user_id = $_SESSION['user_id'];

$action = "Visited Game management page";
require 'audit.php';

if (isset($_POST['add_game'])) {
    if (isset($_FILES['game_image']) && $_FILES['game_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["game_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (getimagesize($_FILES["game_image"]["tmp_name"]) === false) {
            echo "File is not an image."; exit();
        }

        $allowed_formats = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_formats)) {
            echo "Only JPG, JPEG, PNG, and GIF files are allowed."; exit();
        }

        if (move_uploaded_file($_FILES["game_image"]["tmp_name"], $target_file)) {
            $game_image = $target_file;
            $title = $_POST['title'];
            $release_date = $_POST['release_date'];
            $developer = $_POST['developer'];
            $publisher = $_POST['publisher'];
            $genre = $_POST['genre'];
            $price = $_POST['price'];

            $sql = "INSERT INTO games (game_image, title, release_date, developer, publisher, genre, price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssd", $game_image, $title, $release_date, $developer, $publisher, $genre, $price);
            $stmt->execute();
            $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF']);
            $action = "Added new game: $title";
            require 'audit.php';
            exit();
        } else {
            echo "Sorry, there was an error uploading your file."; exit();
        }
    } else {
        echo "No file was uploaded."; exit();
    }
}

if (isset($_POST['update_game'])) {
    $game_id = $_POST['game_id'];

    if (isset($_FILES['game_image']) && $_FILES['game_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["game_image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (getimagesize($_FILES["game_image"]["tmp_name"]) === false) {
            echo "File is not an image."; exit();
        }

        $allowed_formats = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_formats)) {
            echo "Only JPG, JPEG, PNG, and GIF files are allowed."; exit();
        }

        if (move_uploaded_file($_FILES["game_image"]["tmp_name"], $target_file)) {
            $game_image = $target_file;
        } else {
            echo "Sorry, there was an error uploading your file."; exit();
        }
    } else {
        $game_image = $_POST['existing_image'];
    }

    $title = $_POST['title'];
    $release_date = $_POST['release_date'];
    $developer = $_POST['developer'];
    $publisher = $_POST['publisher'];
    $genre = $_POST['genre'];
    $price = $_POST['price'];

    $sql = "UPDATE games SET game_image=?, title=?, release_date=?, developer=?, publisher=?, genre=?, price=? WHERE game_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssdi", $game_image, $title, $release_date, $developer, $publisher, $genre, $price, $game_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    $action = "Updated game ID: $game_id";
    require 'audit.php';
    exit();
}

if (isset($_GET['delete'])) {
    $game_id = $_GET['delete'];

    $sql = "DELETE FROM games WHERE game_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    $action = "Deleted game ID: $game_id";
    require 'audit.php';
    exit();
}

function getGames($conn, $search = '') {
    $sql = "SELECT * FROM games WHERE title LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$search%"; 
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    return $stmt->get_result();
}

$search = isset($_POST['search']) ? $_POST['search'] : '';
$games = getGames($conn, $search);
$action = "Searched games with query: $search";
require 'audit.php';

$edit = false;
$editData = null;

if (isset($_GET['edit'])) {
    $edit = true;
    $game_id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM games WHERE game_id = $game_id");
    $editData = $result->fetch_assoc();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Management</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        h2 {
            text-align: center;
            color: #1e293b;
            margin-bottom: 20px;
            font-size: 32px;
        }

        h3 {
            color: #1e293b;
            margin-top: 40px;
            font-size: 22px;
        }

        form {
            margin-bottom: 40px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: 600;
            color: #1e293b;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        input[type="file"],
        select {
            width: 100%;
            padding: 10px 12px;
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 15px;
            background-color: #f8fafc;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus {
            border-color: #090493;
            outline: none;
        }

        .submit-btn, .back-button {
            margin-top: 25px;
            background-color: #090493;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover, .back-button:hover {
            background-color: #0d0def;
        }

        .search-bar {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .search-bar input[type="text"] {
            width: 300px;
            padding: 10px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .search-bar button,
        .search-bar a {
            padding: 10px 20px;
            background-color: #090493;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }

        .search-bar button:hover,
        .search-bar a:hover {
            background-color: #0d0def;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        th, td {
            text-align: left;
            padding: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background-color: #1e293b;
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background-color: #f8fafc;
        }

        tr:hover {
            background-color: #e0f2fe;
        }

        table a {
            color: #090493;
            text-decoration: none;
            font-weight: 600;
            margin-right: 8px;
        }

        table a:hover {
            text-decoration: underline;
        }

        .image-preview img {
            margin-top: 10px;
            border-radius: 12px;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Game Management</h2>

    <!-- Search -->
    <form method="POST" class="search-bar">
        <input type="text" name="search" placeholder="Search by Title" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Reset</a>
    </form>

    <!-- Add Game Form -->
    <form method="POST" enctype="multipart/form-data">
        <h3>Add New Game</h3>
        <label>Game Profile:</label>
        <input type="file" name="game_image" id="game_image" required accept="image/*" onchange="previewImage()">
        <div class="image-preview" id="imagePreview"></div>

        <label>Title:</label>
        <input type="text" name="title" required>

        <label>Release Date:</label>
        <input type="date" name="release_date" required>

        <label>Developer:</label>
        <input type="text" name="developer" required>

        <label>Publisher:</label>
        <input type="text" name="publisher" required>

        <label>Genre:</label>
        <input type="text" name="genre" required>

        <label>Price:</label>
        <input type="number" step="0.01" name="price" required>

        <button class="submit-btn" type="submit" name="add_game">Add Game</button>
    </form>

    <!-- Edit Form -->
    <?php if ($edit): ?>
        <form method="POST" enctype="multipart/form-data">
            <h3>Edit Game</h3>
            <input type="hidden" name="game_id" value="<?php echo $editData['game_id']; ?>">
            <input type="hidden" name="existing_image" value="<?php echo $editData['game_image']; ?>">

            <label>Image:</label>
            <input type="file" name="game_image" id="game_image" accept="image/*" onchange="previewImage()">
            <div class="image-preview" id="imagePreview">
                <img src="<?php echo $editData['game_image']; ?>" alt="Game Image" width="200">
            </div>

            <label>Title:</label>
            <input type="text" name="title" value="<?php echo $editData['title']; ?>" required>

            <label>Release Date:</label>
            <input type="date" name="release_date" value="<?php echo $editData['release_date']; ?>" required>

            <label>Developer:</label>
            <input type="text" name="developer" value="<?php echo $editData['developer']; ?>" required>

            <label>Publisher:</label>
            <input type="text" name="publisher" value="<?php echo $editData['publisher']; ?>" required>

            <label>Genre:</label>
            <input type="text" name="genre" value="<?php echo $editData['genre']; ?>" required>

            <label>Price:</label>
            <input type="number" step="0.01" name="price" value="<?php echo $editData['price']; ?>" required>

            <button class="submit-btn" type="submit" name="update_game">Update Game</button>
        </form>
    <?php endif; ?>

    <!-- Game Table -->
    <table>
        <tr>
            <th>Image</th>
            <th>Title</th>
            <th>Release Date</th>
            <th>Developer</th>
            <th>Publisher</th>
            <th>Genre</th>
            <th>Price</th>
            <th>Actions</th>
        </tr>
        <?php while ($game = $games->fetch_assoc()): ?>
        <tr>
            <td><img src="<?php echo $game['game_image']; ?>" width="50" height="50" style="border-radius: 6px;"></td>
            <td><?php echo $game['title']; ?></td>
            <td><?php echo $game['release_date']; ?></td>
            <td><?php echo $game['developer']; ?></td>
            <td><?php echo $game['publisher']; ?></td>
            <td><?php echo $game['genre']; ?></td>
            <td><?php echo $game['price']; ?></td>
            <td>
                <a href="?edit=<?php echo $game['game_id']; ?>">Edit</a>
                <a href="?delete=<?php echo $game['game_id']; ?>" onclick="return confirm('Are you sure you want to delete this game?');">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <br>
    <a href="management.php" class="back-button">Back</a>
</div>

<script>
    function previewImage() {
        const file = document.getElementById('game_image').files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = `<img src="${e.target.result}" alt="Image preview" width="200">`;
        };
        reader.readAsDataURL(file);
    }
</script>

</body>
</html>
