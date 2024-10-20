<?php
require 'db.php'; // Include your DB connection
header('Content-Type: application/json');

// Check the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Handle the request based on the method
switch ($method) {
    case 'POST': // Add a new task
        addTask($pdo);
        break;
    case 'PUT': // Update an existing task
        updateTask($pdo);
        break;
    case 'DELETE': // Delete a task
        deleteTask($pdo);
        break;
    case 'GET': // Get tasks
        getTasks($pdo);
        break;
    default:
        // Return a 405 Method Not Allowed if the request method is not supported
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

// Function to add a task
function addTask($pdo) {
    // Get the data from the request body
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['title'], $data['description'], $data['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO tasks (title, description, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$data['title'], $data['description'], $data['user_id']]);
        echo json_encode(['message' => 'Task added successfully']);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid input']);
    }
}

// Function to update a task
function updateTask($pdo) {
    // Get the task ID from the query string
    if (!isset($_GET['id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Task ID is required']);
        return;
    }

    $task_id = $_GET['id'];
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['title'], $data['description'])) {
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$data['title'], $data['description'], $task_id]);
        echo json_encode(['message' => 'Task updated successfully']);
    } else {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid input']);
    }
}

// Function to delete a task
function deleteTask($pdo) {
    // Get the task ID from the query string
    if (!isset($_GET['id'])) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Task ID is required']);
        return;
    }

    $task_id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);

    echo json_encode(['message' => 'Task deleted successfully']);
}

// Function to get tasks
function getTasks($pdo) {
    // Check if a specific task ID is provided in the query string
    if (isset($_GET['id'])) {
        $task_id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($task) {
            echo json_encode($task);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['error' => 'Task not found']);
        }
    } else {
        // If no ID is provided, fetch all tasks
        $stmt = $pdo->query("SELECT * FROM tasks");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
    }
}
?>
