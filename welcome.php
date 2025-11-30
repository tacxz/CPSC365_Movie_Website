<?php
session_start();
require 'connect.php';

// login redirect
if (!isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

include 'template.php';

// Current user ID
$currentUserID = $_SESSION['user_id'];

// gets last 10 movies
$stmt = $pdo->prepare("SELECT * FROM Movies ORDER BY movie_ID DESC LIMIT 10");
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// gets 10 most recent comments from friends
$stmtComments = $pdo->prepare("
    SELECT C.comment_ID, C.comment_date, C.comment_content, C.user_commented, M.movie_title
    FROM Comment C
    JOIN User_Has_Friends F ON C.user_commented = (SELECT user_name FROM User WHERE user_ID = F.friend_ID)
    JOIN Movies M ON C.movie_ID = M.movie_ID
    WHERE F.user_ID = ?
    ORDER BY C.comment_date DESC
    LIMIT 10
");
$stmtComments->execute([$currentUserID]);
$friendComments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome - Movie Site</title>
    <link rel="stylesheet" href="style.css">
    <style>
        main {
            color: white;
            text-align: center;
            margin-top: -20px; 
        }

        /* Centered search bar with dark transparent blur */
        .search-bar {
            width: 50%;
            margin: 20px auto;
            display: flex;
            backdrop-filter: blur(10px);
            background: rgba(0, 0, 0, 0.5);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
        }

        .search-bar input[type="text"] {
            flex: 1;
            padding: 12px 15px;
            border: none;
            outline: none;
            background: transparent;
            color: white;
            font-size: 16px;
        }

        .search-bar input::placeholder {
            color: #ccc;
        }

        .search-bar button {
            padding: 12px 20px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        .search-bar button:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .movie-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            grid-gap: 30px;
            justify-items: center;
            margin: 40px auto;
            width: 90%;
            max-width: 1200px;
        }

        .movie-card {
            background: rgba(0, 0, 0, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 15px;
            overflow: hidden;
            padding: 10px;
            width: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .movie-card:hover {
            transform: translateY(-8px);
            box-shadow: 0px 5px 15px rgba(255, 255, 255, 0.2);
        }

        .movie-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            border-radius: 10px;
        }

        .movie-card h3 {
            font-size: 18px;
            margin: 10px 0 5px;
        }

        .movie-card p {
            font-size: 14px;
            color: #ccc;
        }

        .movie-link {
            text-decoration: none;
            color: inherit;
        }

        .movie-link:hover .movie-card {
            transform: translateY(-10px);
        }

        h2 {
            margin-bottom: 20px;
            font-size: 30px;
            letter-spacing: 1px;
            color: #fff;
        }

        /* Friend comments section */
        .friend-comments {
            width: 70%;
            margin: 50px auto;
            text-align: left;
        }

        .friend-comment {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 12px;
        }

        .friend-comment h4 {
            margin: 0 0 5px;
            font-size: 16px;
            color: #fff;
        }

        .friend-comment p {
            margin: 0;
            font-size: 14px;
            color: #ccc;
        }

        .friend-comment span {
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>

<body>
    <main>
        <!-- Search bar -->
        <form class="search-bar" method="get" action="movie_search.php">
            <input type="text" name="query" placeholder="Search for a movie..." required>
            <button type="submit">Search</button>
        </form>

        <h2>Latest Movies</h2>

        <?php if (count($movies) > 0): ?>
            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                    <?php
                        // Average
                        $stmt = $pdo->prepare("SELECT AVG(personal_movie_rating) as avg_rating FROM Movies_Rated WHERE movie_ID = ?");
                        $stmt->execute([$movie['movie_ID']]);
                        $avgRating = $stmt->fetchColumn();
                        $avgRating = $avgRating ? round($avgRating, 1) : 0;
                    ?>
                    <a href="movie.php?id=<?php echo $movie['movie_ID']; ?>" class="movie-link">
                        <div class="movie-card">
                            <img src="image_storage/<?php echo htmlspecialchars($movie['movie_image']); ?>" alt="Movie Cover">
                            <h3><?php echo htmlspecialchars($movie['movie_title']); ?></h3>
                            <p>⭐ <?php echo $avgRating; ?>/5</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>No movies found.</p>
        <?php endif; ?>

        <!-- friends comments -->
        <div class="friend-comments">
            <h2>Friends' Recent Comments</h2>
            <?php if (count($friendComments) > 0): ?>
                <?php foreach ($friendComments as $comment): ?>
                    <div class="friend-comment">
                        <h4><?php echo htmlspecialchars($comment['user_commented']); ?> on <?php echo htmlspecialchars($comment['movie_title']); ?></h4>
                        <p><?php echo htmlspecialchars($comment['comment_content']); ?></p>
                        <span><?php echo htmlspecialchars($comment['comment_date']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recent comments from your friends.</p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
