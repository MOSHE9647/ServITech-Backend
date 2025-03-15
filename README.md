<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Sobre el Proyecto

ServITech es una aplicación web desarrollada con el framework Laravel. Este proyecto tiene como objetivo proporcionar una plataforma robusta y escalable para la gestión de servicios técnicos.

## Cómo Ejecutar el Proyecto

Para ejecutar el proyecto localmente, sigue estos pasos:

1. Clona el repositorio:
    ```sh
    git clone https://github.com/tu-usuario/servitech.git
    cd servitech
    ```

2. Instala las dependencias de PHP y Node.js:
    ```sh
    composer install
    npm install
    ```

3. Crea un archivo `.env` basado en el ejemplo proporcionado:
    ```sh
    cp .env.example .env
    ```

4. Genera la clave de la aplicación:
    ```sh
    php artisan key:generate
    ```

5. Configura tu archivo `.env` con las credenciales de tu base de datos y otros servicios.

6. Ejecuta las migraciones de la base de datos:
    ```sh
    php artisan migrate
    ```

7. Inicia el servidor de desarrollo:
    ```sh
    php artisan serve
    ```

## Cómo Buildear el Proyecto

Para buildear el proyecto para producción, ejecuta:
```sh
npm run build
```

# Acceder a la Documentación de la API  
La documentación de la API está disponible en la ruta `/api/doc`. Puedes acceder a ella visitando:  

## Ejemplo de Archivo .env  
Aquí tienes un ejemplo de cómo debería verse tu archivo `.env`:

```sh
APP_NAME=ServITech
APP_VERSION=1.0.0
APP_ENV=local
APP_KEY=base64:tu_clave_genérica
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

LOG_CHANNEL=stack
LOG_LEVEL=debug

## Si utilizas MySQL cambia 'sqlite' por 'mysql' y descomenta las variables DB_*
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu_usuario
MAIL_PASSWORD=tu_contraseña
MAIL_FROM_ADDRESS="mailhelper@servitechcr.com"
MAIL_FROM_NAME="${APP_NAME}"

# L5 SWAGGER
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_API_BASE_PATH=/
L5_SWAGGER_API_ROUTE=/api/doc
L5_SWAGGER_USE_ABSOLUTE_PATH=true
L5_FORMAT_TO_USE_FOR_DOCS=json

VITE_APP_NAME="${APP_NAME}"
```

## Contribuyendo  
Gracias por considerar contribuir al proyecto **ServITech**. La guía de contribución se puede encontrar en la documentación de Laravel.  

## Código de Conducta  
Para asegurar que la comunidad de **ServITech** sea acogedora para todos, por favor revisa y cumple con el **Código de Conducta**.  

## Vulnerabilidades de Seguridad  
Si descubres una vulnerabilidad de seguridad en **ServITech**, por favor envía un correo electrónico a **Taylor Otwell** a [taylor@laravel.com](mailto:taylor@laravel.com). Todas las vulnerabilidades de seguridad serán atendidas de inmediato.  

## Licencia  
El framework **Laravel** es un software de código abierto licenciado bajo la licencia **MIT**.  
