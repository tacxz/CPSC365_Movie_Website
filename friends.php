<?php
session_start();
require 'connect.php';

// Redirect if not logged in
$currentUserID = $_SESSION['user_id']; // lowercase id
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include 'template.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Friends</title>
<style>
/* Container styling */
.friends-container {
    width: 60%;
    margin: 40px auto;
    background: rgba(0, 0, 0, 0.5);
    padding: 25px;
    border-radius: 20px;
    text-align: center;
}

/* Boxes for requests or friends */
.request-box, .friend-box {
    background: rgba(255, 255, 255, 0.1);
    padding: 15px;
    margin-bottom: 15px;
    border-radius: 12px;
    font-size: 18px;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.request-actions button, .friend-box button {
    padding: 6px 15px;
    border-radius: 12px;
    border: 2px solid #fff;
    background: transparent;
    color: white;
    cursor: pointer;
    transition: 0.3s;
}

.request-actions button:hover, .friend-box button:hover {
    background: white;
    color: black;
}

.request-username, .friend-name {
    font-weight: bold;
    color: #fff;
}
</style>
</head>
<body>

<!-- FRIEND REQUESTS -->
<div class="friends-container">
    <h2>Friend Requests</h2>
    <div id="request-list">
        <?php
        $stmt = $pdo->prepare("
            SELECT FR.request_ID, U.user_name AS sender_name
            FROM Friend_Requests FR
            JOIN User U ON FR.sender_ID = U.user_ID
            WHERE FR.receiver_ID = ? AND FR.request_status = 'pending'
        ");
        $stmt->execute([$currentUserID]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($requests)) {
            echo "<p>No pending friend requests.</p>";
        } else {
            foreach ($requests as $req) {
                echo '<div class="request-box" id="req-'.$req['request_ID'].'">
                        <span class="request-username">'.htmlspecialchars($req['sender_name']).'</span>
                        <div class="request-actions">
                            <button onclick="handleRequest('.$req['request_ID'].', \'accept\')">Accept</button>
                            <button onclick="handleRequest('.$req['request_ID'].', \'reject\')">Reject</button>
                        </div>
                      </div>';
            }
        }
        ?>
    </div>
</div>

<!-- YOUR FRIENDS -->
<div class="friends-container">
    <h2>Your Friends</h2>
    <div id="friend-list">
        <?php
        $stmt = $pdo->prepare("
            SELECT U.user_ID, U.user_name
            FROM User_Has_Friends F
            JOIN User U ON F.friend_ID = U.user_ID
            WHERE F.user_ID = ?
        ");
        $stmt->execute([$currentUserID]);
        $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($friends)) {
            echo "<p>You have no friends yet.</p>";
        } else {
            foreach ($friends as $f) {
                echo '<div class="friend-box" id="friend-'.$f['user_ID'].'">
                        <span class="friend-name">'.htmlspecialchars($f['user_name']).'</span>
                        <button onclick="removeFriend('.$f['user_ID'].')">Remove</button>
                      </div>';
            }
        }
        ?>
    </div>
</div>

<script>
// Accept or reject a friend request
function handleRequest(requestID, action) {
    const formData = new FormData();
    formData.append("requestID", requestID);
    formData.append("action", action);

    fetch("friend_request_action.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(response => {
        if (response === "success") {
            if (action === 'accept') {
                // Reload page to show the new friend
                location.reload();
            } else {
                // Remove rejected request box
                document.getElementById("req-" + requestID).remove();
            }
        } else {
            alert("Error processing request.");
        }
    });
}

// Remove a friend
function removeFriend(friendID) {
    const formData = new FormData();
    formData.append("friendID", friendID);

    fetch("remove_friend.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(response => {
        if (response === "success") {
            document.getElementById("friend-" + friendID).remove();
        } else {
            alert("Error removing friend.");
        }
    });
}
</script>

</body>
</html>
