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

// Fetch all games from the games table
$games_query = "SELECT * FROM games";
$games_result = mysqli_query($conn, $games_query);

// Handle form submissions (add, edit, delete)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_rank'])) {
        $game_id = $_POST['game_id'];
        $rank_name = $_POST['rank_name'];
        $rank_level = $_POST['rank_level'];

        $add_rank_query = "INSERT INTO ranks (rank_name, rank_level) 
                           VALUES ('$rank_name', '$rank_level')";
        if (mysqli_query($conn, $add_rank_query)) {
            $rank_id = mysqli_insert_id($conn);
            $insert_game_rank_query = "INSERT INTO game_ranks (game_id, rank_id) 
                                       VALUES ('$game_id', '$rank_id')";
            mysqli_query($conn, $insert_game_rank_query);
        }
    }

    if (isset($_POST['delete_rank'])) {
        $rank_id = $_POST['rank_id'];
        $delete_game_rank_query = "DELETE FROM game_ranks WHERE rank_id = '$rank_id'";
        mysqli_query($conn, $delete_game_rank_query);
        $delete_rank_query = "DELETE FROM ranks WHERE rank_id = '$rank_id'";
        mysqli_query($conn, $delete_rank_query);
    }

    if (isset($_POST['edit_rank'])) {
        $rank_id = $_POST['rank_id'];
        $game_id = $_POST['game_id'];
        $rank_name = $_POST['rank_name'];
        $rank_level = $_POST['rank_level'];

        $edit_rank_query = "UPDATE ranks 
                            SET rank_name = '$rank_name', rank_level = '$rank_level' 
                            WHERE rank_id = '$rank_id'";
        mysqli_query($conn, $edit_rank_query);

        $update_game_rank_query = "UPDATE game_ranks 
                                   SET game_id = '$game_id' 
                                   WHERE rank_id = '$rank_id'";
        mysqli_query($conn, $update_game_rank_query);
    }
}

if (isset($_GET['delete'])) {
    $rank_id = $_GET['delete'];
    $delete_game_rank_query = "DELETE FROM game_ranks WHERE rank_id = '$rank_id'";
    mysqli_query($conn, $delete_game_rank_query);
    $delete_rank_query = "DELETE FROM ranks WHERE rank_id = '$rank_id'";
    mysqli_query($conn, $delete_rank_query);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Search functionality
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $game_ranks_query = "SELECT g.title, r.rank_id, r.rank_name, r.rank_level 
                         FROM game_ranks gr
                         JOIN ranks r ON gr.rank_id = r.rank_id
                         JOIN games g ON gr.game_id = g.game_id
                         WHERE r.rank_name LIKE '%$search%' OR g.title LIKE '%$search%'";
} else {
    $game_ranks_query = "SELECT g.title, r.rank_id, r.rank_name, r.rank_level 
                         FROM game_ranks gr
                         JOIN ranks r ON gr.rank_id = r.rank_id
                         JOIN games g ON gr.game_id = g.game_id";
}
$game_ranks_result = mysqli_query($conn, $game_ranks_query);

