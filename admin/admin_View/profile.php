<?php
session_start();
include("../../db.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch user info
$userQuery = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result()->fetch_assoc();

// Fetch games uploaded by the user from collections
$gameQuery = $conn->prepare("SELECT g.game_id, g.title, g.game_image
                             FROM games g
                             JOIN user_game_collection ON g.game_id = user_game_collection.game_id
                             WHERE user_game_collection.user_id = ?");
$gameQuery->bind_param("i", $user_id);
$gameQuery->execute();
$gameResult = $gameQuery->get_result();
$games = $gameResult->fetch_all(MYSQLI_ASSOC);

// Handle user search
$searchResults = [];
$searchUsername = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $searchUsername = trim($_GET['search']);
    $searchQuery = $conn->prepare("SELECT username, email FROM users WHERE username LIKE ?");
    $likeSearch = "%" . $searchUsername . "%";
    $searchQuery->bind_param("s", $likeSearch);
    $searchQuery->execute();
    $searchResults = $searchQuery->get_result();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
        }
        .top-bar {
            background: #1e293b;
            color: white;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .top-bar form {
            display: flex;
            gap: 10px;
        }
        .top-bar input[type="text"] {
            padding: 6px;
            border-radius: 5px;
            border: none;
        }
        .top-bar button {
            background-color: #090493;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
        }
        .main-content {
            display: flex;
            flex: 1;
        }
        .chatbox {
            width: 40%;
            padding: 20px;
            background: #f0f0f0;
            border-right: 2px solid #ccc;
            display: flex;
            flex-direction: column;
        }
        .chatbox h2 {
            margin-bottom: 10px;
        }
        .chat-messages {
            flex: 1;
            overflow-y: scroll;
            border: 1px solid #aaa;
            padding: 10px;
            background: white;
            margin-bottom: 10px;
        }
        .chatbox form {
            display: flex;
        }
        .chatbox input[type="text"] {
            flex: 1;
            padding: 8px;
        }
        .chatbox button {
            padding: 8px;
            background-color: #090493;
            color: white;
            border: none;
        }

        .profile-info {
            width: 60%;
            padding: 20px;
            background: #ffffff;
        }

        .user-details {
            margin-bottom: 30px;
        }

        .games {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .game-card {
            width: 150px;
            border: 1px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            text-align: center;
            background-color: #f9f9f9;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .game-card button {
            all: unset;
            cursor: pointer;
            width: 100%;
        }

        .game-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }

        .game-card p {
            padding: 10px;
            margin: 0;
            font-weight: bold;
        }

        .search-results {
            background: #f8f8f8;
            padding: 10px;
            margin: 10px 20px;
            border-radius: 5px;
        }

        .search-results p {
            margin: 5px 0;
        }
    </style>
</head>
<body>

    <!-- Top Search Bar -->
    <div class="top-bar">
        <div><strong>Welcome, <?php echo htmlspecialchars($username); ?></strong></div>
        <form method="GET" action="">
            <input type="text" name="search" placeholder="Search usernames..." value="<?php echo htmlspecialchars($searchUsername); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (!empty($searchUsername)): ?>
    <div class="search-results">
        <h3>Search Results for "<?php echo htmlspecialchars($searchUsername); ?>"</h3>
        <?php if ($searchResults->num_rows > 0): ?>
            <?php while ($row = $searchResults->fetch_assoc()): ?>
                <p>👤 <?php echo htmlspecialchars($row['username']); ?> (<?php echo htmlspecialchars($row['email']); ?>)</p>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="main-content">
        <div class="chatbox">
            <h2>Community Chat</h2>
            <div class="chat-messages" id="chat-messages">
                <!-- Chat messages here -->
            </div>
            <form id="chat-form">
                <input type="text" name="message" placeholder="Type your message...">
                <button type="submit">Send</button>
            </form>
        </div>

        <div class="profile-info">
            <div class="user-details">
                <h2><?php echo htmlspecialchars($userResult['username']); ?>'s Profile</h2>
                <p>Email: <?php echo htmlspecialchars($userResult['email']); ?></p>
            </div>

            <div class="user-games">
                <h3>Your Games</h3>
                <div class="games">
                    <?php foreach ($games as $game): ?>
                        <form class="game-card" method="GET" action="game_details.php">
                            <input type="hidden" name="game_id" value="<?php echo htmlspecialchars($game['game_id']); ?>">
                            <button type="submit">
                                <img src="../admin_manage/uploads/<?php echo htmlspecialchars($game['game_image']); ?>" alt="Game Image">
                                <p><?php echo htmlspecialchars($game['title']); ?></p>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Optional: Add AJAX here for real-time chat
        document.getElementById('chat-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const messageInput = this.querySelector('input[name="message"]');
            const message = messageInput.value;
            if (message.trim() !== "") {
                const msgBox = document.getElementById('chat-messages');
                msgBox.innerHTML += `<div><strong>You:</strong> ${message}</div>`;
                messageInput.value = "";
            }
        });
    </script>
</body>
</html>

