<?php
session_start();
include("../db.php");

if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'user') {
    header("Location: ../index.html");
    exit();
} 

function getCollection($conn) {
    $user_id = $_SESSION['user_id']; // Get logged-in user ID

    $sql = "
        SELECT user_game_collection.*, 
               users.username AS user_name, 
               games.title AS game_title, 
               skins.skin_name, 
               reviews.review_text
        FROM user_game_collection
        JOIN users ON user_game_collection.user_id = users.user_id
        JOIN games ON user_game_collection.game_id = games.game_id
        JOIN skins ON user_game_collection.skin_id = skins.skin_id
        LEFT JOIN reviews ON user_game_collection.review_id = reviews.review_id
        WHERE user_game_collection.user_id = $user_id
    ";
    return $conn->query($sql);
}

$update = false; // Initialize the update variable
$editData = null; // Initialize edit data

// Check if an edit action is triggered
if (isset($_GET['edit_collection'])) {
    $update = true;
    $collection_id = $_GET['edit_collection'];
    
    // Fetch the collection data to pre-fill the edit form
    $sql = "SELECT * FROM user_game_collection WHERE collection_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $collection_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editData = $result->fetch_assoc();
    $stmt->close();
}

// Add Collection
if (isset($_POST['add_collection'])) {
    $user_id = $_SESSION['user_id']; // Get logged-in user ID
    $game_id = $_POST['game_id'];
    $skin_id = $_POST['skin_id'];
    $download_date = $_POST['download_date']; 
    $review_id = $_POST['review_id'];

    $sql = "INSERT INTO user_game_collection (user_id, game_id, skin_id, download_date, review_id) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisi", $user_id, $game_id, $skin_id, $download_date, $review_id); 
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Update Collection
if (isset($_POST['update_collection'])) {
    $collection_id = $_POST['collection_id'];
    $user_id = $_SESSION['user_id']; // Get logged-in user ID
    $game_id = $_POST['game_id'];
    $skin_id = $_POST['skin_id'];
    $download_date = $_POST['download_date']; 
    $review_id = $_POST['review_id'];

    $sql = "UPDATE user_game_collection SET user_id=?, game_id=?, skin_id=?, download_date=?, review_id=? WHERE collection_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiisis", $user_id, $game_id, $skin_id, $download_date, $review_id, $collection_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete Collection
if (isset($_GET['delete_collection'])) {
    $collection_id = $_GET['delete_collection'];
    $user_id = $_SESSION['user_id']; // Get logged-in user ID

    $sql = "DELETE FROM user_game_collection WHERE collection_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $collection_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$collection = getCollection($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Management</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; margin: 20px; }
        .container { width: 90%; margin: auto; background: white; padding: 20px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); border-radius: 8px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background: #007BFF; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .back-button, .submit-btn { margin-top: 20px; padding: 10px 20px; background: #007BFF; color: white; border: none; cursor: pointer; border-radius: 5px; font-size: 16px; }
        .back-button:hover, .submit-btn:hover { background: #0056b3; }
        form { margin-top: 20px; text-align: left; }
        form input[type="text"], form input[type="number"], form select { width: 100%; padding: 8px; margin: 5px 0; }
    </style>
</head>
<body>

<div class="container">
    <h2>Collection Management</h2>
    <form method="POST">
        <h3>Add New Collection</h3>
        <label>User:</label>
        <select name="user_id" required>
            <?php
            $userResult = $conn->query("SELECT user_id, username FROM users");
            while ($userRow = $userResult->fetch_assoc()) {
                echo "<option value='" . $userRow['user_id'] . "'>" . $userRow['username'] . "</option>";
            }
            ?>
        </select>
        <label>Game Title:</label>
        <select name="game_id" required>
            <?php
            $gameResult = $conn->query("SELECT game_id, title FROM games");
            while ($gameRow = $gameResult->fetch_assoc()) {
                echo "<option value='" . $gameRow['game_id'] . "'>" . $gameRow['title'] . "</option>";
            }
            ?>
        </select>
        <label>Skin:</label>
        <select name="skin_id" required>
            <?php
            $skinResult = $conn->query("SELECT skin_id, skin_name FROM skins");
            while ($skinRow = $skinResult->fetch_assoc()) {
                echo "<option value='" . $skinRow['skin_id'] . "'>" . $skinRow['skin_name'] . "</option>";
            }
            ?>
        </select>
        <label>Download Date:</label><input type="date" name="download_date" required> <!-- Changed label -->
        <label>Review:</label>
        <select name="review_id" required>
            <?php
            $reviewResult = $conn->query("SELECT review_id, review_text FROM reviews");
            while ($reviewRow = $reviewResult->fetch_assoc()) {
                echo "<option value='" . $reviewRow['review_id'] . "'>" . $reviewRow['review_text'] . "</option>";
            }
            ?>
        </select><br>
        <button class="submit-btn" type="submit" name="add_collection">Add Collection</button>
    </form>

    <?php if ($update): ?>
    <form method="POST">
        <h3>Edit Collection</h3>
        <input type="hidden" name="collection_id" value="<?php echo $editData['collection_id']; ?>">
        <label>User:</label>
        <select name="user_id" required>
            <?php
            $userResult = $conn->query("SELECT user_id, username FROM users");
            while ($userRow = $userResult->fetch_assoc()) {
                $selected = ($editData['user_id'] == $userRow['user_id']) ? "selected" : "";
                echo "<option value='" . $userRow['user_id'] . "' $selected>" . $userRow['username'] . "</option>";
            }
            ?>
        </select>
        <label>Game Title:</label>
        <select name="game_id" required>
            <?php
            $gameResult = $conn->query("SELECT game_id, title FROM games");
            while ($gameRow = $gameResult->fetch_assoc()) {
                $selected = ($editData['game_id'] == $gameRow['game_id']) ? "selected" : "";
                echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
            }
            ?>
        </select>
        <label>Skin:</label>
        <select name="skin_id" required>
            <?php
            $skinResult = $conn->query("SELECT skin_id, skin_name FROM skins");
            while ($skinRow = $skinResult->fetch_assoc()) {
                $selected = ($editData['skin_id'] == $skinRow['skin_id']) ? "selected" : "";
                echo "<option value='" . $skinRow['skin_id'] . "' $selected>" . $skinRow['skin_name'] . "</option>";
            }
            ?>
        </select>
        <label>Download Date:</label><input type="date" name="download_date" required value="<?php echo $editData['download_date']; ?>"> 
        <label>Review:</label>
        <select name="review_id" required>
            <?php
            $reviewResult = $conn->query("SELECT review_id, review_text FROM reviews");
            while ($reviewRow = $reviewResult->fetch_assoc()) {
                $selected = ($editData['review_id'] == $reviewRow['review_id']) ? "selected" : "";
                echo "<option value='" . $reviewRow['review_id'] . "' $selected>" . $reviewRow['review_text'] . "</option>";
            }
            ?>
        </select><br>
        <button class="submit-btn" type="submit" name="update_collection">Update Collection</button>
    </form>
    <?php endif; ?>

    <table>
        <tr>
            <th>Collection ID</th>
            <th>User</th>
            <th>Game Title</th>
            <th>Skin</th>
            <th>Download Date</th> 
            <th>Review</th>
            <th>Action</th>
        </tr>
        <?php if ($collection->num_rows > 0): ?>
            <?php while($row = $collection->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row["collection_id"]; ?></td>
                    <td><?php echo $row["user_name"]; ?></td>
                    <td><?php echo $row["game_title"]; ?></td>
                    <td><?php echo $row["skin_name"]; ?></td>
                    <td><?php echo $row["download_date"]; ?></td> 
                    <td><?php echo $row["review_text"]; ?></td>
                    <td>
                        <a href="?edit_collection=<?php echo $row['collection_id']; ?>">Edit</a> |
                        <a href="?delete_collection=<?php echo $row['collection_id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this collection?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No collections found</td></tr>
        <?php endif; ?>
    </table>
    <br>
    <a href="management.php" class="back-button">← Back</a>
</div>

</body>
</html>

<?php $conn->close(); ?>

















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

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "gcms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$searchKeyword = isset($_GET['search']) ? $_GET['search'] : '';

function getCollection($conn, $searchKeyword = '') {
    $user_id = $_SESSION['user_id'];
    $searchClause = "";

    if (!empty($searchKeyword)) {
        $keyword = $conn->real_escape_string($searchKeyword);
        $searchClause = " AND (
            games.title LIKE '%$keyword%' OR 
            reviews.review_text LIKE '%$keyword%' OR 
            ranks.rank_name LIKE '%$keyword%'
        )";
    }

    $sql = "
SELECT user_game_collection.*, 
       users.username AS user_name, 
       games.title AS game_title, 
       reviews.review_text, 
       ranks.rank_name,
       skins.skin_name,
       user_game_collection.video_url
FROM user_game_collection
JOIN users ON user_game_collection.user_id = users.user_id
JOIN games ON user_game_collection.game_id = games.game_id
LEFT JOIN reviews ON user_game_collection.review_id = reviews.review_id
LEFT JOIN ranks ON user_game_collection.rank_id = ranks.rank_id
LEFT JOIN game_skin ON user_game_collection.game_id = game_skin.game_id
LEFT JOIN skins ON user_game_collection.skin_id = skins.skin_id
WHERE user_game_collection.user_id = $user_id $searchClause
";

    return $conn->query($sql);
}

function addCollection($conn, $game_id, $rank_id, $download_date, $review_id, $video_url, $skin_id) {
    $user_id = $_SESSION['user_id'];
    $sql = "INSERT INTO user_game_collection (user_id, game_id, rank_id, download_date, review_id, video_url, skin_id) 
            VALUES ('$user_id', '$game_id', '$rank_id', '$download_date', '$review_id', '$video_url', '$skin_id')";
    return $conn->query($sql);
}

function deleteCollection($conn, $collection_id) {
    $sql = "DELETE FROM user_game_collection WHERE collection_id = '$collection_id'";
    return $conn->query($sql);
}

function updateCollection($conn, $collection_id, $game_id, $rank_id, $download_date, $review_id, $video_url, $skin_id) {
    $sql = "UPDATE user_game_collection 
            SET game_id = '$game_id', rank_id = '$rank_id', download_date = '$download_date', 
                review_id = '$review_id', video_url = '$video_url', skin_id = '$skin_id'
            WHERE collection_id = '$collection_id'";
    return $conn->query($sql);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_collection'])) {
    $game_id = $_POST['game_id'];
    $rank_id = $_POST['rank_name'];
    $skin_id = $_POST['skin_id'];
    $download_date = $_POST['download_date'];
    $review_id = $_POST['review_id'];

    $video_url = null;
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $target_dir = "uploads/videos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["video_file"]["name"]);
        $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!in_array($videoFileType, ['mp4', 'avi', 'mov'])) {
            echo "Only MP4, AVI, and MOV files are allowed."; exit();
        }

        if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
            $video_url = $target_file;
        } else {
            echo "Error uploading video file."; exit();
        }
    }

    if (addCollection($conn, $game_id, $rank_id, $download_date, $review_id, $video_url, $skin_id)) {
        echo "New collection added successfully.";
    } else {
        echo "Error: " . $conn->error;
    }
}

