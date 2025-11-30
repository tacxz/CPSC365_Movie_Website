<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require 'connect.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "error";
    exit;
}

$currentUserID = $_SESSION['user_id'];

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Accept or reject a friend request
    if (isset($_POST['requestID']) && isset($_POST['action'])) {
        $requestID = intval($_POST['requestID']);
        $action = $_POST['action'];

        // Fetch the request details
        $stmt = $pdo->prepare("SELECT sender_ID, receiver_ID FROM Friend_Requests WHERE request_ID = ?");
        $stmt->execute([$requestID]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            echo "error";
            exit;
        }

        $senderID = $request['sender_ID'];
        $receiverID = $request['receiver_ID'];

        if ($action === 'accept') {
            try {
                $pdo->beginTransaction();

                // Update the request status
                $stmtUpdate = $pdo->prepare("UPDATE Friend_Requests SET request_status = 'accepted' WHERE request_ID = ?");
                $stmtUpdate->execute([$requestID]);

                // Insert both directions into User_Has_Friends if not already friends
                $stmtCheck = $pdo->prepare("SELECT * FROM User_Has_Friends WHERE user_ID = ? AND friend_ID = ?");
                $stmtCheck->execute([$receiverID, $senderID]);
                $exists = $stmtCheck->fetch();

                if (!$exists) {
                    // Insert each direction separately
                    $stmtInsert1 = $pdo->prepare("INSERT INTO User_Has_Friends (user_ID, friend_ID) VALUES (?, ?)");
                    $stmtInsert1->execute([$receiverID, $senderID]);

                    $stmtInsert2 = $pdo->prepare("INSERT INTO User_Has_Friends (user_ID, friend_ID) VALUES (?, ?)");
                    $stmtInsert2->execute([$senderID, $receiverID]);
                }

                $pdo->commit();
                echo "success";
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Friend accept error: " . $e->getMessage());
                echo "error";
                exit;
            }
        } elseif ($action === 'reject') {
            $stmtDelete = $pdo->prepare("DELETE FROM Friend_Requests WHERE request_ID = ?");
            $stmtDelete->execute([$requestID]);
            echo "success";
            exit;
}

    } else {
        echo "error";
        exit;
    }
} else {
    echo "error";
    exit;
}
?>
