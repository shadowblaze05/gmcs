<?php
session_start();
require_once '../db.php';

// ensure logged in & role
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: index.html');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

/* ---------------------------------------------------------
   LOAD USER'S GAMES
--------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT g.game_id, g.title, ugc.game_image
    FROM user_game_collection AS ugc
    JOIN games AS g ON ugc.game_id = g.game_id
    WHERE ugc.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$games = [];
while ($row = $result->fetch_assoc()) {
    $games[] = $row;
}
$stmt->close();

/* ---------------------------------------------------------
   LOAD USER'S FRIENDS
--------------------------------------------------------- */
$friendQuery = $conn->prepare("
    SELECT u.username
    FROM friends f
    JOIN users u ON f.friend_id = u.user_id
    WHERE f.user_id = ?
");
$friendQuery->bind_param("i", $user_id);
$friendQuery->execute();
$friendResult = $friendQuery->get_result();

$friends = [];
while ($row = $friendResult->fetch_assoc()) {
    $friends[] = $row['username'];
}

$req = $conn->prepare("
    SELECT fr.id, u.username AS sender
    FROM friend_requests fr
    JOIN users u ON fr.sender_id = u.user_id
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$req->bind_param("i", $user_id);
$req->execute();
$requests = $req->get_result();

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>GMCS — User Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="user_dashboard.css" />
</head>

<body>
    <div class="wrap">

        <!-- LEFT SIDEBAR -->
        <aside class="left">
            <div class="top">
                <div class="brand">GMCS</div>

                <div class="userbox">
                    <div class="avatar"><?= strtoupper($username[0] ?? 'U') ?></div>
                    <div class="un"><?= $username ?></div>

                    <div class="more">
                        <button class="more-btn">⋮</button>
                        <div class="more-menu">
                            <a href="profile.php">Profile</a>
                            <a href="settings.php">Settings</a>
                            <a href="ud_logout.php">Logout</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FRIENDS SECTION -->
            <div class="section">
                <h3>Friends</h3>

                <ul id="friendsList" class="list">
                    <?php foreach ($friends as $f): ?>
                        <li class="item"><?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="add-row">
                    <input id="friendName" placeholder="Username to add" />
                    <button type="button" id="addFriendBtn">Add</button>
                </div>
            </div>

            <!-- COMMUNITIES SECTION -->
            <div class="section">
                <h3>Communities</h3>
                <ul id="gamesList" class="list">
                    <?php if (empty($games)): ?>
                        <li class="empty">No games added.</li>
                    <?php else: ?>
                        <?php foreach ($games as $game): ?>
                            <li class="item" onclick="openCommunity(<?= $game['game_id'] ?>, '<?= $game['title'] ?>')">
                                <?= htmlspecialchars($game['title']) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <div class="section">
            <h3>Friend Requests</h3>
            <ul class="list">
                <?php while ($r = $requests->fetch_assoc()): ?>
                    <li class="item">
                        <?= htmlspecialchars($r['sender']) ?>
                        <button onclick="acceptRequest(<?= $r['id'] ?>)">Accept</button>
                        <button onclick="declineRequest(<?= $r['id'] ?>)">Decline</button>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- CENTER CHAT AREA -->
        <main class="center">
            <div class="chat-head">
                <div id="chatTitle">Select friend or community</div>
                <div class="status" id="chatStatus">No chat selected</div>
            </div>

            <div id="messages" class="messages empty">
                <div class="empty-placeholder">No chat selected — pick a friend or community</div>
            </div>

            <form id="sendForm" class="composer" onsubmit="return false;">
                <input id="msgInput" placeholder="Type a message..." autocomplete="off" />
                <button id="sendBtn">Send</button>
            </form>
        </main>

        <!-- RIGHT SIDEBAR -->
        <aside class="right">

            <!-- ADD GAME -->
            <div class="section">
                <h3>Add a Game</h3>
                <form action="add_game.php" method="POST" class="small-form">
                    <select name="game_id" required>
                        <?php
                        $stmt = $conn->prepare("SELECT game_id, title FROM games");
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['game_id']}'>" . htmlspecialchars($row['title']) . "</option>";
                        }
                        $stmt->close();
                        ?>
                    </select>
                    <button type="submit">+ Add Game</button>
                </form>
            </div>

            <!-- MY GAMES -->
            <div class="section">
                <h3>My Games</h3>
                <ul id="myGamesList" class="list">
                    <?php if (empty($games)): ?>
                        <li class="empty">You have not added games.</li>
                    <?php else: ?>
                        <?php foreach ($games as $game): ?>
                            <li class="item">
                                <?php if (!empty($game['game_image'])): ?>
                                    <img src="<?= htmlspecialchars($game['game_image']) ?>"
                                        alt="<?= htmlspecialchars($game['title']) ?> Logo"
                                        style="width:40px;height:40px;border-radius:6px;margin-right:8px;">
                                <?php endif; ?>

                                <?= htmlspecialchars($game['title']) ?>

                                <div style="margin-left:auto;display:flex;gap:6px;">
                                    <!-- Update -->
                                    <form action="update_game.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="game_id" value="<?= $game['game_id'] ?>">
                                        <button type="submit">Update</button>
                                    </form>

                                    <!-- Delete -->
                                    <form action="delete_game.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="game_id" value="<?= $game['game_id'] ?>">
                                        <button type="submit" style="background:#a11;color:white;">Delete</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

        </aside>
    </div>

    <script>
        /* ---------------------------------------------------------
           COMMUNITY CHAT HANDLING
        --------------------------------------------------------- */
        let CURRENT_CHAT = null;

        function openCommunity(game_id, title) {
            CURRENT_CHAT = {
                type: "community",
                id: game_id,
                name: title
            };
            document.getElementById("chatTitle").innerText = title + " (Community)";
            document.getElementById("chatStatus").innerText = "Connected";
            loadMessages();
        }

        document.getElementById("sendBtn").addEventListener("click", async () => {
            const text = document.getElementById("msgInput").value.trim();
            if (!text || !CURRENT_CHAT) return;

            let res = await fetch("ud_send_message.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    type: CURRENT_CHAT.type,
                    id: CURRENT_CHAT.id,
                    content: text
                })
            });

            let data = await res.json();

            if (data.success) {
                document.getElementById("msgInput").value = "";
                loadMessages();
            } else {
                alert(data.error);
            }
        });

        async function loadMessages() {
            if (!CURRENT_CHAT) return;

            let res = await fetch("ud_get_messages.php?type=" + CURRENT_CHAT.type + "&id=" + CURRENT_CHAT.id);
            let data = await res.json();

            let box = document.getElementById("messages");
            box.innerHTML = "";

            if (!data.length) {
                box.classList.add("empty");
                box.innerHTML = "<div class='empty-placeholder'>No messages yet.</div>";
                return;
            }

            box.classList.remove("empty");
            data.forEach(m => {
                let div = document.createElement("div");
                div.className = "msg";
                div.innerHTML = `
                    <div class="meta"><b>${m.username}</b> — ${m.created_at}</div>
                    <div class="content">${m.content}</div>
                `;
                box.appendChild(div);
            });

            box.scrollTop = box.scrollHeight;
        }

        setInterval(loadMessages, 2000);

        document.getElementById("addFriendBtn").addEventListener("click", async () => {
            const username = document.getElementById("friendName").value.trim();
            if (!username) return alert("Enter a username");

            const res = await fetch("ud_add_friend.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    username
                })
            });
            const data = await res.json();

            if (!data.success) return alert(data.error);

            // Add friend request to the UI
            const li = document.createElement("li");
            li.className = "item";
            li.textContent = data.friend.username;
            // optionally add accept/decline buttons if showing incoming requests
            document.getElementById("friendRequests").appendChild(li);

            document.getElementById("friendName").value = "";
            alert("Friend request sent to " + data.friend.username);
        });
        document.getElementById("addFriendBtn").addEventListener("click", async () => {
            const username = document.getElementById("friendName").value.trim();
            if (!username) return alert("Enter a username");

            const res = await fetch("ud_add_friends.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    username
                })
            });
            const data = await res.json();

            if (!data.success) return alert(data.error);

        // ✅ Add friend to UI (correct place)
        const list = document.getElementById("friendsList");
        const li = document.createElement("li");
        li.className = "item";
        li.textContent = data.friend.username;
        list.appendChild(li);

        document.getElementById("friendName").value = "";

        alert("Friend added: " + data.friend.username);
        });

        async function acceptRequest(id) {
            let res = await fetch("ud_accept_request.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "request_id=" + id
            });

            let data = await res.json();

            if (data.success) {
                alert("Friend Request Accepted!");
                location.reload(); // reload to show new friend
            }
        }

        async function declineRequest(id) {
            let res = await fetch("ud_decline_request.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "request_id=" + id
            });

            let data = await res.json();

            if (data.success) {
                alert("Friend Request Declined.");
                location.reload();
            }
        }

        async function loadRequests() {
            const data = await fetch("ud_get_friend_requests.php").then(r => r.json());
            console.log("Friend Requests:", data); // ← Debug

            const box = document.getElementById("friendRequests");
            box.innerHTML = "";

            if (data.length === 0) {
                box.innerHTML = "<p>No friend requests.</p>";
                return;
            }

            data.forEach(req => {
                box.innerHTML += `
            <div class="req">
                <b>${req.username}</b>
                <button onclick="accept(${req.request_id})">Accept</button>
                <button onclick="decline(${req.request_id})">Decline</button>
            </div>
        `;
            });
        }
    </script>

</body>

</html>