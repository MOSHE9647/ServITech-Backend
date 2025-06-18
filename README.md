<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# ServITech – Backend

![Laravel Logo](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)

![Badge En Desarrollo](https://img.shields.io/badge/STATUS-EN%20DESARROLLO-green)

## 📚 Tabla de Contenidos

1. [Sobre el Proyecto](#%EF%B8%8F-sobre-el-proyecto)
2. [Arquitectura del Sistema](#-arquitectura-del-sistema)
3. [Tecnologías Usadas](#-tecnologías-usadas)
4. [Instalación](#%EF%B8%8F-instalación)
5. [Documentación de la API](#-documentación-de-la-api)
6. [Uso de Gitflow](#-uso-de-gitflow)
7. [Buildeo para Producción](#%EF%B8%8F-buildeo-para-producción)
8. [Ejecución de Pruebas](#-ejecución-de-pruebas)
9. [Ejemplo de `.env`](#-ejemplo-de-env)
10. [Contribuyendo](#-contribuyendo)
11. [Licencia](#-licencia)
12. [Autores](#-autores)

## 🛠️ Sobre el Proyecto

**ServITech** es una aplicación diseñada para gestionar cotizaciones de artículos tecnológicos, artículos de anime y solicitudes de soporte técnico. Este repositorio contiene únicamente el backend del sistema. El frontend del proyecto está disponible en el siguiente repositorio: [ServITech – Frontend](https://github.com/MOSHE9647/ServITech-Frontend).

Este proyecto fue desarrollado como parte de un curso universitario en la Universidad Nacional de Costa Rica para el curso **Diseño y Programación de Plataformas Móviles** durante el **I Ciclo Lectivo del año 2025**.

---

## 📊 Arquitectura del Sistema

El sistema está compuesto por los siguientes componentes principales:
- **Cliente móvil:** Implementado en Android (Kotlin), interactúa con el backend a través de la API REST.
- **Backend:** Implementado en Laravel, gestiona la lógica de negocio, autenticación y acceso a la base de datos.
- **Base de datos:** MySQL o SQLite, utilizada para almacenar datos de usuarios, artículos y solicitudes de soporte técnico.

---

## 🚀 Tecnologías Usadas

- [PHP 8+](https://www.php.net/)
- [Composer](https://getcomposer.org/)
- [Laravel 12](https://laravel.com/)
- [Node.js & NPM](https://nodejs.org/)
- [MySQL](https://www.mysql.com/) / [SQLite](https://www.sqlite.org/)
- [Swagger (L5-Swagger)](https://github.com/DarkaOnLine/L5-Swagger)
- [JWT (jwt-auth)](https://jwt-auth.readthedocs.io/en/develop/)

---

## ⚙️ Instalación

1. Clona el repositorio:

    ```bash
    git clone https://github.com/MOSHE9647/ServITech-Backend.git
    cd ServITech-Backend
    ```

2. Crea el archivo de entorno:

    ```bash
    cp .env.example .env
    ```

3. Genera la clave de la aplicación:

    ```bash
    php artisan key:generate
    ```

4. Genera el ```secret``` de JWT Auth:

    ```bash
    php artisan jwt:secret
    ```

5. Configura las variables del archivo `.env` (base de datos, correo, etc).

6. Instala dependencias de PHP y Node:

    ```bash
    composer install
    npm install
    ```

7. Ejecuta las migraciones:

    ```bash
    php artisan migrate
    ```

8. Inicia el servidor:

    ```bash
    composer run dev
    ```

---

## 📄 Documentación de la API

Puedes acceder a la documentación en:

```
${APP_URL}/api/docs
```

Esta documentación es generada automáticamente con Swagger (`l5-swagger`) e incluye ejemplos de uso y detalles de cada endpoint.

La documentación está dividida en secciones para facilitar la navegación. Aquí tienes un resumen de las secciones más importantes:

- **Autenticación**: Métodos para iniciar sesión y obtener tokens JWT.
- **Usuarios**: Endpoints para gestionar usuarios, roles y permisos.
- **Artículos**: Métodos para gestionar artículos tecnológicos y de anime.
- **Soporte Técnico**: Métodos para gestionar solicitudes de soporte técnico.

---
## 🧠 Uso de Gitflow

Este proyecto usa **Gitflow** para organizar su desarrollo. Las ramas principales son:

- `main`: Rama de producción
- `dev`: Rama de desarrollo

### Ramas adicionales que Gitflow utiliza:

- `feature/*`: Nuevas funcionalidades
- `release/*`: Versiones candidatas
- `bugfix/*`: Correcciones de errores
- `hotfix/*`: Correcciones críticas en producción

### Cómo iniciar Gitflow:

```bash
git flow init -d
```

Esto configura Gitflow con los nombres por defecto que ya usamos (`main` y `dev`).

#### Ejemplos:

Crear una nueva funcionalidad:

```bash
git flow feature start nombre-de-tu-feature
```

Finalizar y fusionar una funcionalidad:

```bash
git flow feature finish nombre-de-tu-feature
```

Crear un release:

```bash
git flow release start v1.0.0
git flow release finish v1.0.0
```

---

## 🏗️ Buildeo para Producción

```bash
npm run build
```
Esto generará los archivos de producción en la carpeta `public/`.
Asegúrate de que el servidor web esté configurado para servir estos archivos.

## 🧪 Ejecución de Pruebas

El proyecto incluye pruebas funcionales para asegurar la calidad del código. Las pruebas están ubicadas en el directorio `tests/`.

Las pruebas están organizadas en subdirectorios para facilitar su localización. Cada prueba está diseñada para verificar una funcionalidad específica del sistema.

Para ejecutar todas las pruebas del proyecto, utiliza el siguiente comando:

```bash
php artisan test
```

Esto ejecutará todas las pruebas funcionales definidas en el proyecto.

O bien, para ejecutar pruebas específicas:

```bash
php artisan test --filter NombreDeLaPrueba
```

---

## 📁 Ejemplo de `.env`

```dotenv
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

## SQLite
DB_CONNECTION=sqlite
DB_DATABASE=/ruta/a/tu/base_de_datos/database.sqlite

## MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tu_base_de_datos
DB_USERNAME=root
DB_PASSWORD=tu_contraseña

SESSION_DRIVER=database
SESSION_LIFETIME=120

QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
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
SCRAMBLE_API_ROUTE=/api/doc
L5_SWAGGER_USE_ABSOLUTE_PATH=true

JWT_SECRET=tu_jwt_secret
VITE_APP_NAME="${APP_NAME}"
```

---

## 🤝 Contribuyendo

Gracias por considerar contribuir a **ServITech**. Por favor usa ramas `feature/*` y sigue el flujo Gitflow.Gracias por considerar contribuir a **ServITech**. Sigue estos pasos para contribuir:

1. Haz un fork del repositorio.
2. Crea una rama para tu funcionalidad o corrección:

    ```bash
    git flow feature start nueva-funcionalidad
    ```

3. Realiza tus cambios y asegúrate de que las pruebas pasen.
4. Envía un pull request a la rama `dev`.

> Nota: Actualmente no seguimos estándares de codificación específicos, pero planeamos adoptar [PSR-12](https://www.php-fig.org/psr/psr-12/) en futuras iteraciones del proyecto.

---

## 📜 Licencia

Este proyecto está protegido por derechos de autor (c) 2025 Isaac Herrera, Carlos Orellana, David Padilla. Todos los derechos reservados.

Consulta el archivo [LICENSE](LICENSE) para más detalles sobre las restricciones y términos de uso.

---

## 👤 Autores

Este proyecto fue desarrollado por:

- **Carlos Orellana**  
  - Rol: Contribuidor  
  - GitHub: [CarlosOrellanaEst](https://github.com/CarlosOrellanaEst)  
  - Correo: [carlos.orellana.obando@est.una.ac.cr](mailto:carlos.orellana.obando@est.una.ac.cr)

- **David Padilla**  
  - Rol: Contribuidor  
  - GitHub: [DavidPMCR](https://github.com/DavidPMCR)  
  - Correo: [alleriaysebastian@gmail.com](mailto:alleriaysebastian@gmail.com)

- **Isaac Herrera**  
  - Rol: Creador del repositorio y desarrollador principal  
  - GitHub: [MOSHE9647](https://github.com/MOSHE9647)  
  - Correo personal: [isaacmhp2001@gmail.com](mailto:isaacmhp2001@gmail.com)  
  - Correo institucional: [isaac.herrera.pastrana@est.una.ac.cr](mailto:isaac.herrera.pastrana@est.una.ac.cr)
