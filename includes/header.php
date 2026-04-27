<?php
/**
 * Cabecera común MIXTY.corp para todas las páginas internas
 */
?>
<div class="app-header">
    <div class="header-brand">
        <a href="<?php echo isLoggedIn() ? (isAdmin() ? 'admin.php' : 'dashboard.php') : 'index.php'; ?>" 
           class="mixty-logo">
            🏢 <strong>MIXTY</strong>.corp
        </a>
    </div>
    <div class="header-user">
        <?php if (isLoggedIn()): 
            $currentUser = getCurrentUser();
        ?>
            <span class="user-welcome">👋 Hola, <?php echo escape($currentUser['nombre']); ?></span>
            <span class="user-role badge badge-<?php echo $currentUser['rol'] === 'admin' ? 'info' : 'secondary'; ?>">
                <?php echo strtoupper($currentUser['rol']); ?>
            </span>
            <a href="logout.php" class="btn btn-sm btn-danger">Cerrar Sesión</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a>
        <?php endif; ?>
    </div>
</div>

<style>
.app-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    background: linear-gradient(135deg, var(--mixty-secondary) 0%, var(--mixty-accent) 100%);
    border-radius: 12px 12px 0 0;
    margin: -20px -20px 30px -20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.header-brand .mixty-logo {
    font-weight: 800;
    font-size: 1.5rem;
    color: white;
    text-decoration: none;
    letter-spacing: -0.5px;
    transition: all 0.3s ease;
}

.header-brand .mixty-logo:hover {
    opacity: 0.9;
    transform: scale(1.02);
}

.header-brand .mixty-logo strong {
    background: linear-gradient(135deg, var(--mixty-primary) 0%, #FF9500 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 900;
}

.header-user {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-welcome {
    color: rgba(255,255,255,0.9);
    font-weight: 500;
}

@media (max-width: 768px) {
    .app-header {
        flex-direction: column;
        gap: 15px;
        padding: 15px 20px;
        text-align: center;
    }
    
    .header-user {
        flex-direction: column;
        gap: 10px;
    }
}
</style>