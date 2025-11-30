<?php
session_start();
require 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$currentUserID = $_SESSION['user_id'];

    // Gets the friend list
$stmt = $pdo->prepare("
    SELECT friend_ID 
    FROM User_Has_Friends
    WHERE user_ID = ?
");
$stmt->execute([$currentUserID]);
$friends = $stmt->fetchAll(PDO::FETCH_COLUMN);

// If no friends, stop
if (empty($friends)) {
    include 'template.php';
    echo "<div style='margin:80px auto;width:600px;color:white;text-align:center;'>
            <h2>Recommendations</h2>
            <p>You need friends before we can recommend movies!</p>
          </div>";
    exit;
}

$friendList = implode(',', array_map('intval', $friends));

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Recommendations</title>
<link rel="stylesheet" href="style.css">
<style>
.recommend-box {
    width: 780px;
    max-width: 92%;
    margin: 80px auto;
    padding: 25px;
    border-radius: 14px;
    background: rgba(0, 0, 0, 0.45);
    backdrop-filter: blur(10px);
    color: white;
}
.movie-card {
    margin: 14px 0;
    padding: 14px;
    border-radius: 10px;
    background: rgba(255,255,255,0.07);
}
.movie-title {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 8px;
}
.movie-count {
    color: #ccc;
}
</style>
</head>
<body>

<?php include 'template.php'; ?>

<div class="recommend-box">
    <h2>Recommended For You</h2>
    <p>Movies your friends rated highly.</p>
    <hr><br>

<?php
// gets movies friends rated > 3 stars
$sql = "
    SELECT 
        Movies.movie_ID,
        Movies.movie_title,
        Movies.movie_image,
        COUNT(Movies_Rated.user_ID) AS rating_count
    FROM Movies_Rated
    INNER JOIN Movies ON Movies.movie_ID = Movies_Rated.movie_ID
    WHERE Movies_Rated.user_ID IN ($friendList)
      AND Movies_Rated.personal_movie_rating >= 4
    GROUP BY Movies.movie_ID
    ORDER BY rating_count DESC
    LIMIT 10
";

$stmt = $pdo->query($sql);
$recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($recommendations)) {
    echo "<p>No recommendations yet. Your friends must rate some movies first.</p></div>";
    exit;
}

// Displays movies fetched
foreach ($recommendations as $movie):
?>
    <div class="movie-card">
        <div class="movie-title">
            <?= htmlspecialchars($movie['movie_title']) ?>
        </div>

        <?php if (!empty($movie['movie_image'])): ?>
            <img src="<?= htmlspecialchars($movie['movie_image']) ?>" width="130">
        <?php endif; ?>

        <p class="movie-count">
            Recommended by <?= intval($movie['rating_count']) ?> friend(s)
        </p>

        <a href="movie.php?id=<?= intval($movie['movie_ID']) ?>"
           style="color: #9cf; text-decoration: underline;">
           View Movie
        </a>
    </div>
<?php endforeach; ?>

</div>

</body>
</html>
