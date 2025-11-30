<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'connect.php';

// Initialize defaults
$profileButtonContents = '?';
$_SESSION['admin_access'] = $_SESSION['admin_access'] ?? 0;
$_SESSION['profile_picture'] = $_SESSION['profile_picture'] ?? null;
$_SESSION['user_name'] = $_SESSION['user_name'] ?? null;

// If logged in, fetch admin + profile_picture (and verify user exists)
if (!empty($_SESSION['user_name'])) {
    $stmt = $pdo->prepare("SELECT admin_access, profile_picture FROM User WHERE user_name = :username LIMIT 1");
    $stmt->execute([':username' => $_SESSION['user_name']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData !== false) {
        $_SESSION['admin_access'] = isset($userData['admin_access']) ? (int)$userData['admin_access'] : 0;
        $_SESSION['profile_picture'] = !empty($userData['profile_picture']) ? $userData['profile_picture'] : null;
    } else {
        $_SESSION['admin_access'] = 0;
        $_SESSION['profile_picture'] = null;
    }
}

// Build profile button contents
if (!empty($_SESSION['profile_picture'])) {
    $imgFile = htmlspecialchars(basename($_SESSION['profile_picture']));
    $profileButtonContents = '<img src="profiles/' . $imgFile . '" alt="Profile" style="width:100%; height:100%; object-fit:cover; display:block;">';
} else {
    $profileLetter = '?';
    if (!empty($_SESSION['user_name'])) {
        $profileLetter = strtoupper(substr($_SESSION['user_name'], 0, 1));
    }
    $profileButtonContents = '<span class="profile-letter">' . htmlspecialchars($profileLetter) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Moviez Template</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            background: url('background.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Poppins', sans-serif;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        main {
            flex: 1;
            padding-top: 120px;
            text-align: center;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 90%;
            padding: 20px 100px;
            display: flex;
            align-items: center;
            z-index: 99;
            backdrop-filter: blur(20px);
        }

        .user-auth {
            margin-left: 40px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .user-auth .admin-btn,
        .user-auth .movies-btn,
        .user-auth .friends-btn,
        .user-auth .logout-btn,
        .user-auth .recs-btn {
            height: 40px;
            padding: 0 35px;
            background: transparent;
            border: 2px solid #fff;
            border-radius: 40px;
            font-size: 16px;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            transition: .5s;
        }

        .user-auth .admin-btn:hover,
        .user-auth .movies-btn:hover,
        .user-auth .friends-btn:hover,
        .user-auth .logout-btn:hover,
        .user-auth .recs-btn:hover {
            background: #fff;
            color: #222;
        }

        /* PROFILE BUTTON */
        .profile-btn {
            width: 42px;
            height: 42px;
            background: rgba(255,255,255,0.25);
            border: 2px solid rgba(255,255,255,0.4);
            backdrop-filter: blur(8px);
            border-radius: 50%;
            color: white;
            font-size: 18px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
            transition: 0.25s;
            overflow: hidden;
        }

        .profile-btn:hover {
            transform: scale(1.08);
            background: rgba(255,255,255,0.35);
        }

        .profile-letter {
            display: inline-flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
        }

        .profile-btn img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>

<header>
    <a href="welcome.php" class="logo">Moviez</a>
    <a class="title">Welcome to Moviez!</a>

    <nav></nav>

    <div class="user-auth">

        <!-- Admin sees Admin button -->
        <?php if (!empty($_SESSION['admin_access']) && $_SESSION['admin_access'] == 1): ?>
            <a href="admin.php"><button type="button" class="admin-btn">Admin</button></a>
        <?php endif; ?>

        <!-- NEW Recommendations Button -->
        <a href="recommendations.php"><button type="button" class="recs-btn">Recommendations</button></a>

        <!-- Movies -->
        <a href="movie.php"><button type="button" class="movies-btn">Movies</button></a>

        <!-- Friends -->
        <a href="friends.php"><button type="button" class="friends-btn">Friends</button></a>

        <!-- Profile Picture Button -->
        <a href="profile.php" class="profile-btn" title="Profile">
            <?php echo $profileButtonContents; ?>
        </a>

        <!-- Logout -->
        <form action="logout.php" method="post" style="display:inline;">
            <button type="submit" class="logout-btn">Logout</button>
        </form>

    </div>
</header>

<main>
