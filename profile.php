<?php
session_start();
require 'connect.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$currentUserID = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? '';

// id check
$viewUserID = null;
$viewingOther = false;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $viewUserID = intval($_GET['id']);
    if ($viewUserID !== $currentUserID) {
        $viewingOther = true;
    }
}


$targetUserID = $viewingOther ? $viewUserID : $currentUserID;

// directory
$profilesDir = __DIR__ . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR;
if (!is_dir($profilesDir)) {
    @mkdir($profilesDir, 0755, true);
}

// retrieval
$stmt = $pdo->prepare("SELECT user_ID, user_name, profile_picture, user_bio FROM User WHERE user_ID = ?");
$stmt->execute([$targetUserID]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: welcome.php');
    exit;
}

// Handle profile image upload
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$viewingOther && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['name'] !== '') {
    $file = $_FILES['profile_pic'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadError = 'Upload error.';
    } else {
        $tmp = $file['tmp_name'];
        $mime = mime_content_type($tmp);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowed)) {
            $uploadError = 'Only JPG / PNG / WEBP allowed.';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = 'profile_' . $currentUserID . '_' . time() . '.' . $ext;
            $dest = $profilesDir . $safeName;
            if (move_uploaded_file($tmp, $dest)) {
                $stmt = $pdo->prepare("UPDATE User SET profile_picture = ? WHERE user_ID = ?");
                $stmt->execute([$safeName, $currentUserID]);
                $user['profile_picture'] = $safeName;
            } else {
                $uploadError = 'Failed to move uploaded file.';
            }
        }
    }
}

// Handle bio update
$bioMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$viewingOther && isset($_POST['user_bio'])) {
    $newBio = substr(trim($_POST['user_bio']), 0, 500);
    $stmt = $pdo->prepare("UPDATE User SET user_bio = ? WHERE user_ID = ?");
    $stmt->execute([$newBio, $currentUserID]);
    $user['user_bio'] = $newBio;
    $bioMessage = 'Bio saved.';
}

