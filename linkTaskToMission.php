<?php
session_start();
require 'db.php';

// Ensure the user is authorized
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mission_id = $_GET['mission_id'];
$task_id = $_GET['task_id'] ?? null;  // Handle cases where task_id may not be present

// Add a new task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $task_title = $_POST['task_title'];
    $task_description = $_POST['task_description'];

    // Insert the task into the tasks table, linking it to the mission
    $stmt = $pdo->prepare("INSERT INTO tasks (mission_id, nom, description) VALUES (?, ?, ?)");
    $stmt->execute([$mission_id, $task_title, $task_description]);

    // Redirect back to the mission view page
    header("Location: linkTaskToMission.php?mission_id=$mission_id&action=view");
    exit();
}

// Handle task editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_task'])) {
    $task_title = $_POST['task_title'];
    $task_description = $_POST['task_description'];

    // Update the task details in the database
    $stmt = $pdo->prepare("UPDATE tasks SET nom = ?, description = ? WHERE id = ?");
    $stmt->execute([$task_title, $task_description, $task_id]);

    // Redirect back to mission view page
    header("Location: linkTaskToMission.php?mission_id=$mission_id&action=view");
    exit();
}

// Handle task deletion
if (isset($_POST['delete_task'])) {
    // Delete the task from the database
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);

    // Redirect back to mission view page
    header("Location: linkTaskToMission.php?mission_id=$mission_id&action=view");
    exit();
}

// Handle task sharing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_task'])) {
    $shared_user_id = $_POST['user_id'];
    $share_permission = $_POST['permission'];

    // Insert into task_shares table
    $stmt = $pdo->prepare("INSERT INTO task_shares (task_id, user_id, permission) VALUES (?, ?, ?)");
    $stmt->execute([$task_id, $shared_user_id, $share_permission]);

    // Redirect back to mission view page
    header("Location: linkTaskToMission.php?mission_id=$mission_id&action=view");
    exit();
}

// Fetch the task details for editing if a task_id is present
$task = null;
if ($task_id) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch the list of users to share the task with
$users_stmt = $pdo->prepare("SELECT * FROM users WHERE id != ?");
$users_stmt->execute([$_SESSION['user_id']]);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Task to Mission</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2><?php echo $task ? 'Edit, Delete, or Share Task' : 'Add Task to Mission'; ?></h2>

        <!-- Add/Edit Task Form -->
        <form method="POST">
            <div class="mb-3">
                <label for="task_title" class="form-label">Task Title</label>
                <input type="text" class="form-control" id="task_title" name="task_title" 
                       value="<?= htmlspecialchars($task['nom'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="task_description" class="form-label">Task Description</label>
                <textarea class="form-control" id="task_description" name="task_description" rows="3" required><?= htmlspecialchars($task['description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="<?= $task ? 'edit_task' : 'add_task'; ?>" class="btn btn-primary">
                <?= $task ? 'Update Task' : 'Add Task'; ?>
            </button>
        </form>

        <?php if ($task): ?>
            <!-- Delete Task Button -->
            <!-- <form method="POST" class="mt-3">
                <button type="submit" name="delete_task" class="btn btn-danger">Delete Task</button>
            </form> -->

            <!-- Share Task Form -->
            <?php include 'menu.php'; ?>

            <h3 class="mt-5">Share Task</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="user_id" class="form-label">Select User to Share With</label>
                    <select name="user_id" id="user_id" class="form-control" required>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="permission" class="form-label">Permission</label>
                    <select name="permission" id="permission" class="form-control" required>
                        <option value="modify">Allow Modification</option>
                        <option value="view">View Only</option>
                    </select>
                </div>
                <button type="submit" name="share_task" class="btn btn-success">Share Task</button>
            </form>
        <?php endif; ?>

        <a href="mission_actions.php?mission_id=<?= $mission_id; ?>&action=view" class="btn btn-secondary mt-3">Back to Mission</a>
    </div>
</body>
</html>
