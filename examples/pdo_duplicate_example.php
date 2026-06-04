<?php
/**
 * Example: Handling duplicate entry errors with PDO
 *
 * This pattern works for ANY INSERT or UPDATE that touches UNIQUE columns.
 * Copy this try/catch block into your own PDO files.
 */

require_once __DIR__ . '/../includes/duplicate_error_helper.php';
require_once __DIR__ . '/../profile/db.php'; // your PDO connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user (prenom, nom, username, email, mdp, filliere, school)
            VALUES (:prenom, :nom, :username, :email, :mdp, :filliere, :school)
        ");

        $stmt->execute([
            ':prenom'   => $_POST['firstname'],
            ':nom'      => $_POST['lastname'],
            ':username' => $_POST['username'],
            ':email'    => $_POST['email'],
            ':mdp'      => password_hash($_POST['password'], PASSWORD_DEFAULT),
            ':filliere' => $_POST['field'],
            ':school'   => $_POST['university'],
        ]);

        // Success
        header("Location: ../profile/profile.php");
        exit();

    } catch (PDOException $e) {
        // Check if this is a duplicate entry error (1062)
        $dupError = getDuplicateErrorFromPDO($e);

        if ($dupError) {
            // Specific user-friendly message
            echo "<p style='color:red;'>" . htmlspecialchars($dupError['message']) . "</p>";
        } else {
            // Some other database error
            echo "<p style='color:red;'>An error occurred. Please try again later.</p>";
            // For debugging only (remove in production):
            // echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        }
    }
}
