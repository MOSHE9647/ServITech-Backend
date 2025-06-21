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
- [Scramble (Dedoc Scramble)](https://scramble.dedoc.co/)
- [JWT (jwt-auth)](https://jwt-auth.readthedocs.io/en/develop/)

---

## ⚙️ Instalación

> Recuerda instalar Node.JS y ejecutar el siguiente comando para instalar PHP y Composer:

- **Mac:**
    
    ```bash
    /bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.4)"
    ```

- **Windows:**

    ```powershell
    # Run as administrator...
    Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.4'))
    ```

- **Linux:**

    ```bash
    /bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
    ```

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

Esta documentación es generada automáticamente con Scramble (`scramble`) e incluye ejemplos de uso y detalles de cada endpoint.

La documentación está dividida en secciones para facilitar la navegación. Aquí tienes un resumen de las secciones más importantes:

- **Autenticación**: Métodos para iniciar sesión y obtener tokens JWT.
- **Usuarios**: Endpoints para gestionar la información del usuario logueado.
- **Artículos**: Métodos para gestionar artículos tecnológicos y de anime.
- **Soporte Técnico**: Métodos para gestionar solicitudes de soporte técnico.
- **Solicitudes de Reparación**: Métodos para gestionar solicitudes de reparación por parte de los administradores del sistema.

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
