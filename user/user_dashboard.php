<?php
session_start();
require_once '../db.php';

// Ensure logged in & role
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

// ---------------------------------------------------------
// LOAD USER'S GAMES
// ---------------------------------------------------------
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

// ---------------------------------------------------------
// LOAD FRIEND REQUESTS
// ---------------------------------------------------------
$req = $conn->prepare("
    SELECT fr.id, u.username AS sender
    FROM friend_requests fr
    JOIN users u ON fr.sender_id = u.user_id
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
");
$req->bind_param("i", $user_id);
$req->execute();
$requests = $req->get_result();

// ---------------------------------------------------------
// LOAD FRIENDS
// ---------------------------------------------------------
$friendsQuery = $conn->prepare("
    SELECT u.user_id, u.username
    FROM friends f
    JOIN users u ON u.user_id = f.friend_id
    WHERE f.user_id = ?
");
$friendsQuery->bind_param("i", $user_id);
$friendsQuery->execute();
$friendsRes = $friendsQuery->get_result();
$friends = [];
while ($f = $friendsRes->fetch_assoc()) {
    $friends[] = $f;
}
$friendsQuery->close();
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
                        <li class="item" data-id="<?= $f['user_id'] ?>">
                            <span><?= htmlspecialchars($f['username']) ?></span>
                            <button class="delBtn" style="margin-left:10px;">Delete</button>
                        </li>
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
                            <li class="item" onclick="openCommunity(<?= $game['game_id'] ?>, '<?= htmlspecialchars($game['title'], ENT_QUOTES) ?>')">
                                <?= htmlspecialchars($game['title']) ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <!-- FRIEND REQUESTS -->
        <div class="section">
            <h3>Friend Requests</h3>
            <ul id="friendRequests" class="list">
                <?php while ($r = $requests->fetch_assoc()): ?>
                    <li class="item" data-id="<?= $r['id'] ?>">
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
                                    <img src="<?= htmlspecialchars($game['game_image']) ?>" style="width:40px;height:40px;border-radius:6px;margin-right:8px;">
                                <?php endif; ?>
                                <?= htmlspecialchars($game['title']) ?>
                                <!--<button type="button" onclick="openProfile(<?= $game['game_id'] ?>)">View Profile</button>-->
                            </li>   
                </ul>

             </div>
            <div style="display:flex; gap:6px;">
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
    <script>
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

        async function loadMessages() {
            if (!CURRENT_CHAT) return;
            const res = await fetch(`ud_get_messages.php?type=${CURRENT_CHAT.type}&id=${CURRENT_CHAT.id}`);
            const data = await res.json();
            const box = document.getElementById("messages");
            box.innerHTML = "";
            if (!data.length) {
                box.classList.add("empty");
                box.innerHTML = "<div class='empty-placeholder'>No messages yet.</div>";
                return;
            }
            box.classList.remove("empty");
            data.forEach(m => {
                const div = document.createElement("div");
                div.className = "msg";
                div.innerHTML = `<div class="meta"><b>${m.username}</b> — ${m.created_at}</div>
                                 <div class="content">${m.content}</div>`;
                box.appendChild(div);
            });
            box.scrollTop = box.scrollHeight;
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


        // --------------------------
        // FRIEND REQUESTS
        // --------------------------
        async function acceptRequest(id) {
            const res = await fetch("ud_accept_friend.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "request_id=" + id
            });
            const data = await res.json();
            if (data.success) {
                // Remove request from list
                const reqLi = document.querySelector(`#friendRequests li[data-id='${id}']`);
                if (reqLi) reqLi.remove();
                // Add friend to friends list
                addFriendToList(data.friend);
            } else alert(data.error);
        }

        async function declineRequest(id) {
            const res = await fetch("ud_decline_friend.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "request_id=" + id
            });
            const data = await res.json();
            if (data.success) {
                const reqLi = document.querySelector(`#friendRequests li[data-id='${id}']`);
                if (reqLi) reqLi.remove();
            } else alert(data.error);
        }

        // --------------------------
        // ADD FRIEND
        // --------------------------
        document.getElementById("addFriendBtn").addEventListener("click", async () => {
            const username = document.getElementById("friendName").value.trim();
            if (!username) return alert("Enter a username");

            try {
                const res = await fetch("ud_add_friends.php", { // <-- updated filename
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        username
                    })
                });

                const data = await res.json();
                if (!data.success) {
                    return alert(data.error);
                }

                // ✅ Add friend to UI
                const list = document.getElementById("friendsList");
                const li = document.createElement("li");
                li.className = "item";
                li.textContent = data.friend.username;
                list.appendChild(li);

                document.getElementById("friendName").value = "";
                alert("Friend request sent to " + data.friend.username);

            } catch (err) {
                console.error("Error sending friend request:", err);
                alert("Something went wrong. Check console for details.");
            }
        });

        // --------------------------
        // ADD FRIEND TO LIST
        // --------------------------
        function addFriendToList(friend) {
            const li = document.createElement("li");
            li.className = "item";
            li.dataset.id = friend.user_id;

            const span = document.createElement("span");
            span.textContent = friend.username;
            li.appendChild(span);

            // Click to chat
            span.addEventListener("click", () => {
                CURRENT_CHAT = {
                    type: "private",
                    id: friend.user_id,
                    name: friend.username
                };
                document.getElementById("chatTitle").innerText = friend.username + " (Chat)";
                document.getElementById("chatStatus").innerText = "Connected";
                loadMessages();
            });

            // Delete / Unfriend button
            const delBtn = document.createElement("button");
            delBtn.textContent = "Delete";
            delBtn.style.marginLeft = "10px";
            delBtn.addEventListener("click", async () => {
                if (!confirm(`Are you sure you want to unfriend ${friend.username}?`)) return;
                const res = await fetch("ud_remove_friend.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "friend_id=" + friend.user_id
                });
                const data = await res.json();
                if (data.success) li.remove();
                else alert(data.error);
            });
            li.appendChild(delBtn);

            document.getElementById("friendsList").appendChild(li);
        }

        // --------------------------
        // Initial setup for existing friends Delete buttons
        // --------------------------
        document.querySelectorAll("#friendsList .item").forEach(li => {
            const span = li.querySelector("span");
            const delBtn = li.querySelector(".delBtn");
            const friendId = li.dataset.id;
            span.addEventListener("click", () => {
                CURRENT_CHAT = {
                    type: "private",
                    id: friendId,
                    name: span.textContent
                };
                document.getElementById("chatTitle").innerText = span.textContent + " (Chat)";
                document.getElementById("chatStatus").innerText = "Connected";
                loadMessages();
            });
            delBtn.addEventListener("click", async () => {
                if (!confirm(`Are you sure you want to unfriend ${span.textContent}?`)) return;
                const res = await fetch("ud_remove_friend.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "friend_id=" + friendId
                });
                const data = await res.json();
                if (data.success) li.remove();
                else alert(data.error);
            });
        });

        async function refreshFriendRequests() {
            const res = await fetch("ud_get_friend_requests.php");
            const data = await res.json();
            const list = document.getElementById("friendRequests");
            list.innerHTML = "";

            if (!data.length) {
                list.innerHTML = "<li class='empty'>No friend requests.</li>";
                return;
            }

            data.forEach(req => {
                const li = document.createElement("li");
                li.className = "item";
                li.dataset.id = req.request_id;

                const span = document.createElement("span");
                span.textContent = req.username;
                li.appendChild(span);

                const acceptBtn = document.createElement("button");
                acceptBtn.textContent = "Accept";
                acceptBtn.addEventListener("click", () => acceptRequest(req.request_id));
                li.appendChild(acceptBtn);

                const declineBtn = document.createElement("button");
                declineBtn.textContent = "Decline";
                declineBtn.addEventListener("click", () => declineRequest(req.request_id));
                li.appendChild(declineBtn);

                list.appendChild(li);
            });
        }

        // Initial load
        refreshFriendRequests();

        // Poll every 5 seconds for new requests
        setInterval(refreshFriendRequests, 5000);

        async function acceptRequest(id) {
            const res = await fetch("ud_accept_friend.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    request_id: id
                })
            });
            const data = await res.json();
            if (data.success) {
                // Remove request from list
                const li = document.querySelector(`#friendRequests li[data-id='${id}']`);
                if (li) li.remove();

                // Add new friend to friends list
                if (data.friend) addFriendToList(data.friend);
            } else {
                alert(data.error);
            }
        }

        async function declineRequest(id) {
            const res = await fetch("ud_decline_friend.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    request_id: id
                })
            });
            const data = await res.json();
            if (data.success) {
                const li = document.querySelector(`#friendRequests li[data-id='${id}']`);
                if (li) li.remove();
            } else {
                alert(data.error);
            }
        }


        // --------------------------
        // My Games — Update & Delete
        // --------------------------
        document.querySelectorAll("#myGamesList .item").forEach(li => {
            const gameId = li.dataset.id;
            const updateBtn = li.querySelector(".updateGameBtn");
            const deleteBtn = li.querySelector(".deleteGameBtn");

            // Update game
            updateBtn.addEventListener("click", async () => {
                const newTitle = prompt("Enter new game title:", li.querySelector("span").textContent);
                if (!newTitle) return;
                const res = await fetch("update_game.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "game_id=" + gameId + "&title=" + encodeURIComponent(newTitle)
                });
                const data = await res.json();
                if (data.success) {
                    li.querySelector("span").textContent = newTitle;
                    alert("Game updated successfully!");
                } else alert(data.error);
            });

            // Delete game
            deleteBtn.addEventListener("click", async () => {
                if (!confirm("Are you sure you want to remove this game from your collection?")) return;
                const res = await fetch("delete_game.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "game_id=" + gameId
                });
                const data = await res.json();
                if (data.success) li.remove();
                else alert(data.error);
            });
        });

        function openProfile(game_id) {
            fetch(`profile.php?game_id=${game_id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('gameTitle').innerText = data.title;
                        document.getElementById('gameImage').src = data.game_image ?? '';
                        document.getElementById('gameSkins').innerText = data.skins.join(', ') || 'None';
                        document.getElementById('gameRank').innerText = data.rank || 'None';
                        document.getElementById('gameTransaction').innerText = data.transaction || 'None';
                        document.getElementById('gameReview').innerText = data.review || 'No review';
                        document.getElementById('profileModal').style.display = 'block';
                    } else {
                        alert('Failed to load game details.');
                    }
                });
        }


        function closeProfile() {
            document.getElementById('profileModal').style.display = 'none';
        }

        document.querySelector(".more-menu a[href='profile.php']").addEventListener("click", async (e) => {
            e.preventDefault();
            openProfileModal();
        });

        async function openProfileModal() {
            document.getElementById("profileModal").style.display = "block";
            const res = await fetch("profile_games.php"); // a new PHP endpoint to get all user games
            const data = await res.json();

            const list = document.getElementById("profileGamesList");
            list.innerHTML = "";

            if (!data.length) {
                list.innerHTML = "<li>No games in your collection.</li>";
                return;
            }

            data.forEach(game => {
                const li = document.createElement("li");
                li.innerHTML = `
            <b>${game.title}</b>
            <button onclick="viewGameDetails(${game.collection_id})">View Details</button>
        `;
                list.appendChild(li);
            });
        }

        function closeProfile() {
            document.getElementById("profileModal").style.display = "none";
            document.getElementById("gameDetails").innerHTML = "";
        }


        // Load user's games into the modal
        function loadUserGamesProfile() {
            fetch('profile_games.php')
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('userGamesList');
                    list.innerHTML = '';
                    if (!data.length) {
                        list.innerHTML = '<li>No games added.</li>';
                        return;
                    }

                    data.forEach(game => {
                        const li = document.createElement('li');
                        li.style.marginBottom = '10px';
                        li.innerHTML = `
                <b>${game.title}</b>
                <button onclick="openGameDetail(${game.game_id})" style="margin-left:10px;">View Details</button>
            `;
                        list.appendChild(li);
                    });
                });
        }

        // Open details for a specific game
        function openGameDetail(game_id) {
            fetch(`profile_game_details.php?game_id=${game_id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        let details = `
                Skins: ${data.skins.length ? data.skins.join(', ') : 'None'}<br>
                Rank: ${data.rank || 'None'}<br>
                Transaction: ${data.transaction || 'None'}<br>
                Review: ${data.review || 'None'}
            `;
                        alert(`${data.title}\n\n${details}`); // simple popup for now
                    } else alert('Could not load game details');
                });
        }

        async function viewGameDetails(collection_id) {
            const res = await fetch(`profile_game_details.php?collection_id=${collection_id}`);
            const data = await res.json();

            const details = document.getElementById("gameDetails");
            if (!data.success) {
                details.innerHTML = "<p>Error loading game details</p>";
                return;
            }

            details.innerHTML = `
        <h3>${data.title}</h3>
        <p>Rank: ${data.rank}</p>
        <p>Transaction: ${data.transaction}</p>
        <p>Skin: ${data.skin}</p>
        <p>Review: ${data.review}</p>
    `;
        }

    </script>
    


</body>

</html>