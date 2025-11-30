<?php
session_start();
require 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['user_name'];
$userID = $_SESSION['user_id'];

// Check movie ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: welcome.php");
    exit;
}

$movieID = intval($_GET['id']);

// Get movie info
$stmt = $pdo->prepare("SELECT * FROM Movies WHERE movie_ID = ?");
$stmt->execute([$movieID]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    header("Location: welcome.php");
    exit;
}

// Check if user already rated movie
$stmt = $pdo->prepare("SELECT personal_movie_rating FROM Movies_Rated WHERE movie_ID = ? AND user_ID = ?");
$stmt->execute([$movieID, $userID]);
$userRating = $stmt->fetchColumn();

// Handle rating submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating']) && !$userRating) {
    $rating = floatval($_POST['rating']);
    if ($rating > 0 && $rating <= 5) {
        $stmt = $pdo->prepare("INSERT INTO Movies_Rated (movie_ID, movie_title, personal_movie_rating, user_ID) VALUES (?, ?, ?, ?)");
        $stmt->execute([$movieID, $movie['movie_title'], $rating, $userID]);
        $userRating = $rating;
    }
}

// Handle new comment submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content']) && trim($_POST['comment_content']) !== '') {
    $commentContent = trim($_POST['comment_content']);
    $stmt = $pdo->prepare("INSERT INTO Comment (comment_date, comment_content, user_commented, movie_ID) VALUES (CURDATE(), ?, ?, ?)");
    $stmt->execute([$commentContent, $username, $movieID]);
}

