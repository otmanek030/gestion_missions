<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php';

$email_verified = false; // Variable to track if email is verified

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email']) && !isset($_POST['mot_de_passe'])) {
        // Step 1: Check if email exists
        $email = $_POST['email'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Email found, proceed to password step
            $email_verified = true;
            $_SESSION['user_email'] = $email; // Save the email to session for the password check
        } else {
            echo "Email not found. Please register.";
        }
    }

    if (isset($_POST['mot_de_passe']) && isset($_SESSION['user_email'])) {
        // Step 2: Check password after email verification
        $mot_de_passe = $_POST['mot_de_passe'];
        $email = $_SESSION['user_email'];

        // Fetch user by email (again, to verify password)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if password matches
        if ($user && password_verify($mot_de_passe, $user['password'])) {
            if ($user['etat'] == 'active') {
                // Save session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nom'] = $user['nom'];
                $_SESSION['droit'] = $user['droit'];

                // Redirect based on user role
                if ($user['droit'] == 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: user.php");
                }
                exit;
            } else {
                echo "Account is deactivated.";
            }
        } else {
            echo "Invalid password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 mt-5">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Login</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="login.php">
                            <?php if (!$email_verified): ?>
                                <!-- Step 1: Email input form -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Next</button>
                            <?php else: ?>
                                <!-- Step 2: Password input form (after email verification) -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" name="mot_de_passe" id="password" placeholder="Enter your password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Login</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
