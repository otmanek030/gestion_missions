<!-- menu.php -->
<style>
    .sidebar {
        height: 100vh; 
        width: 200px; 
        position: fixed;
        top: 56px; 
        left: 0;
        padding-top: 20px;
        background-color: #343a40; 
    }

    .sidebar a {
        color: white;
        padding: 10px 15px;
        text-decoration: none;
        display: block; 
    }

    .sidebar a:hover {
        background-color: #495057;
        color: white;
        text-decoration: none;
    }
</style>

<div class="sidebar">
    <h5 class="text-white text-center">Menu</h5>
    <a href="index.php?page=user">User</a>
    <a href="index.php?page=task">Tâches</a>
    <a href="index.php?page=MP">Missions</a>
    <a href="index.php?page=LT">Link Task To Mission</a> <!-- Add this link -->
</div>