// Handle friend request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_friend'])) {
    $friendUsername = $_POST['add_friend'];

    $stmt = $pdo->prepare("SELECT user_ID FROM User WHERE user_name = ?");
    $stmt->execute([$friendUsername]);
    $receiverID = $stmt->fetchColumn();

    if ($receiverID) {
        $stmt = $pdo->prepare("SELECT request_status FROM Friend_Requests 
                               WHERE sender_ID = ? AND receiver_ID = ?");
        $stmt->execute([$userID, $receiverID]);

        if (!$stmt->fetchColumn()) {
            $stmt = $pdo->prepare("INSERT INTO Friend_Requests (sender_ID, receiver_ID, request_status)
                                   VALUES (?, ?, 'pending')");
            $stmt->execute([$userID, $receiverID]);
        }
    }
}

// Get comments
$stmt = $pdo->prepare("SELECT * FROM Comment WHERE movie_ID = ? ORDER BY comment_date DESC");
$stmt->execute([$movieID]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Average rating
$stmt = $pdo->prepare("SELECT AVG(personal_movie_rating) as avg_rating FROM Movies_Rated WHERE movie_ID = ?");
$stmt->execute([$movieID]);
$avgRating = $stmt->fetchColumn();
$avgRating = $avgRating ? round($avgRating, 1) : 0;


// ---------- FRIENDSHIP CHECK FUNCTIONS ----------
function areFriends($pdo, $userID, $otherUsername) {
    $stmt = $pdo->prepare("SELECT user_ID FROM User WHERE user_name = ?");
    $stmt->execute([$otherUsername]);
    $otherID = $stmt->fetchColumn();
    if (!$otherID) return false;

    $check = $pdo->prepare("
        SELECT request_status FROM Friend_Requests
        WHERE (
            (sender_ID = ? AND receiver_ID = ?)
            OR
            (sender_ID = ? AND receiver_ID = ?)
        )
        AND request_status = 'accepted'
    ");
    $check->execute([$userID, $otherID, $otherID, $userID]);

    return $check->fetchColumn() ? true : false;
}

function isPendingRequest($pdo, $userID, $otherUsername) {
    $stmt = $pdo->prepare("SELECT user_ID FROM User WHERE user_name = ?");
    $stmt->execute([$otherUsername]);
    $otherID = $stmt->fetchColumn();
    if (!$otherID) return false;

    $check = $pdo->prepare("
        SELECT request_status FROM Friend_Requests
        WHERE (
            (sender_ID = ? AND receiver_ID = ?)
            OR
            (sender_ID = ? AND receiver_ID = ?)
        )
        AND request_status = 'pending'
    ");
    $check->execute([$userID, $otherID, $otherID, $userID]);

    return $check->fetchColumn() ? true : false;
}


include 'template.php';
?>

<!-- BEGIN PAGE CONTENT -->
<main style="margin-top: -50px; text-align:center; color:white;">
    <div style="display:flex; flex-direction:column; align-items:center; gap:10px;">

        <!-- Movie Image -->
        <img src="image_storage/<?php echo htmlspecialchars($movie['movie_image']); ?>" 
             alt="Movie Image" 
             style="max-width:600px; width:90%; height:auto; border-radius:10px;">

        <!-- Star Rating -->
        <?php if (!$userRating): ?>
            <form method="POST" id="rating-form" style="display:flex; flex-direction:column; align-items:center; gap:5px;">
                <div id="star-rating" style="display:flex; justify-content:center; gap:5px; cursor:pointer;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star" data-value="<?= $i ?>" style="font-size:40px; color:#555;">&#9733;</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="rating-input">
                <button type="submit" class="btn" style="width:200px; padding:10px 0; font-size:16px;">Submit Rating</button>
            </form>
        <?php else: ?>
            <p>Your Rating: <?= $userRating ?> / 5</p>
        <?php endif; ?>

        <h1><?= htmlspecialchars($movie['movie_title']) ?></h1>
        <h3>Genre: <?= htmlspecialchars($movie['genre']) ?></h3>

        <p style="max-width:600px;"><?= nl2br(htmlspecialchars($movie['movie_description'])) ?></p>

        <h4>Average Rating: <?= $avgRating ?> / 5</h4>

        <!-- Comment submit -->
        <div style="margin-top:20px; max-width:600px; width:90%; text-align:left;">
            <h3>Leave a Comment</h3>
            <form method="POST" style="display:flex; flex-direction:column; gap:5px;">
                <textarea name="comment_content" placeholder="Write your comment..." 
                          style="width:100%; padding:10px; border-radius:8px; border:2px solid rgba(255,255,255,0.3); background:rgba(0,0,0,0.4); color:white;" rows="3"></textarea>
                <button type="submit" class="btn" style="width:150px;">Post Comment</button>
            </form>
        </div>

        <!-- Comments -->
        <div style="margin-top:20px; max-width:600px; width:90%; text-align:left;">
            <h3>Comments</h3>
            <div style="max-height:300px; overflow-y:auto;">

                <?php foreach ($comments as $comment): ?>
                    <div style="background:rgba(0,0,0,0.4); padding:10px; border-radius:8px; margin-bottom:10px; display:flex; justify-content:space-between;">

                        <span><strong><?= htmlspecialchars($comment['user_commented']) ?>:</strong> 
                            <?= htmlspecialchars($comment['comment_content']) ?>
                        </span>

                        <?php
                        $commentUser = $comment['user_commented'];
                        $showAddButton = false;

                        if ($commentUser !== $username) {
                            if (!areFriends($pdo, $userID, $commentUser) &&
                                !isPendingRequest($pdo, $userID, $commentUser)) {
                                $showAddButton = true;
                            }
                        }
                        ?>

                        <?php if ($showAddButton): ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="add_friend" value="<?= htmlspecialchars($commentUser) ?>">
                                <button title="Send friend request"
                                        style="width:36px; height:36px; border-radius:50%; background:#fff; border:none; cursor:pointer;">
                                    +
                                </button>
                            </form>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>
</main>

<script>
const stars = document.querySelectorAll('#star-rating .star');
const ratingInput = document.getElementById('rating-input');
let selectedRating = 0;

stars.forEach((star, index) => {
    star.addEventListener('mouseover', () => {
        stars.forEach((s, i) => s.style.color = i <= index ? '#ffcc00' : '#555');
    });

    star.addEventListener('click', () => {
        selectedRating = index + 1;
        ratingInput.value = selectedRating;
        stars.forEach((s, i) => s.style.color = i <= index ? '#ffcc00' : '#555');
    });

    star.addEventListener('mouseout', () => {
        stars.forEach((s, i) => s.style.color = i < selectedRating ? '#ffcc00' : '#555');
    });
});
</script>
