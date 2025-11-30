<?php
session_start();
require 'connect.php';

// login redirect
if (!isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

include 'template.php';

// Get search query
$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

// Prevent empty query
if ($searchQuery === '') {
    header("Location: welcome.php");
    exit;
}

// Search for movies (case-insensitive, partial match)
$stmt = $pdo->prepare("SELECT * FROM Movies WHERE movie_title LIKE ? ORDER BY movie_ID DESC");
$stmt->execute(["%$searchQuery%"]);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</title>
    <link rel="stylesheet" href="style.css">
    <style>
        main {
            color: white;
            text-align: center;
            margin-top: -20px;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 30px;
            letter-spacing: 1px;
            color: #fff;
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

        /* Back button */
        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 20px;
            border-radius: 12px;
            border: 2px solid #fff;
            background: transparent;
            color: white;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-btn:hover {
            background: white;
            color: black;
        }
    </style>
</head>
<body>
    <main>
        <h2>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>

        <a href="welcome.php" class="back-btn">← Back to Welcome</a>

        <?php if (count($movies) > 0): ?>
            <div class="movie-grid">
                <?php foreach ($movies as $movie): ?>
                    <?php
                        // Average rating
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
            <p>No movies found matching "<?php echo htmlspecialchars($searchQuery); ?>"</p>
        <?php endif; ?>
    </main>
</body>
</html>
