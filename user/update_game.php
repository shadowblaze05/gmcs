<?php
session_start();
require_once '../db.php';

/* ---------------- AUTH CHECK ---------------- */
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$action  = "Visited Update Game page";
require '../admin/admin_manage/audit.php';

/* =================================================
   STEP 1 — DISPLAY UPDATE FORM
================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id']) && !isset($_POST['skin_id'])) {

    $game_id = (int)$_POST['game_id'];

    /* ---------- LOAD RANKS ---------- */
    $rankStmt = $conn->prepare("
        SELECT r.rank_id, r.rank_name, r.rank_level
        FROM ranks r
        INNER JOIN game_ranks gr ON r.rank_id = gr.rank_id
        WHERE gr.game_id = ?
        ORDER BY r.rank_name, r.rank_level
    ");
    $rankStmt->bind_param("i", $game_id);
    $rankStmt->execute();
    $rankResult = $rankStmt->get_result();

    $rankData = [];
    while ($row = $rankResult->fetch_assoc()) {
        $rankData[$row['rank_name']][] = [
            'rank_id'    => $row['rank_id'],
            'rank_level' => $row['rank_level']
        ];
    }

    /* ---------- LOAD ALL SKINS FOR THE GAME ---------- */
    $skinStmt = $conn->prepare("
        SELECT s.skin_id, s.skin_name
        FROM skins s
        INNER JOIN game_skin gs ON s.skin_id = gs.skin_id
        WHERE gs.game_id = ?
        ORDER BY s.skin_name
    ");
    $skinStmt->bind_param("i", $game_id);
    $skinStmt->execute();
    $skinResult = $skinStmt->get_result();
?>
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <title>Update Game</title>
        <style>
            body {
                background: linear-gradient(135deg, #05060a, #0d1020);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: Arial, sans-serif;
                color: #fff;
            }

            .form-container {
                background: #0f1225;
                width: 420px;
                padding: 30px;
                border-radius: 14px;
                box-shadow: 0 25px 45px rgba(0, 0, 0, .6);
            }

            label {
                font-size: 14px;
                color: #cfd2ff;
            }

            select,
            input,
            textarea {
                width: 100%;
                margin-top: 6px;
                margin-bottom: 16px;
                padding: 9px;
                border-radius: 7px;
                border: none;
                background: #1a1d3a;
                color: #fff;
            }

            button {
                width: 100%;
                height: 44px;
                border: none;
                border-radius: 9px;
                background: #4f6cff;
                color: #fff;
                font-weight: bold;
                cursor: pointer;
            }

            .back-btn {
                background: none;
                border: none;
                color: #cfd2ff;
                cursor: pointer;
                margin-bottom: 10px;
            }
        </style>
    </head>

    <body>
        <div class="form-container">
            <button class="back-btn" onclick="window.history.back()">← Back</button>
            <h2>Update Game</h2>

            <form method="POST" action="update_game.php">
                <input type="hidden" name="game_id" value="<?= $game_id ?>">

                <label>Skin</label>
                <select name="skin_id[]" multiple>
                    <?php while ($skin = $skinResult->fetch_assoc()): ?>
                        <option value="<?= $skin['skin_id'] ?>">
                            <?= htmlspecialchars($skin['skin_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label>Rank</label>
                <select name="rank_id" id="rankSelect" required>
                    <?php foreach ($rankData as $rankName => $levels): ?>
                        <option value="<?= $levels[0]['rank_id'] ?>">
                            <?= htmlspecialchars($rankName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                

                <label>Review</label>
                <textarea name="review_text"></textarea>

                <!--<label>Rating (1–5)</label>
                <input type="number" name="rating" min="1" max="5">-->

                <button type="submit">Save Changes</button>
            </form>
        </div>

        <script>
            const rankData = <?= json_encode($rankData) ?>;
            document.getElementById('rankSelect').addEventListener('change', function() {
                const levels = rankData[this.options[this.selectedIndex].text];
                const lvlSel = document.getElementById('rankLevelSelect');
                lvlSel.innerHTML = '';
                levels.forEach(l => {
                    const o = document.createElement('option');
                    o.textContent = l.rank_level;
                    lvlSel.appendChild(o);
                });
            });
        </script>
    </body>

    </html>
<?php
    exit;
}

/* =================================================
   STEP 2 — SAVE UPDATE
================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id    = (int) $_POST['game_id'];
    $rank_id    = (int) $_POST['rank_id'];
    $rating     = (int) $_POST['rating'];
    $reviewText = trim($_POST['review_text']);

    $conn->begin_transaction();

    try {
        // Update skins separately
        if (!empty($_POST['skin_id'])) {
            $selected_skins = $_POST['skin_id'];
            $conn->query("DELETE FROM user_game_skins WHERE user_id = $user_id AND game_id = $game_id");
            foreach ($selected_skins as $skin_id) {
                $skin_id = (int)$skin_id;
                $conn->query("INSERT INTO user_game_skins (user_id, game_id, skin_id) VALUES ($user_id, $game_id, $skin_id)");
            }
            // use first skin as main skin for collection
            $skin_for_sp = (int)$selected_skins[0];
        } else {
            $skin_for_sp = 0;
        }

        // Update rank/review/rating via stored procedure
        $stmt = $conn->prepare("CALL sp_update_user_game_full(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iiiisi",
            $user_id,
            $game_id,
            $skin_for_sp,
            $rank_id,
            $reviewText,
            $rating
        );
        $stmt->execute();
        $stmt->close();

        // Log this in audit trail
        $action = "Updated game ID $game_id (Rank/Review/Skins)";
        $auditStmt = $conn->prepare("INSERT INTO audit_trail (user_id, action, created_at) VALUES (?, ?, NOW())");
        $auditStmt->bind_param("is", $user_id, $action);
        $auditStmt->execute();
        $auditStmt->close();

        $conn->commit();

        header("Location: user_dashboard.php");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }

            if ($result['has_skin'] > 0) {
                // user already owns this skin → skip insert
            } else {
                $insertSkin = $conn->prepare(
                    "INSERT INTO user_game_skins (user_id, game_id, skin_id)
                     VALUES (?, ?, ?)"
                );
                $insertSkin->bind_param("iii", $user_id, $game_id, $skin_id);
                $insertSkin->execute();
                $insertSkin->close();
            }


            header("Location: user_dashboard.php");
    exit;
}