if (isset($_GET['delete_collection'])) {
    $collection_id = $_GET['delete_collection'];
    if (deleteCollection($conn, $collection_id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error deleting collection: " . $conn->error;
    }
}

$editCollection = null;
if (isset($_GET['edit_collection'])) {
    $edit_id = $_GET['edit_collection'];
    $sql = "SELECT * FROM user_game_collection WHERE collection_id = '$edit_id'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $editCollection = $result->fetch_assoc();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_collection'])) {
    $collection_id = $_POST['collection_id'];
    $game_id = $_POST['game_id'];
    $rank_id = $_POST['rank_name'];
    $skin_id = $_POST['skin_id'];
    $download_date = $_POST['download_date'];
    $review_id = $_POST['review_id'];

    $video_url = $editCollection['video_url'];
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
        $target_dir = "uploads/videos/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["video_file"]["name"]);
        $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!in_array($videoFileType, ['mp4', 'avi', 'mov'])) {
            echo "Only MP4, AVI, and MOV files are allowed."; exit();
        }

        if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
            $video_url = $target_file;
        } else {
            echo "Error uploading video file."; exit();
        }
    }

    if (updateCollection($conn, $collection_id, $game_id, $rank_id, $download_date, $review_id, $video_url, $skin_id)) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error updating collection: " . $conn->error;
    }
}

