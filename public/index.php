<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIXTY - Sistema Control Horario</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .hero-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .hero-card {
            background: rgba(255,255,255,0.97);
            border-radius: 24px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
            backdrop-filter: blur(20px);
            position: relative;
            overflow: hidden;
        }
        
        .hero-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--mixty-primary), #FF9500, var(--mixty-primary));
            background-size: 200% 100%;
            animation: gradientMove 3s ease infinite;
        }
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .mixty-logo-large {
            font-weight: 900;
            font-size: 5rem;
            letter-spacing: -3px;
            background: linear-gradient(135deg, var(--mixty-primary) 0%, #FF9500 50%, var(--mixty-primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            text-shadow: 0 10px 30px rgba(255,90,31,0.3);
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--mixty-gray);
            margin-bottom: 40px;
            font-weight: 300;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }
        
        .hero-buttons .btn {
            padding: 16px 32px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .feature-item {
            padding: 20px 15px;
            border-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            background: #fff1ed;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .feature-text {
            font-weight: 600;
            color: var(--mixty-secondary);
            font-size: 0.9rem;
        }
        
        .footer-text {
            margin-top: 40px;
            color: #94a3b8;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="hero-container">
        <div class="hero-card">
            <div class="mixty-logo-large">MIXTY</div>
            <div class="hero-subtitle">Sistema de Control Horario Empresarial</div>
            
            <p style="color: #64748b; margin-bottom: 30px; line-height: 1.8;">
                Control y gestión de horas de empleados. Registra fichajes, administra proyectos
                y genera reportes profesionales en tiempo real.
            </p>
            
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">🚀 Iniciar Sesión</a>
                <a href="register.php" class="btn btn-secondary">📝 Registrarse</a>
            </div>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">⏱️</div>
                    <div class="feature-text">Control Horario</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📁</div>
                    <div class="feature-text">Proyectos</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <div class="feature-text">Reportes</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔔</div>
                    <div class="feature-text">Alertas</div>
                </div>
            </div>
            
            <div class="footer-text">
                © 2026 MIXTY • Todos los derechos reservados
            </div>
        </div>
    </div>
</body>
</html>