if (isset($_GET['edit'])) {
    $edit_rank_id = $_GET['edit'];
    $edit_rank_query = "SELECT r.*, gr.game_id 
                        FROM ranks r 
                        JOIN game_ranks gr ON r.rank_id = gr.rank_id 
                        WHERE r.rank_id = '$edit_rank_id'";
    $edit_rank_result = mysqli_query($conn, $edit_rank_query);
    $edit_rank = mysqli_fetch_assoc($edit_rank_result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Game Ranks</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    .container {
        width: 90%;
        max-width: 1000px;
        margin: 30px auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
    }

    h2 {
        margin-bottom: 20px;
        color: #1e293b;
        font-size: 28px;
    }

    .search-form {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
    }

    .search-form input[type="text"] {
        padding: 10px;
        width: 100%;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .search-form button {
        background-color: #090493;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
    }

    .search-form button:hover {
        background-color: #0d0def;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 30px;
    }

    form label {
        font-weight: 600;
    }

    form input[type="text"],
    form select {
        padding: 10px;
        width: 100%;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    .submit-btn {
        width: fit-content;
        background-color: #090493;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
    }

    .submit-btn:hover {
        background-color: #0d0def;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        padding: 12px;
        border: 1px solid #ddd;
        text-align: left;
    }

    th {
        background-color: #1e293b;
        color: white;
    }

    a {
        color: #090493;
        text-decoration: none;
        font-weight: 500;
    }

    a:hover {
        text-decoration: underline;
    }

    .back-button {
        display: inline-block;
        margin-top: 20px;
        background-color: #090493;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
    }

    .back-button:hover {
        background-color: #0d0def;
    }
</style>

<body>
    <div class="container">
        <h2>Manage Game Ranks</h2>

        <!-- Search Form (Moved to Top) -->
        <form method="GET" action="rank_management.php" class="search-form">
            <input type="text" name="search" placeholder="Search rank or game" value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Search</button>
        </form>

        <!-- Add or Edit Rank Form -->
        <h3><?= isset($edit_rank) ? 'Edit Rank' : 'Add Rank to Game' ?></h3>
        <form action="rank_management.php" method="POST">
            <label for="game_id">Select Game:</label>
            <select name="game_id" required>
                <?php mysqli_data_seek($games_result, 0); ?>
                <?php while ($game = mysqli_fetch_assoc($games_result)) { ?>
                    <option value="<?= $game['game_id'] ?>" 
                        <?= isset($edit_rank) && $edit_rank['game_id'] == $game['game_id'] ? 'selected' : '' ?>>
                        <?= $game['title'] ?>
                    </option>
                <?php } ?>
            </select>

            <label for="rank_name">Rank Name:</label>
            <input type="text" name="rank_name" required value="<?= isset($edit_rank) ? $edit_rank['rank_name'] : '' ?>">

            <label for="rank_level">Rank Level:</label>
            <input type="text" name="rank_level" required value="<?= isset($edit_rank) ? $edit_rank['rank_level'] : '' ?>">

            <button class="submit-btn" type="submit" name="<?= isset($edit_rank) ? 'edit_rank' : 'add_rank' ?>">
                <?= isset($edit_rank) ? 'Update Rank' : 'Add Rank' ?>
            </button>

            <?php if (isset($edit_rank)) { ?>
                <input type="hidden" name="rank_id" value="<?= $edit_rank['rank_id'] ?>">
            <?php } ?>
        </form>

        <!-- Display Ranks Table -->
        <h3>Existing Ranks</h3>
        <table>
            <tr>
                <th>Game Title</th>
                <th>Rank Name</th>
                <th>Rank Level</th>
                <th>Action</th>
            </tr>
            <?php if (mysqli_num_rows($game_ranks_result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($game_ranks_result)): ?>
                    <tr>
                        <td><?= $row["title"]; ?></td>
                        <td><?= $row["rank_name"]; ?></td>
                        <td><?= $row["rank_level"]; ?></td>
                        <td>
                            <a href="rank_management.php?edit=<?= $row['rank_id']; ?>">Edit</a> |
                            <a href="rank_management.php?delete=<?= $row['rank_id']; ?>" onclick="return confirm('Are you sure you want to permanently delete this rank?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No ranks found</td></tr>
            <?php endif; ?>
        </table>

        <a href="management.php" class="back-button">← Back</a>
    </div>
</body>

<script>
function fetchSkinsByGame(game_id, form) {
    var xhr = new XMLHttpRequest();
    xhr.open("GET", "getSkins.php?game_id=" + game_id, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var skins = JSON.parse(xhr.responseText);
            var skinDropdown = form.querySelector(".skin-dropdown");
            skinDropdown.innerHTML = "<option value=''>Select a skin</option>";
            skins.forEach(function(skin) {
                var option = document.createElement("option");
                option.value = skin.skin_id;
                option.textContent = skin.skin_name;
                skinDropdown.appendChild(option);
            });
        }
    };
    xhr.send();
}
</script>

</body>
</html>

<?php $conn->close(); ?>