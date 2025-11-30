<?php
session_start();
require 'connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "error";
    exit;
}

$currentUserID = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friendID'])) {
    $friendID = intval($_POST['friendID']);

    try {
        $pdo->beginTransaction();

        // Delete mutual friendships
        $stmtDeleteFriends = $pdo->prepare("
            DELETE FROM User_Has_Friends 
            WHERE (user_ID = ? AND friend_ID = ?) OR (user_ID = ? AND friend_ID = ?)
        ");
        $stmtDeleteFriends->execute([$currentUserID, $friendID, $friendID, $currentUserID]);

        // Delete any pending/accepted friend requests between the users
        $stmtDeleteRequests = $pdo->prepare("
            DELETE FROM Friend_Requests 
            WHERE (sender_ID = ? AND receiver_ID = ?) OR (sender_ID = ? AND receiver_ID = ?)
        ");
        $stmtDeleteRequests->execute([$currentUserID, $friendID, $friendID, $currentUserID]);

        $pdo->commit();
        echo "success";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Remove friend error: " . $e->getMessage());
        echo "error";
        exit;
    }
} else {
    echo "error";
    exit;
}
?>
