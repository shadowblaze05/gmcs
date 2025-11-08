<?php
session_start();
include("../../db.php");

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gcms";

$conn = new mysqli($servername, $username, $password, $dbname);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Management</title>
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
    <h2>Game Management</h2>

    <!-- Search Bar -->
    <div class="search-container">
        <form method="GET" action="">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by Title">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php
    if (isset($_GET['edit'])) {
        $gameId = $_GET['edit'];
        $editQuery = "SELECT * FROM games WHERE game_id = '$gameId'";
        $editResult = $conn->query($editQuery);
        $editData = $editResult->fetch_assoc();
        $edit = true;
    } else {
        $edit = false;
    }
    ?>

    <!-- Add/Edit Game Form -->
    <div class="form-container">
        <h3><?php echo $edit ? 'Edit Game' : 'Add New Game'; ?></h3>

        <form method="POST" enctype="multipart/form-data">
            <!-- Game Image Upload -->
            <label>Game Profile:</label>
            <input type="file" name="game_image" id="game_image" accept="image/*" onchange="previewImage(event)">
            
            <!-- Image Preview -->
            <div id="imagePreview">
                <?php if ($edit && $editData['game_image']): ?>
                    <img src="<?php echo $editData['game_image']; ?>" class="image-preview" alt="Current Game Image">
                <?php endif; ?>
            </div>

            <!-- Game Title -->
            <label>Title:</label>
            <input type="text" name="title" required value="<?php echo $edit ? $editData['title'] : ''; ?>">

            <!-- Release Date -->
            <label>Release Date:</label>
            <input type="date" name="release_date" required value="<?php echo $edit ? $editData['release_date'] : ''; ?>">

            <!-- Developer -->
            <label>Developer:</label>
            <input type="text" name="developer" required value="<?php echo $edit ? $editData['developer'] : ''; ?>">

            <!-- Publisher -->
            <label>Publisher:</label>
            <input type="text" name="publisher" required value="<?php echo $edit ? $editData['publisher'] : ''; ?>">

            <!-- Genre -->
            <label>Genre:</label>
            <input type="text" name="genre" required value="<?php echo $edit ? $editData['genre'] : ''; ?>">

            <!-- Price -->
            <label>Price:</label>
            <input type="number" step="0.01" name="price" required value="<?php echo $edit ? $editData['price'] : ''; ?>">

            <button type="submit" name="<?php echo $edit ? 'update_game' : 'add_game'; ?>">
                <?php echo $edit ? 'Update Game' : 'Add Game'; ?>
            </button>

            <?php if ($edit): ?>
                <input type="hidden" name="game_id" value="<?php echo $editData['game_id']; ?>">
            <?php endif; ?>
        </form>
    </div>

    <?php
    $query = "SELECT * FROM games";
    $games = $conn->query($query);

    $gamesArray = [];
    while ($game = $games->fetch_assoc()) {
        $gamesArray[] = $game;
    }
    ?>

    <div class="grid-container">
        <?php if (count($gamesArray) > 0): ?>
            <?php foreach ($gamesArray as $game): ?>
                <div class="card">
                    <img src="<?php echo $game['game_image']; ?>" alt="Game Image">
                    <h3><?php echo $game['title']; ?></h3>
                    <p>Genre: <?php echo $game['genre']; ?></p>
                    <p>Price: $<?php echo $game['price']; ?></p>

                    <div class="action-buttons">
                        <a href="?edit=<?php echo $game['game_id']; ?>">Edit</a>
                        <a href="?delete=<?php echo $game['game_id']; ?>" onclick="return confirm('Are you sure you want to delete this game?');">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No games found.</p>
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
            var preview = document.getElementById('imagePreview');
            preview.innerHTML = '<img src="' + reader.result + '" class="image-preview" />';
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

</body>
</html>
