<style>
    .header {
        background-color: #28a745; 
        color: white;
        padding: 15px;
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 1000;
        height: 70px; 
    }

    .header .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 2px;
    }

    .content {
        margin-top: 80px; 
    }
</style>

<div class="header">
    <div class="container">
        <h2>Tableau de bord</h2>
        <a href="logout.php" class="btn btn-light">Déconnexion</a>
    </div>
</div>