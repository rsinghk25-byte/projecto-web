# Sistema de Control de Horas de Empleados

Aplicación web en PHP con MySQL para el control de horas de empleados.

## Estructura del Proyecto

```
projecto web/
├── config/          # Configuración de la aplicación
├── public/          # Punto de entrada y archivos accesibles públicamente
├── src/             # Clases y lógica de negocio
├── actions/         # Procesamiento de formularios y acciones
├── includes/        # Archivos reutilizables (header, footer, funciones)
├── assets/          # Recursos estáticos (CSS, JS, imágenes)
└── README.md
```

## Descripción de Carpetas

### `config/`
Contiene los archivos de configuración de la aplicación, como la conexión a la base de datos, variables de entorno y constantes globales.

### `public/`
Directorio raíz del servidor web. Contiene el punto de entrada principal (`index.php`) y otros archivos accesibles públicamente. Todas las solicitudes pasan a través de este directorio.

### `src/`
Contiene las clases principales de la aplicación (modelo de negocio). Aquí se definen las clases como `User` (empleados) y `TimeRecord` (registros de horas).

### `actions/`
Archivos que procesan las acciones del usuario, como el inicio de sesión, registro de horas, logout, etc. Cada archivo maneja una acción específica del sistema.

### `includes/`
Archivos PHP reutilizables que se incluyen en múltiples páginas: cabeceras, pies de página, funciones de autenticación, helpers, etc.

### `assets/`
Recursos estáticos del frontend:
- `css/` - Hojas de estilo
- `js/` - Scripts JavaScript
- `images/` - Imágenes e iconos

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)

## Instalación

1. Clonar el repositorio
2. Configurar la base de datos en `config/database.php`
3. Importar el script SQL en la base de datos
4. Configurar el servidor web para apuntar al directorio `public/`