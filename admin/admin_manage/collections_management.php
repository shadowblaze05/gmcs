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

$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "gcms";
$port = 3307;

    $conn = new mysqli($host, $username, $password, $database, $port);

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

// Add this in your function definitions and SQL:
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

// same for updateCollection()
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Collection Management</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
    }

    .header {
      background-color: #1e293b;
      color: white;
      padding: 20px;
      text-align: center;
    }

    .container {
      max-width: 1100px;
      margin: 30px auto;
      background-color: white;
      padding: 30px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      border-radius: 16px;
    }

    h2 {
      color: #1e293b;
      text-align: center;
    }

    form {
      margin-top: 30px;
    }

    form label {
      font-weight: bold;
      display: block;
      margin: 15px 0 5px;
    }

    form input[type="text"],
    form input[type="number"],
    form input[type="date"],
    form input[type="file"],
    form select {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }

    .submit-btn,
    .back-button,
    .reset-btn {
      display: inline-block;
      background-color: #090493;
      color: white;
      padding: 10px 20px;
      margin: 10px 5px 0 0;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      text-decoration: none;
      transition: background-color 0.3s ease;
    }

    .submit-btn:hover,
    .back-button:hover,
    .reset-btn:hover {
      background-color: #0d0def;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
    }

    table, th, td {
      border: 1px solid #ddd;
    }

    th, td {
      padding: 14px;
      text-align: left;
    }

    th {
      background-color: #1e293b;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .actions a {
      margin-right: 10px;
      color: #090493;
      text-decoration: none;
    }

    .actions a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="header">
  <h2>Collection Management</h2>
</div>

<div class="container">
  <!-- Search -->
  <form method="GET" style="text-align: center;">
    <input type="text" name="search" placeholder="Search Game Title, Review or Rank"
           value="<?php echo htmlspecialchars($searchKeyword); ?>"
           style="width: 300px; padding: 10px; border-radius: 10px; border: 1px solid #ccc;">
    <button type="submit" class="submit-btn">Search</button>
    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="reset-btn">Reset</a>
  </form>

  <!-- Add/Edit Form -->
  <form method="POST" enctype="multipart/form-data">
    <h3 style="text-align:center;">
      <?php echo isset($editCollection) ? 'Edit Collection' : 'Add New Collection'; ?>
    </h3>

    <label>Game Title:</label>
    <select name="game_id" id="game_id" required onchange="loadRanks(this.value);">
      <option value="">Select Game</option>
      <?php
        $gameResult = $conn->query("SELECT game_id, title FROM games");
        while ($gameRow = $gameResult->fetch_assoc()) {
            $selected = isset($editCollection) && $editCollection['game_id'] == $gameRow['game_id'] ? 'selected' : '';
            echo "<option value='" . $gameRow['game_id'] . "' $selected>" . $gameRow['title'] . "</option>";
        }
      ?>
    </select>

    <label>Rank:</label>
    <select name="rank_name" required id="rank_name">
      <option value="">Select Rank</option>
    </select>

    <label for="skin_id">Skin (Optional):</label>
    <select name="skin_id" id="skin_id">
      <option value="">No Skin</option>
    </select>

    <label>Download Date:</label>
    <input type="date" name="download_date" required value="<?php echo isset($editCollection) ? $editCollection['download_date'] : ''; ?>">

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

    <label>Video (Optional):</label>
    <input type="file" name="video_file" accept="video/mp4,video/avi,video/mov">

    <button class="submit-btn" type="submit" name="<?php echo isset($editCollection) ? 'edit_collection' : 'add_collection'; ?>">
      <?php echo isset($editCollection) ? 'Update Collection' : 'Add Collection'; ?>
    </button>

    <?php if (isset($editCollection)): ?>
      <input type="hidden" name="collection_id" value="<?php echo $editCollection['collection_id']; ?>">
    <?php endif; ?>
  </form>

  <!-- Table -->
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
    <?php if ($collection->num_rows > 0): ?>
      <?php while($row = $collection->fetch_assoc()): ?>
        <tr>
          <td><?php echo $row["collection_id"]; ?></td>
          <td><?php echo $row["user_name"]; ?></td>
          <td><?php echo $row["game_title"]; ?></td>
          <td><?php echo $row["download_date"]; ?></td>
          <td><?php echo $row["review_text"]; ?></td>
          <td><?php echo $row["rank_name"]; ?></td>
          <td><?php echo $row["skin_name"]; ?></td>
          <td>
            <?php if ($row["video_url"]): ?>
              <a href="<?php echo $row["video_url"]; ?>" target="_blank">Watch Video</a>
            <?php else: ?>
              No video uploaded
            <?php endif; ?>
          </td>
          <td class="actions">
            <a href="?edit_collection=<?php echo $row['collection_id']; ?>">Edit</a>
            |
            <a href="?delete_collection=<?php echo $row['collection_id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="9" style="text-align: center;">No collections found.</td></tr>
    <?php endif; ?>
  </table>

  <a class="back-button" href="management.php">Back</a>
</div>

<script>
function loadRanks(game_id) {
  if (game_id) {
    fetch("getRanks.php?game_id=" + game_id)
      .then(res => res.json())
      .then(ranks => {
        const rankSelect = document.getElementById("rank_name");
        rankSelect.innerHTML = '<option value="">Select Rank</option>';
        ranks.forEach(rank => {
          const option = document.createElement("option");
          option.value = rank.rank_id;
          option.textContent = rank.rank_name;
          <?php if (isset($editCollection)): ?>
            if (rank.rank_id == <?php echo $editCollection['rank_id']; ?>) {
              option.selected = true;
            }
          <?php endif; ?>
          rankSelect.appendChild(option);
        });
      });
  }
}

function loadSkins(game_id) {
  if (game_id) {
    fetch("getSkins.php?game_id=" + game_id)
      .then(res => res.json())
      .then(skins => {
        const skinSelect = document.getElementById("skin_id");
        skinSelect.innerHTML = '<option value="">No Skin</option>';
        skins.forEach(skin => {
          const option = document.createElement("option");
          option.value = skin.skin_id;
          option.textContent = skin.skin_name;
          <?php if (isset($editCollection)): ?>
            if (skin.skin_id == <?php echo $editCollection['skin_id']; ?>) {
              option.selected = true;
            }
          <?php endif; ?>
          skinSelect.appendChild(option);
        });
      });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const gameSelect = document.getElementById('game_id');
  gameSelect.addEventListener('change', function () {
    loadRanks(this.value);
    loadSkins(this.value);
  });

  <?php if (isset($editCollection)): ?>
    loadRanks(<?php echo $editCollection['game_id']; ?>);
    loadSkins(<?php echo $editCollection['game_id']; ?>);
  <?php endif; ?>
});
</script>

</body>
</html>
