<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* ---------------------------------------------------------
   STEP 1 — SHOW UPDATE FORM
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id']) && !isset($_POST['skin_id'])) {

    $game_id = (int)$_POST['game_id'];

    /* ------------------ LOAD RANKS ------------------ */
    $rankQuery = $conn->prepare("
        SELECT r.rank_id, r.rank_name, r.rank_level
        FROM ranks r
        JOIN game_ranks gr ON r.rank_id = gr.rank_id
        WHERE gr.game_id = ?
        ORDER BY r.rank_name, r.rank_level
    ");
    $rankQuery->bind_param("i", $game_id);
    $rankQuery->execute();
    $rankResult = $rankQuery->get_result();

    $rankData = [];
    while ($row = $rankResult->fetch_assoc()) {
        $rankData[$row['rank_name']][] = [
            "rank_id" => $row["rank_id"],
            "rank_level" => $row["rank_level"]
        ];
    }

    /* ------------------ LOAD SKINS ------------------ */
    $skinQuery = $conn->prepare("
        SELECT s.skin_id, s.skin_name
        FROM skins s
        JOIN game_skin gs ON s.skin_id = gs.skin_id
        WHERE gs.game_id = ?
        ORDER BY s.skin_name
    ");
    $skinQuery->bind_param("i", $game_id);
    $skinQuery->execute();
    $skinResult = $skinQuery->get_result();
?>
    <!doctype html>
    <html>

    <head>
        <title>Update Game</title>
    </head>

    <body style="background:#05060a;color:white;font-family:Arial;">
        <h2>Update Game Info</h2>

        <form action="update_game.php" method="POST">
            <input type="hidden" name="game_id" value="<?= $game_id ?>">

            <!-- SKIN DROPDOWN -->
            <label>Skin Purchased:</label><br>
            <select name="skin_id" style="width:250px;">
                <?php while ($skin = $skinResult->fetch_assoc()): ?>
                    <option value="<?= $skin['skin_id'] ?>"><?= $skin['skin_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <br><br>

            <!-- RANK DROPDOWN -->
            <label>Rank:</label><br>
            <select name="rank_id" id="rankSelect">
                <?php foreach ($rankData as $rankName => $levels): ?>
                    <option value="<?= $levels[0]['rank_id'] ?>"><?= $rankName ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <!-- RANK LEVEL DROPDOWN -->
            <label>Rank Level:</label><br>
            <select name="rank_level" id="rankLevelSelect">
                <?php
                $firstRank = array_key_first($rankData);
                foreach ($rankData[$firstRank] as $lvl):
                ?>
                    <option value="<?= $lvl['rank_level'] ?>"><?= $lvl['rank_level'] ?></option>
                <?php endforeach; ?>
            </select>
            <br><br>

            <label>Transaction Amount:</label><br>
            <input type="number" name="transaction"><br><br>

            <label>Review:</label><br>
            <textarea name="review_text"></textarea><br><br>

            <label>Rating (1–5):</label><br>
            <input type="number" name="rating" min="1" max="5"><br><br>

            <button type="submit">Save</button>
        </form>

        <!-- AUTO-UPDATE RANK LEVELS -->
        <script>
            const rankData = <?= json_encode($rankData) ?>;

            document.getElementById('rankSelect').addEventListener('change', function() {
                const selectedRankName = this.options[this.selectedIndex].text;
                const levels = rankData[selectedRankName];

                const levelDropdown = document.getElementById('rankLevelSelect');
                levelDropdown.innerHTML = "";

                levels.forEach(item => {
                    const opt = document.createElement("option");
                    opt.value = item.rank_level;
                    opt.textContent = item.rank_level;
                    levelDropdown.appendChild(opt);
                });
            });
        </script>

    </body>

    </html>

<?php
    exit();
}

/* ---------------------------------------------------------
   STEP 2 — SAVE UPDATE
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skin_id'])) {

    $game_id = (int)$_POST['game_id'];
    $skin_id = (int)$_POST['skin_id'];
    $rank_id = (int)$_POST['rank_id'];
    $transaction = (int)$_POST['transaction'];

    /* ------------------ INSERT REVIEW INTO reviews TABLE ------------------ */
    $reviewText = $_POST['review_text'];
    $rating = (int)$_POST['rating'];
    $reviewDate = date("Y-m-d");

    $insertReview = $conn->prepare("
        INSERT INTO reviews (user_id, game_id, review_text, rating, review_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $insertReview->bind_param("iisis", $user_id, $game_id, $reviewText, $rating, $reviewDate);
    $insertReview->execute();

    $review_id = $insertReview->insert_id;

    /* ------------------ UPDATE user_game_collection ------------------ */
    $stmt = $conn->prepare("
        UPDATE user_game_collection
        SET skin_id = ?, rank_id = ?, transaction_id = ?, review_id = ?
        WHERE user_id = ? AND game_id = ?
    ");

    $stmt->bind_param("iiiiii", $skin_id, $rank_id, $transaction, $review_id, $user_id, $game_id);

    if ($stmt->execute()) {
        header("Location: user_dashboard.php");
        exit();
    } else {
        echo "Update failed: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>