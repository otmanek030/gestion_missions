<?php
session_start();
require 'db.php'; // Include your DB connection

// Check if the user is logged in and if their role is not admin
if (!isset($_SESSION['user_id']) || $_SESSION['droit'] === 'admin') {
    // If not logged in or it's an admin, redirect to the admin page or login page
    header('Location: login.php');
    exit;
}

// Fetch all missions for the current user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM missions WHERE user_id = ?");
$stmt->execute([$user_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all tasks for the current user
$stmt_tasks = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ?");
$stmt_tasks->execute([$user_id]);
$tasks = $stmt_tasks->fetchAll(PDO::FETCH_ASSOC);

// Fetch all tasks shared with the current user
$stmt_shared_tasks = $pdo->prepare("
    SELECT tasks.* 
    FROM tasks 
    JOIN task_shares ON tasks.id = task_shares.task_id 
    WHERE task_shares.user_id = ?
");
$stmt_shared_tasks->execute([$user_id]);
$shared_tasks = $stmt_shared_tasks->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - View All Activities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Include your custom CSS file -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            margin-top: 30px;
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        h3 {
            color: #16a085;
        }
        table {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <?php include 'menuU.php'; ?> <!-- Include the menu here -->

    <!-- Main Container -->
    <div class="container">
        <h3 class="text-center">My Activities</h3>

        <div class="row">
            <!-- Missions Table -->
            <div class="col-md-4">
                <h4>Missions</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mission Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($missions)): ?>
                            <tr>
                                <td colspan="2" class="text-center">No missions found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($missions as $mission): ?>
                                <tr>
                                    <td><?= htmlspecialchars($mission['id']); ?></td>
                                    <td><?= htmlspecialchars($mission['title']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- My Tasks Table -->
            <div class="col-md-4">
                <h4>My Tasks</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Task Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="2" class="text-center">No tasks found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?= htmlspecialchars($task['id']); ?></td>
                                    <td><?= htmlspecialchars($task['nom']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Shared Tasks Table -->
            <div class="col-md-4">
                <h4>Shared Tasks</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Task Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shared_tasks)): ?>
                            <tr>
                                <td colspan="2" class="text-center">No shared tasks found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($shared_tasks as $shared_task): ?>
                                <tr>
                                    <td><?= htmlspecialchars($shared_task['id']); ?></td>
                                    <td><?= htmlspecialchars($shared_task['nom']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div> <!-- End Row -->

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
