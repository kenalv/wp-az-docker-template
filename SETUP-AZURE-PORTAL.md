# Guía de Setup Manual en Azure Portal para WordPress App Service (Linux/Container)

## 1. Crear los recursos en Azure Portal

1. **Resource Group**
   - Ve a "Resource groups" > "Create".
   - Asigna un nombre (ejemplo: `rg-wp-az-docker-template`) y región.

2. **GitHub Container Registry**
   - No necesitas crear nada. Se usa automáticamente ghcr.io con tu repositorio de GitHub.

3. **Storage Account**
   - Ve a "Storage accounts" > "Create".
   - Asigna nombre (ejemplo: `stvylpcms`), selecciona el resource group y tipo `StorageV2`.
   - Una vez creado, ve a "Containers" y crea uno llamado `media`.

4. **Azure MySQL Flexible Server**
   - Ve a "Azure Database for MySQL flexible server" > "Create".
   - Asigna nombre (ejemplo: `mysql-wp-az-docker-template`), usuario, contraseña y resource group.
   - Crea la base de datos `wordpress`.
   - Configura las reglas de firewall para permitir acceso desde App Service.

5. **Azure Cache for Redis**
   - Ve a "Azure Cache for Redis" > "Create".
   - Asigna nombre (ejemplo: `redis-wp-az-docker-template`), resource group y región.
   - Obtén la cadena de conexión (hostname, puerto, clave).

6. **Key Vault**
   - (No usar en este setup. Los secretos se almacenarán directamente en App Service y GitHub Secrets.)

6. **App Service Plan**
   - Ve a "App Service plans" > "Create".
   - Asigna nombre (ejemplo: `asp-wp-az-docker-template`), tipo Linux y SKU Premium.

7. **App Service (Web App for Containers)**
   - Ve a "App Services" > "Create".
   - Asigna nombre (ejemplo: `wp-az-docker-template`), selecciona el plan y configura el contenedor con una imagen temporal (se actualizará vía GitHub Actions).

---

## 2. Reunir la información necesaria

Para cada servicio, anota:

- **Storage Account:** nombre, nombre del contenedor, clave de acceso.
- **MySQL Flexible Server:** hostname, usuario, contraseña, nombre de la base de datos.
- **Redis:** cadena de conexión.
   (No necesitas Key Vault. Guarda los valores directamente.)
- **App Service:** nombre de la app.

---

## 3. Configurar variables de entorno en App Service

En el portal de App Service:
- Ve a "Configuration" > "Application settings".
- Añade las variables necesarias, por ejemplo:

Ejemplo:
```
MYSQL_HOST=tu-servidor.mysql.database.azure.com
MYSQL_DATABASE=wordpress
MYSQL_USERNAME=tu-usuario
MYSQL_PASSWORD=tu-password-super-secreto
REDIS_URL=redis://:password@tu-redis.redis.cache.windows.net:6380/0?ssl=true
AZURE_STORAGE_ACCOUNT=tu-storage-account
AZURE_STORAGE_KEY=tu-storage-key-super-secreto
AZURE_STORAGE_CONTAINER=media
WP_ENVIRONMENT_TYPE=production
```

---

## 4. Configurar GitHub Secrets

En tu repositorio de GitHub:
- Ve a "Settings" > "Secrets and variables" > "Actions".
- Crea un Secret para cada dato relevante (usa los valores que reuniste, incluyendo contraseñas y claves directamente, sin Key Vault).

---

## 5. Despliegue

- Haz push a tu rama principal en GitHub.
- GitHub Actions usará los secrets para desplegar automáticamente.

---

> **¿Necesitas pasos más detallados o capturas de pantalla para algún servicio específico? Solicítalo y te ayudo.**
