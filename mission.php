<?php
session_start();
require 'db.php'; // Include your DB connection

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch all missions for the current user
$stmt = $pdo->prepare("SELECT * FROM missions WHERE user_id = ?");
$stmt->execute([$user_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch shared missions for the current user
$shared_missions = []; // Initialize as an empty array in case no shared missions are found
$stmt_shared_missions = $pdo->prepare("
    SELECT missions.* 
    FROM missions 
    JOIN mission_shares ON missions.id = mission_shares.mission_id 
    WHERE mission_shares.user_id = ?
");
$stmt_shared_missions->execute([$user_id]);
$shared_missions = $stmt_shared_missions->fetchAll(PDO::FETCH_ASSOC); // Fetch results

// Fetch tasks shared with the current user
$shared_tasks = []; // Initialize as an empty array in case no shared tasks are found
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
    <title>Home - View Missions and Tasks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Include your custom CSS file -->
    <style>
        /* Custom styles for better table layout */
        .card {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

    <?php include 'menu.php'; ?> <!-- Include the menu here -->

    <!-- Main Container -->
    <div class="container-fluid">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-md-12">
                <div class="container mt-5">
                    <h3>Home - View Missions and Tasks</h3>

                    <!-- Row for Missions and Shared Missions Tables -->
                    <div class="row">
                        <!-- Missions Table -->
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">Your Missions</h4>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered mt-3">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th class="text-center">ID</th>
                                                <th>Mission Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($missions as $mission): ?>
                                                <tr>
                                                    <td class="text-center"><?= htmlspecialchars($mission['id']); ?></td>
                                                    <td>
                                                        <a href="task.php?mission_id=<?= htmlspecialchars($mission['id']); ?>" class="text-decoration-none text-primary">
                                                            <?= htmlspecialchars($mission['title']); ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Shared Missions Section -->
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">Shared Missions</h4>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered mt-3">
                                        <thead>
                                            <tr>
                                                <th class="text-center">ID</th>
                                                <th>Mission Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($shared_missions)): ?>
                                                <?php foreach ($shared_missions as $shared_mission): ?>
                                                    <tr>
                                                        <td class="text-center"><?= htmlspecialchars($shared_mission['id']); ?></td>
                                                        <td>
                                                            <a href="tasks.php?mission_id=<?= htmlspecialchars($shared_mission['id']); ?>" class="text-decoration-none text-success">
                                                                <?= htmlspecialchars($shared_mission['title']); ?>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="2" class="text-center">No shared missions found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End Row -->

                    <!-- Row for Shared Tasks Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Shared Tasks Section -->
                            <div class="card shadow-sm">
                                <div class="card-header bg-warning text-white">
                                    <h4 class="mb-0">Shared Tasks</h4>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered mt-3">
                                        <thead>
                                            <tr>
                                                <th class="text-center">ID</th>
                                                <th>Task Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($shared_tasks)): ?>
                                                <?php foreach ($shared_tasks as $shared_task): ?>
                                                    <tr>
                                                        <td class="text-center"><?= htmlspecialchars($shared_task['id']); ?></td>
                                                        <td><?= htmlspecialchars($shared_task['nom']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="2" class="text-center">No shared tasks found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div> <!-- End Row -->

                </div>
            </div>
        </div>
    </div>

</body>
</html>
