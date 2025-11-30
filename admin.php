<?php
session_start();
require 'connect.php';

// User must be logged in
if (!isset($_SESSION['user_name'])) {
    header("Location: index.php");
    exit;
}

// Check if admin
$stmt = $pdo->prepare("SELECT admin_access FROM User WHERE user_name = :username");
$stmt->execute([':username' => $_SESSION['user_name']]);
$isAdmin = $stmt->fetchColumn();

if ($isAdmin != 1) {
    header("Location: welcome.php");
    exit;
}

include 'template.php';

$title = $description = $genre = "";
$message = "";
$messageType = "";

// Folder path for image uploads
$uploadDir = "C:/xampp/htdocs/MovieSite/image_storage/";

// Form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["movie_title"]);
    $description = trim($_POST["movie_description"]);
    $genre = trim($_POST["genre"]);

    if ($title === "" || $description === "" || $genre === "") {
        $message = "All fields are required.";
        $messageType = "error";
    } elseif (!isset($_FILES['movie_image']) || $_FILES['movie_image']['error'] !== UPLOAD_ERR_OK) {
        $message = "Please upload a valid movie cover image.";
        $messageType = "error";
    } else {
        // Upload
        $fileTmpPath = $_FILES['movie_image']['tmp_name'];
        $fileName = basename($_FILES['movie_image']['name']);
        $fileType = mime_content_type($fileTmpPath);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        if (!in_array($fileType, $allowedTypes)) {
            $message = "Only JPG, PNG, or WEBP files are allowed.";
            $messageType = "error";
        } else {
            // Name for image file
            $uniqueFileName = uniqid("movie_", true) . "_" . $fileName;
            $destPath = $uploadDir . $uniqueFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO Movies (movie_title, movie_description, genre, movie_image) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $genre, $uniqueFileName]);
                    $message = "Movie added successfully!";
                    $messageType = "success";
                    $title = $description = $genre = "";
                } catch (PDOException $e) {
                    $message = "Error adding movie: " . htmlspecialchars($e->getMessage());
                    $messageType = "error";
                }
            } else {
                $message = "Error uploading image file.";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Add Movie</title>
    <link rel="stylesheet" href="style.css">
    <style>
        main {
            color: white;
            text-align: center;
            margin-top: 30px; 
        }

        .form-container {
            width: 420px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            padding: 30px 25px;
            backdrop-filter: blur(15px);
        }

        .form-container h2 {
            margin-bottom: 20px;
            font-size: 28px;
            color: #fff;
        }

        .input-box {
            margin: 15px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .input-box input,
        .input-box textarea {
            width: 100%;
            height: 50px;
            padding: 12px 15px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: transparent;
            color: white;
            font-size: 15px;
            outline: none;
            box-sizing: border-box;
        }

        .input-box textarea {
            height: 100px;
            resize: none;
        }

       
        input[type="file"] {
            color: #ccc;
            font-size: 15px;
            height: 90px; 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        input[type="file"]::-webkit-file-upload-button {
            background: #fff;
            border: none;
            border-radius: 20px;
            padding: 8px 16px; 
            cursor: pointer;
            font-weight: 600;
        }

        input[type="file"]::-webkit-file-upload-button:hover {
            background: #ccc;
        }

        .btn {
            width: 100%;
            height: 45px;
            border-radius: 40px;
            border: none;
            background: #fff;
            color: #222;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn:hover {
            background: #ccc;
        }

        .form-message {
            margin-top: 15px;
            font-size: 15px;
            font-weight: 500;
        }

        .form-message.success {
            color: #4caf50;
        }

        .form-message.error {
            color: #ff4d4d;
        }
    </style>
</head>

<body>
    <main>
        <div class="form-container">
            <h2>Add New Movie</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="input-box">
                    <input type="text" name="movie_title" placeholder="Movie Title" value="<?php echo htmlspecialchars($title); ?>">
                </div>
                <div class="input-box">
                    <textarea name="movie_description" placeholder="Movie Description"><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="input-box">
                    <input type="text" name="genre" placeholder="Genre" value="<?php echo htmlspecialchars($genre); ?>">
                </div>
                <div class="input-box">
                    <input type="file" name="movie_image" accept="image/*">
                </div>
                <button type="submit" class="btn">Add Movie</button>

                <?php if ($message): ?>
                    <div class="form-message <?php echo $messageType; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </main>
</body>
</html>
