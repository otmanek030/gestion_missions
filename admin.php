<?php
session_start();
require 'db.php'; // Include your DB connection

// Check if the user is an admin
if (!isset($_SESSION['droit']) || $_SESSION['droit'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Fetch all users from the database
$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle activate/deactivate requests
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $action = $_GET['action'];

    // Fetch the user's role to ensure admin cannot be deactivated
    $user_stmt = $pdo->prepare("SELECT droit FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();

    // Ensure admins cannot deactivate other admins
    if ($user['droit'] !== 'admin') {
        // Change the user status based on the action
        if ($action === 'activate') {
            $pdo->prepare("UPDATE users SET etat = 'active' WHERE id = ?")->execute([$user_id]);
        } elseif ($action === 'deactivate') {
            $pdo->prepare("UPDATE users SET etat = 'desactive' WHERE id = ?")->execute([$user_id]);
        }
    }

    // Redirect back to the admin page after the action
    header("Location: admin.php");
    exit();
}

// Fetch missions and tasks for a specific user if `user_id` is provided
$user_id = $_GET['user_id'] ?? null;
$missions = [];
$tasks = [];
if ($user_id) {
    // Fetch missions and tasks related to the user
    $missions_stmt = $pdo->prepare("SELECT * FROM missions WHERE user_id = ?");
    $missions_stmt->execute([$user_id]);
    $missions = $missions_stmt->fetchAll(PDO::FETCH_ASSOC);

    $tasks_stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ?");
    $tasks_stmt->execute([$user_id]);
    $tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle deletion of missions or tasks
if (isset($_GET['delete']) && isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];  // 'mission' or 'task'
    $id = $_GET['id'];      // ID of the mission or task

    if ($type === 'mission') {
        // Delete tasks associated with the mission
        $pdo->prepare("DELETE FROM tasks WHERE mission_id = ?")->execute([$id]);
        // Delete the mission itself
        $pdo->prepare("DELETE FROM missions WHERE id = ?")->execute([$id]);
        
        // Log activity
        $log_stmt = $pdo->prepare("INSERT INTO activities (user_id, action, target_type, target_id) VALUES (?, ?, ?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], 'delete', 'mission', $id]);
    } elseif ($type === 'task') {
        // Delete the task
        $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
        
        // Log activity
        $log_stmt = $pdo->prepare("INSERT INTO activities (user_id, action, target_type, target_id) VALUES (?, ?, ?, ?)");
        $log_stmt->execute([$_SESSION['user_id'], 'delete', 'task', $id]);
    }

    // Redirect back to the admin page after deletion
    header("Location: admin.php?user_id=$user_id");
    exit();
}

// Fetch summary metrics
try {
    $activeUserCount = $pdo->query("SELECT COUNT(*) FROM users WHERE etat = 'active'")->fetchColumn() ?: 0;
    $inactiveUserCount = $pdo->query("SELECT COUNT(*) FROM users WHERE etat = 'desactive'")->fetchColumn() ?: 0;
    $totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn() ?: 0;
    $totalMissions = $pdo->query("SELECT COUNT(*) FROM missions")->fetchColumn() ?: 0;

    // Fetch all activities from the database
    $activities_stmt = $pdo->prepare("SELECT a.*, u.username FROM activities a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
    $activities_stmt->execute();
    $activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle any errors while fetching metrics
    $activeUserCount = $inactiveUserCount = $totalTasks = $totalMissions = 0;
    $activities = []; // Ensure $activities is an empty array if there's an error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User and Task Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'menu.php'; ?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-danger text-white text-center">
            <h3>Admin Panel - User Management</h3>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">Active Users</h5>
                            <p class="card-text"><?= $activeUserCount; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5 class="card-title">Inactive Users</h5>
                            <p class="card-text"><?= $inactiveUserCount; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Tasks</h5>
                            <p class="card-text"><?= $totalTasks; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5 class="card-title">Total Missions</h5>
                            <p class="card-text"><?= $totalMissions; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <table class="table table-hover">
                <thead class="table-danger">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']); ?></td>
                            <td><?= htmlspecialchars($user['username']); ?></td>
                            <td><?= htmlspecialchars($user['email']); ?></td>
                            <td><?= htmlspecialchars($user['droit']); ?></td>
                            <td>
                                <span class="badge bg-<?= $user['etat'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?= htmlspecialchars(ucfirst($user['etat'])); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['droit'] === 'admin'): ?>
                                    <span class="text-muted">Admin (always active)</span>
                                <?php else: ?>
                                    <a href="admin.php?user_id=<?= $user['id']; ?>" class="btn btn-info btn-sm">View Tasks/Missions</a>
                                    <?php if ($user['etat'] === 'active'): ?>
                                        <a href="admin.php?action=deactivate&user_id=<?= $user['id']; ?>" class="btn btn-warning btn-sm">Deactivate</a>
                                    <?php else: ?>
                                        <a href="admin.php?action=activate&user_id=<?= $user['id']; ?>" class="btn btn-success btn-sm">Activate</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($user_id): ?>
    <div class="card mt-5">
        <div class="card-header bg-primary text-white text-center">
            <h3>Missions and Tasks for User ID: <?= htmlspecialchars($user_id); ?></h3>
        </div>
        <div class="card-body">
            <!-- Missions Table -->
            <h4 class="text-primary">Missions</h4>
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missions as $mission): ?>
                        <tr>
                            <td><?= htmlspecialchars($mission['id']); ?></td>
                            <td><?= htmlspecialchars($mission['title']); ?></td>
                            <td>
                                <a href="?delete=mission&type=mission&id=<?= $mission['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Tasks Table -->
            <h4 class="text-primary">Tasks</h4>
            <table class="table table-hover table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td><?= htmlspecialchars($task['id']); ?></td>
                            <td><?= htmlspecialchars($task['description']); ?></td>
                            <td>
                                <a href="?delete=task&type=task&id=<?= $task['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Activities Log -->
    <div class="card mt-5">
        <div class="card-header bg-secondary text-white text-center">
            <h3>Activity Log</h3>
        </div>
        <div class="card-body">
            <table class="table table-hover">
                <thead class="table-secondary">
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Type</th>
                        <th>ID</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?= htmlspecialchars($activity['username']); ?></td>
                            <td><?= htmlspecialchars($activity['action']); ?></td>
                            <td><?= htmlspecialchars($activity['target_type']); ?></td>
                            <td><?= htmlspecialchars($activity['target_id']); ?></td>
                            <td><?= htmlspecialchars($activity['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
