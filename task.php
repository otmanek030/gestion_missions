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
$missions_stmt = $pdo->prepare("SELECT * FROM missions WHERE user_id = ?");
$missions_stmt->execute([$user_id]);
$missions = $missions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$mission_id = null;
$mission = null;
$tasks = [];

// Handle form submission for adding a new task
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['task_name'])) {
        // Add a new task
        $mission_id = $_POST['mission_id'];
        $task_name = $_POST['task_name'];
        $task_description = $_POST['task_description'];

        // Insert the new task into the database
        $stmt = $pdo->prepare("INSERT INTO tasks (mission_id, nom, description) VALUES (?, ?, ?)");
        $stmt->execute([$mission_id, $task_name, $task_description]);
        // Get the ID of the newly created task
        $new_task_id = $pdo->lastInsertId();

        // Prepare the response as an associative array
        $response = [
            'id' => $new_task_id,
            'nom' => $task_name,
            'description' => $task_description
        ];

        // Return the response as JSON
        echo json_encode($response);
        exit(); // Exit to prevent further output
    } elseif (isset($_POST['task_id'])) {
        // Update an existing task
        $task_id = $_POST['task_id'];
        $task_name = $_POST['task_name'];
        $task_description = $_POST['task_description'];

        // Update the task in the database
        $stmt = $pdo->prepare("UPDATE tasks SET nom = ?, description = ? WHERE id = ? AND mission_id IN (SELECT id FROM missions WHERE user_id = ?)");
        $stmt->execute([$task_name, $task_description, $task_id, $_SESSION['user_id']]);

        // Prepare the response as an associative array
        $response = [
            'id' => $task_id,
            'nom' => $task_name,
            'description' => $task_description
        ];

        // Return the response as JSON
        echo json_encode($response);
        exit(); // Exit to prevent further output
    }
}

// Get the mission ID from the query parameter
if (isset($_GET['mission_id'])) {
    $mission_id = $_GET['mission_id'];

    // Fetch the mission details for the current user
    $mission_stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ? AND user_id = ?"); // Ensure mission belongs to user
    $mission_stmt->execute([$mission_id, $user_id]);
    $mission = $mission_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        echo "Mission not found.";
        exit();
    }

    // Fetch tasks related to this mission
    $tasks_stmt = $pdo->prepare("SELECT * FROM tasks WHERE mission_id = ?");
    $tasks_stmt->execute([$mission_id]);
    $tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fetch all tasks when no mission ID is provided
    $tasks_stmt = $pdo->prepare("SELECT * FROM tasks");
    $tasks_stmt->execute();
    $tasks = $tasks_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks <?= $mission ? 'for Mission: ' . htmlspecialchars($mission['title']) : 'Overview'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .container {
            max-width: 1200px;
        }
        .task-table {
            margin-bottom: 30px;
        }
        .modal-body {
            padding: 20px;
        }
    </style>
</head>
<body>
<?php include 'menuU.php'; ?> 

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h3><?= $mission ? 'Tasks for Mission: ' . htmlspecialchars($mission['title']) : 'All Tasks'; ?></h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <!-- Task Table -->
                    <table class="table table-hover table-bordered task-table">
                        <thead>
                            <tr>
                                <th class="text-center">ID</th>
                                <th>Task Name</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($tasks)): ?>
                                <?php foreach ($tasks as $task): ?>
                                    <tr data-task-id="<?= htmlspecialchars($task['id']); ?>">
                                        <td class="text-center"><?= htmlspecialchars($task['id']); ?></td>
                                        <td><?= htmlspecialchars($task['nom']); ?></td>
                                        <td class="text-center">
                                            <button class="btn btn-warning btn-sm edit-btn" data-id="<?= htmlspecialchars($task['id']); ?>" data-name="<?= htmlspecialchars($task['nom']); ?>" data-description="<?= htmlspecialchars($task['description']); ?>">Edit</button>
                                            <a href="delete_task.php?id=<?= htmlspecialchars($task['id']); ?>" class="btn btn-danger btn-sm">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No tasks found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <!-- Add New Task Form -->
                    <h4>Add New Task</h4>
                    <form id="add-task-form">
                        <div class="mb-3">
                            <label for="mission_id" class="form-label">Select Mission</label>
                            <select class="form-select" id="mission_id" name="mission_id" required>
                                <option value="" disabled selected>Select a mission</option>
                                <?php foreach ($missions as $m): ?>
                                    <option value="<?= htmlspecialchars($m['id']); ?>"><?= htmlspecialchars($m['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="task_name" class="form-label">Task Name</label>
                            <input type="text" class="form-control" id="task_name" name="task_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="task_description" class="form-label">Task Description</label>
                            <textarea class="form-control" id="task_description" name="task_description" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Task</button>
                    </form>
                </div>
            </div>

            <!-- Back to Mission List Button -->
            <div class="mt-3">
                <a href="mission.php" class="btn btn-secondary">Back to Mission List</a>
            </div>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTaskModalLabel">Edit Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-task-form">
                    <input type="hidden" id="edit_task_id" name="task_id">
                    <div class="mb-3">
                        <label for="edit_task_name" class="form-label">Task Name</label>
                        <input type="text" class="form-control" id="edit_task_name" name="task_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_task_description" class="form-label">Task Description</label>
                        <textarea class="form-control" id="edit_task_description" name="task_description" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle add task form submission
    $('#add-task-form').on('submit', function(event) {
        event.preventDefault(); // Prevent default form submission
        const formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: 'tasks.php', // Your PHP file to handle form submission
            data: formData,
            success: function(response) {
                const task = JSON.parse(response);
                const newRow = `<tr data-task-id="${task.id}">
                    <td class="text-center">${task.id}</td>
                    <td>${task.nom}</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm edit-btn" data-id="${task.id}" data-name="${task.nom}" data-description="${task.description}">Edit</button>
                        <a href="delete_task.php?id=${task.id}" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>`;
                $('table tbody').append(newRow); // Append the new task to the table
                $(this).trigger("reset"); // Reset the form
            },
            error: function() {
                alert('An error occurred while adding the task.');
            }
        });
    });

    // Handle edit button click
    $(document).on('click', '.edit-btn', function() {
        const taskId = $(this).data('id');
        const taskName = $(this).data('name');
        const taskDescription = $(this).data('description');

        // Populate the modal fields
        $('#edit_task_id').val(taskId);
        $('#edit_task_name').val(taskName);
        $('#edit_task_description').val(taskDescription);

        // Show the modal
        $('#editTaskModal').modal('show');
    });

    // Handle edit task form submission
    $('#edit-task-form').on('submit', function(event) {
        event.preventDefault(); // Prevent default form submission
        const formData = $(this).serialize();

        $.ajax({
            type: 'POST',
            url: 'tasks.php', // Your PHP file to handle edit submission
            data: formData,
            success: function(response) {
                const task = JSON.parse(response);
                const row = $(`tr[data-task-id='${task.id}']`);
                row.find('td:eq(1)').text(task.nom); // Update task name
                $('#editTaskModal').modal('hide'); // Hide the modal
            },
            error: function() {
                alert('An error occurred while updating the task.');
            }
        });
    });
});
</script>
</body>
</html>
