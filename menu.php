<?php
// Start session and include database connection

require 'db.php';

// Get the user role from session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'guest';

// Fetch recent activities from the database
$sql = "SELECT action, date FROM activities ORDER BY date DESC LIMIT 10";
$stmt = $pdo->query($sql);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<?php if ($role === 'admin'): ?>
<!-- Admin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Admin Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">Manage Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_tasks.php">Manage Tasks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Area for Admins -->
<div class="container mt-4">
    <h3>Welcome, Admin!</h3>
    <p>Here you can view your tasks and manage your activities.</p>

    <div class="alert alert-info" role="alert">
        As an admin, you have access to additional functionalities such as managing users and settings.
    </div>

    <h4>Recent Activities</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                    <td><?php echo htmlspecialchars($activity['date']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php elseif ($role === 'user'): ?>
<!-- User Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">User Dashboard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="user.php">Home Page</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mission.php">View My Missions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="task.php">View My Tasks</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Area for Users -->
<div class="container mt-4">
    <h3>Welcome, User!</h3>
    <p>Here you can view your tasks and missions.</p>
    
    <h4>Recent Activities</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                    <td><?php echo htmlspecialchars($activity['date']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<!-- If user is not logged in or role is invalid -->
<div class="container mt-4">
    <div class="alert alert-danger" role="alert">
        You do not have access to this page. Please <a href="login.php">login</a>.
    </div>
</div>
<?php endif; ?>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