// Fetch friends
$stmt = $pdo->prepare("
    SELECT u.user_ID, u.user_name, u.profile_picture
    FROM User_Has_Friends f
    JOIN User u ON u.user_ID = f.friend_ID
    WHERE f.user_ID = ?
");
$stmt->execute([$targetUserID]);
$friends = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch 10 most recent movie comments for this profile
$stmt = $pdo->prepare("
    SELECT 
        Movies.movie_ID,
        Movies.movie_title,
        Comment.comment_content,
        Comment.comment_date
    FROM Comment
    INNER JOIN Movies ON Comment.movie_ID = Movies.movie_ID
    WHERE Comment.user_commented = ?
    ORDER BY Comment.comment_date DESC
    LIMIT 10
");
$stmt->execute([$user['user_name']]);
$recentComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// helper
function profile_picture_markup($userRow) {
    if (!empty($userRow['profile_picture'])) {
        $url = 'profiles/' . htmlspecialchars($userRow['profile_picture']);
        return '<img src="' . $url . '" alt="    " />';
    } else {
        $letter = strtoupper(substr($userRow['user_name'], 0, 1));
        return '<div class="avatar-letter">' . htmlspecialchars($letter) . '</div>';
    }
}

include 'template.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title><?php echo htmlspecialchars($user['user_name']); ?> — Profile</title>
<style>
main {
    color: white;
    text-align: center;
    margin-top: -50px;
}
.profile-wrap {
    width: 760px;
    max-width: 92%;
    margin: 20px auto 20px;
    background: rgba(0,0,0,0.45);
    border-radius: 14px;
    padding: 28px;
    backdrop-filter: blur(10px);
}
.avatar-large {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    margin: 0 auto 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: rgba(255,255,255,0.06);
    border: 2px solid rgba(255,255,255,0.15);
    cursor: pointer;
}
.avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.avatar-letter {
    font-size: 72px;
    font-weight: 700;
    color: #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    width:100%;
    height:100%;
}
.msg {
    color: #ffd;
    margin-top: 8px;
}
.bio-area textarea {
    width: 80%;
    max-width:640px;
    min-height:120px;
    border-radius: 10px;
    padding: 12px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
    color: white;
    font-size:15px;
}
.btn {
    margin-top:10px;
    padding:10px 20px;
    border-radius:40px;
    background:white;
    color:#222;
    border:none;
    cursor:pointer;
    font-weight:600;
}
.friends-grid {
    margin-top: 26px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:12px;
    text-align:left;
}
.friend-card {
    background:rgba(255,255,255,0.03);
    border-radius:8px;
    padding:10px;
    display:flex;
    gap:10px;
    align-items:center;
}
.friend-card img {
    width:44px;
    height:44px;
    border-radius:50%;
    object-fit:cover;
}
.view-profile {
    margin-left:auto;
    font-size:13px;
    color:#fff;
    text-decoration:none;
}


.recent-comment-item {
    padding: 12px;
    margin-bottom: 10px;
    background: rgba(255,255,255,0.08);
    border-radius: 8px;
    text-align: left;
}
.recent-comment-item a {
    color:#fff;
    text-decoration:underline;
}
.comment-text {
    margin: 6px 0;
    color:#eaeaea;
}
.comment-date {
    opacity:0.75;
    font-size:13px;
}

#profile_pic_input { display:none; }
</style>
</head>
<body>
<main>
    <div class="profile-wrap">
        <h2><?php echo htmlspecialchars($user['user_name']); ?><?php if($viewingOther) echo " (Viewing)"; ?></h2>

        <!-- PFP -->
        <?php if (!$viewingOther): ?>
            <form method="post" enctype="multipart/form-data" id="picForm">
                <label for="profile_pic_input" class="avatar-large">
                    <?php echo profile_picture_markup($user); ?>
                </label>
                <input id="profile_pic_input" name="profile_pic" type="file" accept="image/*" onchange="document.getElementById('picForm').submit()">
            </form>
            <?php if ($uploadError): ?>
                <div class="msg" style="color:#ffb3b3;"><?php echo htmlspecialchars($uploadError); ?></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="avatar-large">
                <?php echo profile_picture_markup($user); ?>
            </div>
        <?php endif; ?>

        <!-- BIO -->
        <h3>Bio</h3>
        <?php if (!$viewingOther): ?>
            <form method="post">
                <div class="bio-area">
                    <textarea name="user_bio" maxlength="500"><?php echo htmlspecialchars($user['user_bio']); ?></textarea>
                </div>
                <button class="btn" type="submit">Save Bio</button>
                <?php if($bioMessage): ?><div class="msg"><?php echo htmlspecialchars($bioMessage); ?></div><?php endif; ?>
            </form>
        <?php else: ?>
            <div style="padding:12px; text-align:center; color:#ddd; white-space:pre-wrap;">
                <?php echo nl2br(htmlspecialchars($user['user_bio'])); ?>
            </div>
        <?php endif; ?>

        <!-- FRIENDS -->
        <h3 style="margin-top:25px;">Friends</h3>
        <?php if (count($friends) === 0): ?>
            <div style="color:#ccc;">No friends to show.</div>
        <?php else: ?>
            <div class="friends-grid">
                <?php foreach ($friends as $f): ?>
                    <div class="friend-card">
                        <?php
                            if (!empty($f['profile_picture'])) {
                                echo '<img src="profiles/' . htmlspecialchars($f['profile_picture']) . '" />';
                            } else {
                                echo '<div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;font-weight:700;">' .
                                    htmlspecialchars(strtoupper(substr($f['user_name'],0,1))) .
                                    '</div>';
                            }
                        ?>
                        <div class="friend-name"><?php echo htmlspecialchars($f['user_name']); ?></div>
                        <a class="view-profile" href="profile.php?id=<?php echo intval($f['user_ID']); ?>">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- RECENT COMMENTS -->
        <h3 style="margin-top:35px;">Recent Movie Comments</h3>
        <?php if (empty($recentComments)): ?>
            <p style="color:#ccc;">No movie comments yet.</p>
        <?php else: ?>
            <?php foreach ($recentComments as $row): ?>
                <div class="recent-comment-item">
                    <strong>
                        <a href="movie.php?id=<?= intval($row['movie_ID']); ?>">
                            <?= htmlspecialchars($row['movie_title']); ?>
                        </a>
                    </strong>
                    <div class="comment-text">
                        <?= nl2br(htmlspecialchars($row['comment_content'])); ?>
                    </div>
                    <div class="comment-date">
                        <?= htmlspecialchars($row['comment_date']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</main>
</body>
</html>