$collection = getCollection($conn, $searchKeyword);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collection Management</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; margin: 20px; }
        .container { width: 90%; margin: auto; background: white; padding: 20px; box-shadow: 0px 0px 10px rgba(0,0,0,0.1); border-radius: 8px; }
        h2 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background: #007BFF; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .back-button, .submit-btn { margin-top: 20px; padding: 10px 20px; background: #007BFF; color: white; border: none; cursor: pointer; border-radius: 5px; font-size: 16px; text-decoration: none; display: inline-block; }
        .back-button:hover, .submit-btn:hover { background: #0056b3; }
        form { margin-top: 20px; text-align: left; }
        form input[type="text"], form input[type="number"], form select { width: 100%; padding: 8px; margin: 5px 0; }
        .submit-btn.reset { background-color: #0056b3; }
        .submit-btn.reset:hover { background-color: #004a9c; }
    </style>
</head>
<body>

<div class="container">
    <h2>Collection Management</h2>

    <!-- Search Form -->
    <form method="GET" style="margin-top: 30px; text-align: center;">
        <input type="text" name="search" placeholder="Search Game Title, Review or Rank" 
               value="<?php echo htmlspecialchars($searchKeyword); ?>" 
               style="padding: 8px; width: 250px;">
        <button type="submit" class="submit-btn">Search</button>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="submit-btn reset">Reset</a>
    </form>

    <!-- Add/Edit Collection Form -->
    <form method="POST" enctype="multipart/form-data">
        <h3><?php echo isset($editCollection) ? 'Edit Collection' : 'Add New Collection'; ?></h3>

        <!-- Game Selection -->
        <label>Game Title:</label>
        <select name="game_id" id="game_id" required onchange="loadRanks(this.value); loadSkins(this.value);">
            <option value="">Select Game</option>
            <?php
            $gameResult = $conn->query("SELECT game_id, title FROM games");
            while ($gameRow = $gameResult->fetch_assoc()) {
                $selected = isset($editCollection) && $editCollection['game_id'] == $gameRow['game_id'] ? 'selected' : '';
                echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
            }
            ?>
        </select>

        <!-- Rank Selection -->
        <label>Rank:</label>
        <select name="rank_name" id="rank_name" required>
            <option value="">Select Rank</option>
        </select>

        <!-- Skin Selection -->
        <label>Skin:</label>
        <select name="skin_id" id="skin_id" required>
            <option value="">Select Skin</option>
        </select>

        <!-- Other Form Fields -->
        <label>Download Date:</label><input type="date" name="download_date" required value="<?php echo isset($editCollection) ? $editCollection['download_date'] : ''; ?>">
        <label>Review:</label>
        <select name="review_id" required>
            <?php
            $reviewResult = $conn->query("SELECT review_id, review_text FROM reviews");
            while ($reviewRow = $reviewResult->fetch_assoc()) {
                $selected = isset($editCollection) && $editCollection['review_id'] == $reviewRow['review_id'] ? 'selected' : '';
                echo "<option value='" . $reviewRow['review_id'] . "' $selected>" . $reviewRow['review_text'] . "</option>";
            }
            ?>
        </select>

        <!-- Video Upload -->
        <label>Video (Optional):</label>
        <input type="file" name="video_file" accept="video/mp4,video/avi,video/mov">

        <button class="submit-btn" type="submit" name="<?php echo isset($editCollection) ? 'edit_collection' : 'add_collection'; ?>">
            <?php echo isset($editCollection) ? 'Update Collection' : 'Add Collection'; ?>
        </button>

        <?php if (isset($editCollection)): ?>
            <input type="hidden" name="collection_id" value="<?php echo $editCollection['collection_id']; ?>">
        <?php endif; ?>
    </form>

    <!-- Display Collection Table -->
    <table>
        <tr>
            <th>Collection ID</th>
            <th>User</th>
            <th>Game Title</th>
            <th>Download Date</th>
            <th>Review</th>
            <th>Rank</th>
            <th>Skins</th>
            <th>Video (Clips)</th>
            <th>Action</th>
        </tr>
        <?php
        if ($collection && $collection->num_rows > 0) {
            while ($row = $collection->fetch_assoc()) {
                echo "<tr>
                    <td>" . $row['collection_id'] . "</td>
                    <td>" . $row['user_name'] . "</td>
                    <td>" . $row['game_title'] . "</td>
                    <td>" . $row['download_date'] . "</td>
                    <td>" . $row['review_text'] . "</td>
                    <td>" . $row['rank_name'] . "</td>
                    <td>" . $row['skin_name'] . "</td>
                    <td><video width='200' controls><source src='" . $row['video_url'] . "' type='video/mp4'></video></td>
                    <td><a href='?delete_collection=" . $row['collection_id'] . "'>Delete</a></td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='9'>No collections found.</td></tr>";
        }
        ?>
    </table>
</div>

<script>
    function loadRanks(game_id) {
        var rankSelect = document.getElementById('rank_name');
        fetch('getRanks.php?game_id=' + game_id)
            .then(response => response.json())
            .then(data => {
                rankSelect.innerHTML = "<option value=''>Select Rank</option>";
                data.forEach(rank => {
                    rankSelect.innerHTML += `<option value='${rank.rank_id}'>${rank.rank_name}</option>`;
                });
            });
    }

    function loadSkins(game_id) {
        var skinSelect = document.getElementById('skin_id');
        fetch('getSkins.php?game_id=' + game_id)
            .then(response => response.json())
            .then(data => {
                skinSelect.innerHTML = "<option value=''>Select Skin</option>";
                data.forEach(skin => {
                    skinSelect.innerHTML += `<option value='${skin.skin_id}'>${skin.skin_name}</option>`;
                });
            });
    }
</script>

</body>
</html>
