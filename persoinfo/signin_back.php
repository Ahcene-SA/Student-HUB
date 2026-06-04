<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_connect = mysqli_connect("127.0.0.1", "root", "", "devweb", 3306);

if (!$db_connect) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['signin_submit'])) {

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $mdp   = isset($_POST['password']) ? $_POST['password'] : '';

    if ($email === '' || $mdp === '') {
        $_SESSION['signin_error'] = "Please enter both email and password.";
        header("Location: signin.php#form-signin");
        exit();
    }

    // Look up the user by email using a prepared statement
    $stmt = $db_connect->prepare("SELECT id_user, mdp FROM user WHERE email = ? LIMIT 1");

    if (!$stmt) {
        $_SESSION['signin_error'] = "An error occurred. Please try again later.";
        header("Location: signin.php#form-signin");
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    // Verify: user must exist AND password must match the stored hash
    if ($user && password_verify($mdp, $user['mdp'])) {
        // Success — store the user id in the session and redirect
        $_SESSION['id_user'] = (int) $user['id_user'];
        header("Location: ../profile/profile.php");
        exit();
    }

    // Either the email was not found or the password was wrong
    $_SESSION['signin_error'] = "Incorrect email or password. Please try again.";
    header("Location: signin.php#form-signin");
    exit();
}

// If the form wasn't submitted properly, just bounce back to the sign-in page
header("Location: signin.php");
exit();
?>
