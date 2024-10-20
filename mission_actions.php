<?php
session_start();
require 'db.php';

// Ensure the session contains the user_id
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$mission_id = $_GET['mission_id'] ?? null;
$action = $_GET['action'] ?? 'view';

// Initialize the tasks variable
$tasks = [];
$mission = null;

// Fetch mission details if the mission_id is set
if ($mission_id) {
    $stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ?");
    $stmt->execute([$mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle updating a mission
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];

    $update_stmt = $pdo->prepare("UPDATE missions SET title = ?, description = ? WHERE id = ?");
    $update_stmt->execute([$title, $description, $mission_id]);

    // Redirect to view the mission after update
    header("Location: mission_actions.php?mission_id=$mission_id&action=view");
    exit();
}

// Handle deleting a mission
if ($mission_id && $action === 'delete') {
    // Fetch task IDs related to the mission
    $task_ids_stmt = $pdo->prepare("SELECT id FROM tasks WHERE mission_id = ?");
    $task_ids_stmt->execute([$mission_id]);
    $task_ids = $task_ids_stmt->fetchAll(PDO::FETCH_COLUMN); // Fetch task IDs

    if (!empty($task_ids)) {
        // Delete from task_shares and tasks tables
        $in_task_ids = implode(',', array_fill(0, count($task_ids), '?'));
        $delete_task_shares_stmt = $pdo->prepare("DELETE FROM task_shares WHERE task_id IN ($in_task_ids)");
        $delete_task_shares_stmt->execute($task_ids);

        $delete_tasks_stmt = $pdo->prepare("DELETE FROM tasks WHERE mission_id = ?");
        $delete_tasks_stmt->execute([$mission_id]);
    }

    // Delete from mission_shares and missions tables
    $delete_mission_shares_stmt = $pdo->prepare("DELETE FROM mission_shares WHERE mission_id = ?");
    $delete_mission_shares_stmt->execute([$mission_id]);

    $delete_mission_stmt = $pdo->prepare("DELETE FROM missions WHERE id = ?");
    $delete_mission_stmt->execute([$mission_id]);

    // After deletion, redirect to the user panel
    header("Location: user.php");
    exit();
}

// Handle deleting a task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];

    // First, delete any shares related to this task
    $delete_task_shares_stmt = $pdo->prepare("DELETE FROM task_shares WHERE task_id = ?");
    $delete_task_shares_stmt->execute([$task_id]);

    // Then, delete the task itself
    $delete_task_stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $delete_task_stmt->execute([$task_id]);

    // Redirect to the same page after deletion
    header("Location: mission_actions.php?mission_id=$mission_id&action=view");
    exit();
}

// Handle sharing the mission and tasks
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mission_id'], $_POST['user_id'])) {
    $mission_id = $_POST['mission_id'];
    $user_id_to_share = $_POST['user_id'];
    $task_ids = isset($_POST['task_ids']) ? $_POST['task_ids'] : [];
    $permission = $_POST['permission'] ?? 'view'; // Default permission is 'view'

    // Share the mission
    $stmt = $pdo->prepare("INSERT INTO mission_shares (mission_id, user_id, permission) VALUES (?, ?, ?)");
    $stmt->execute([$mission_id, $user_id_to_share, $permission]);

    // Share the selected tasks
    foreach ($task_ids as $task_id) {
        $stmt = $pdo->prepare("INSERT INTO task_shares (task_id, user_id, permission) VALUES (?, ?, ?)");
        $stmt->execute([$task_id, $user_id_to_share, $permission]);
    }

    // Redirect back to the mission page with success message
    header("Location: mission_actions.php?mission_id=$mission_id&success=1");
    exit();
}

// Fetch tasks if viewing the mission
if ($action === 'view' && $mission_id) {
    $taskStmt = $pdo->prepare("SELECT * FROM tasks WHERE mission_id = ?");
    $taskStmt->execute([$mission_id]);
    $tasks = $taskStmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all users except the logged-in user
$userStmt = $pdo->prepare("SELECT id, username FROM users WHERE id != ?");
$userStmt->execute([$user_id]);
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Actions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Include Side Menu -->
        <div class="col-md-3">
            <?php include 'menu.php'; ?>
        </div>

        <div class="col-md-9">
            <!-- Main Content -->
            <div class="container mt-4">
                <?php if ($action === 'view'): ?>
                    <?php if ($mission): ?>
                        <div class="card mb-4 shadow-sm">
                            <div class="card-header">
                                <h2 class="h5">Tasks for Mission: <?= htmlspecialchars($mission['title']); ?></h2>
                            </div>
                            <div class="card-body">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Task Title</th>
                                            <th>Description</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $task): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($task['nom']); ?></td>
                                                <td><?= htmlspecialchars($task['description']); ?></td>
                                                <td>
                                                    <a href="linkTaskToMission.php?task_id=<?= $task['id']; ?>&mission_id=<?= $mission_id; ?>&action=edit" class="btn btn-warning btn-sm">Edit</a>
                                                    <form method="POST" action="mission_actions.php?mission_id=<?= $mission_id; ?>&action=view">
                                                        <input type="hidden" name="task_id" value="<?= $task['id']; ?>">
                                                        <button type="submit" name="delete_task" class="btn btn-danger btn-sm">Delete Task</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <a href="linkTaskToMission.php?mission_id=<?= $mission_id; ?>" class="btn btn-success">Add New Task</a>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($action === 'edit' && $mission): ?>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <h2 class="h5">Edit Mission</h2>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Mission Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($mission['title']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Mission Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($mission['description']); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="mission_actions.php?mission_id=<?= $mission_id; ?>&action=view" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Share Mission Form -->
                <?php if ($mission): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header">
                        <h3 class="h5">Share Mission: <?= htmlspecialchars($mission['title']); ?></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Select User to Share With</label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Select Tasks to Share</label>
                                <?php foreach ($tasks as $task): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="<?= $task['id']; ?>" name="task_ids[]">
                                        <label class="form-check-label"><?= htmlspecialchars($task['nom']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mb-3">
                                <label for="permission" class="form-label">Permission</label>
                                <select class="form-select" id="permission" name="permission" required>
                                    <option value="view">View</option>
                                    <option value="edit">Edit</option>
                                </select>
                            </div>
                            <input type="hidden" name="mission_id" value="<?= $mission_id; ?>">
                            <button type="submit" class="btn btn-primary">Share</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
