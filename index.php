<?php
session_start();
require 'connect.php';

$username = $password = "";
$userNameErr = $passwordErr = "";
$message = "";
$messageType = ""; // "success" or "error"
$loginBlocked = 0; // seconds remaining if blocked

// cleanup data
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if login is blocked
    $waitTime = 5; // seconds
    if (isset($_SESSION['last_failed_login'])) {
        $elapsed = time() - $_SESSION['last_failed_login'];
        if ($elapsed < $waitTime) {
            $loginBlocked = $waitTime - $elapsed;
            $message = "Please wait $loginBlocked seconds before trying again.";
            $messageType = "error";
            goto skip_login; // skip login processing
        }
    }

    // Validate username
    if (empty($_POST["username"])) {
        $userNameErr = "Username is required";
    } else {
        $username = test_input($_POST["username"]);
        if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_]{2,19}$/", $username)) {
            $userNameErr = "Username must be 3-20 chars, letters/numbers/_ only, start with letter";
        }
    }

    // Validate password
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = trim($_POST["password"]);
    }

    // REGISTER
    if (isset($_POST['register']) && $userNameErr == "" && $passwordErr == "") {
        try {
            $stmt = $pdo->prepare("SELECT * FROM User WHERE user_name = ?");
            $stmt->execute([$username]);

            if ($stmt->rowCount() > 0) {
                $message = "Username already exists.";
                $messageType = "error";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $defaultProfilePic = "default.png";

                $insert = $pdo->prepare("INSERT INTO User (user_name, user_password, profile_picture, admin_access) VALUES (?, ?, ?, ?)");
                $insert->execute([$username, $hashedPassword, $defaultProfilePic, 0]);

                $message = "Account created successfully!";
                $messageType = "success";
            }
        } catch (Exception $e) {
            $message = "Error creating account: " . $e->getMessage();
            $messageType = "error";
        }
    }

    // LOGIN
    if (isset($_POST['login']) && $userNameErr == "" && $passwordErr == "") {
        try {
            $stmt = $pdo->prepare("SELECT * FROM User WHERE user_name = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['user_password'])) {
                // Store session correctly
                $_SESSION['user_id'] = $user['user_ID'];  
                $_SESSION['user_name'] = $user['user_name'];
                unset($_SESSION['last_failed_login']);

                $message = "Login successful! Redirecting...";
                $messageType = "success";

                echo "<script>
                    setTimeout(function(){ window.location.href='welcome.php'; }, 1000);
                </script>";
            } else {
                $_SESSION['last_failed_login'] = time();
                $loginBlocked = $waitTime;
                $message = "Incorrect Entry. Please wait $loginBlocked seconds before trying again.";
                $messageType = "error";
            }
        } catch (Exception $e) {
            $message = "Login error: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

skip_login:
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="style.css">
<link href='https://cdn.boxicons.com/fonts/basic/boxicons.min.css' rel='stylesheet'>
<style>
    .form-message {
        text-align: center;
        margin-top: 15px;
        font-size: 15px;
        font-weight: 500;
    }
    .form-message.success { color: #4caf50; }
    .form-message.error { color: #ff4d4d; }
</style>
</head>
<body>
<header> 
    <a href="index.php" class="logo">Moviez</a>
    <a class="title">Welcome to Moviez!</a>
    <nav></nav>
    <div class="user-auth">
        <button type="button" class="login-btn-modal">Login</button>
    </div>
</header>

<section> 
    <h1>Welcome!</h1>
</section>

<div class="auth-modal">
    <button type="button" class='close-btn-modal'><i class='bx bx-x'></i></button>
    
    <div class="form-box login">
        <h2>Login/Register</h2>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="input-box">
                <input type="text" name="username" placeholder="Username" value="<?php echo $username; ?>" required>
                <i class='bx bxs-user'></i>
                <?php if ($userNameErr): ?>
                    <span class="error" style="color:#ff4d4d;font-size:13px;"><?php echo $userNameErr; ?></span>
                <?php endif; ?>
            </div>

            <div class="input-box">
                <input type="password" name="password" placeholder="Password" required>
                <i class='bx bxs-lock'></i>
                <?php if ($passwordErr): ?>
                    <span class="error" style="color:#ff4d4d;font-size:13px;"><?php echo $passwordErr; ?></span>
                <?php endif; ?>
            </div>
            
            <button type="submit" name="login" class="btn" id="login-btn"
                <?php if($loginBlocked > 0) echo "disabled"; ?>>
                <?php if($loginBlocked > 0) { echo "Wait $loginBlocked s"; } else { echo "Login"; } ?>
            </button>
            <br><br>
            <button type="submit" name="register" class='btn' value="Create Account">Create Account</button>

            <?php if ($message): ?>
                <div class="form-message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
<?php if($loginBlocked > 0): ?>
let countdown = <?php echo $loginBlocked; ?>;
const loginBtn = document.getElementById("login-btn");

const timerInterval = setInterval(() => {
    countdown--;
    if(countdown > 0){
        loginBtn.textContent = "Wait " + countdown + " s";
    } else {
        loginBtn.textContent = "Login";
        loginBtn.disabled = false;
        clearInterval(timerInterval);
    }
}, 1000);
<?php endif; ?>
</script>

<script src="script.js"></script>
</body>
</html>
