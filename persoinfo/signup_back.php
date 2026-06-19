<?php
session_start(); // ← must be FIRST, before anything

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/duplicate_error_helper.php';

require_once __DIR__ . '/../includes/db_config.php';
$db_connect = get_db_connection();

if (!$db_connect) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST["firstname"])) {

    $prenom   = $_POST["firstname"];
    $nom      = $_POST["lastname"];
    $username = $_POST["username"];
    $email    = $_POST["email"];
    $mdp      = $_POST["password"];
    $filliere = $_POST["field"];
    $school   = $_POST["university"];

    // Preserve form data in case we need to redirect back
    $formData = [
        'firstname'  => $prenom,
        'lastname'   => $nom,
        'username'   => $username,
        'email'      => $email,
        'field'      => $filliere,
        'university' => $school,
    ];

    if (strlen($mdp) < 8) {
        $_SESSION['signup_error'] = "Password must be at least 8 characters!";
        $_SESSION['signup_data']  = $formData;
        header("Location: signin.php#form-signup");
        exit();
    }

    $hash_pass = password_hash($mdp, PASSWORD_DEFAULT);

    // Use prepared statements to prevent SQL injection
    $stmt = $db_connect->prepare(
        "INSERT INTO user (prenom, nom, username, email, mdp, filliere, school)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        die("Prepare failed: " . $db_connect->error);
    }

    $stmt->bind_param("sssssss", $prenom, $nom, $username, $email, $hash_pass, $filliere, $school);

    if ($stmt->execute()) {
        // Success → save the new user's ID and redirect
        $_SESSION['id_user'] = $stmt->insert_id;
        $stmt->close();
        header("Location: ../profile/profile.php");
        exit();
    }

    $stmt->close();

    // Check for duplicate entry (MySQL error 1062)
    $dupError = getDuplicateErrorFromMysqli($db_connect);

    if ($dupError) {
        $_SESSION['signup_error'] = $dupError['message'];
    } else {
        $_SESSION['signup_error'] = "An error occurred. Please try again later.";
    }

    $_SESSION['signup_data'] = $formData;
    header("Location: signin.php#form-signup");
    exit();
}
?>