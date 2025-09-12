# WordPress CMS en Azure App Service 🚀

Este proyecto despliega WordPress en Azure App Service usando contenedores Docker con integración completa de servicios Azure incluyendo MySQL Flexible Server, Azure Cache for Redis, Storage Blob y despliegue automatizado con GitHub Actions.

## 📋 Arquitectura del Sistema

```
GitHub Repository
    ↓ (Push to main/develop)
GitHub Actions Workflow
    ↓ (Build & Deploy)
GitHub Container Registry (ghcr.io)
    ↓ (Pull Image)
Azure App Service (Linux Container)
    ↓ (Connects to)
├── Azure MySQL Flexible Server
├── Azure Cache for Redis  
└── Azure Blob Storage
```

## 🛠️ Servicios Azure Incluidos

> **Nota importante:** Todos los servicios de Azure requeridos para esta aplicación (App Service, MySQL Flexible Server, Cache for Redis, Storage Account) deben estar **ya creados** antes de continuar con la configuración y despliegue. Este proyecto no crea los recursos automáticamente, solo los utiliza.

- **Azure App Service**: Hosting de la aplicación WordPress
- **Azure MySQL Flexible Server**: Base de datos MySQL 8.0
- **Azure Cache for Redis**: Sistema de caché para mejor rendimiento
- **Azure Storage Account**: Almacenamiento de archivos multimedia de WordPress (no CDN)
- **GitHub Container Registry (ghcr.io)**: Registro de contenedores Docker

## 🚀 Inicio Rápido

### Prerrequisitos

- Azure CLI o Azure PowerShell instalado
- Docker Desktop instalado

### 1. Clonar el Repositorio

```bash
git clone <tu-repositorio>
cd wp-az-docker-template
```

### 2. Configurar Azure Services

### 2. Reunir información de los servicios Azure ya creados

Antes de continuar, asegúrate de tener los siguientes datos de cada servicio:

- **App Service**
  - Nombre de la aplicación web
- **Azure MySQL Flexible Server**
  - Hostname del servidor MySQL
  - Nombre de la base de datos
  - Usuario y contraseña
- **Azure Cache for Redis**
  - Cadena de conexión (hostname, puerto, contraseña)
- **Azure Storage Account** (solo para media de WordPress, no CDN)
  - Nombre de la cuenta de almacenamiento
  - Nombre del contenedor
  - Clave de acceso

Guarda esta información, ya que la necesitarás para configurar las variables de entorno y los secrets de GitHub Actions.

### 3. Configurar GitHub Secrets

En tu repositorio de GitHub, ve a Settings → Secrets and variables → Actions y añade estos secrets (usando la información de los servicios que ya creaste):

```bash
AZURE_CREDENTIALS
AZURE_WEBAPP_NAME
MYSQL_HOST
MYSQL_DATABASE
MYSQL_USERNAME
MYSQL_PASSWORD
REDIS_URL
AZURE_STORAGE_ACCOUNT
AZURE_STORAGE_KEY
AZURE_STORAGE_CONTAINER
```

> **Nota:** No necesitas secrets para GitHub Container Registry (ghcr.io) ya que GitHub Actions tiene acceso automático usando GITHUB_TOKEN.

### 4. Configurar MySQL y Redis

Configura manualmente las reglas de acceso y obtén las cadenas de conexión desde el portal de Azure:

1. **MySQL Flexible Server**:
  - Configura las reglas de firewall para permitir acceso desde App Service
  - Obtén el hostname, usuario, contraseña y nombre de la base de datos

2. **Azure Cache for Redis**:
  - Obtén la cadena de conexión (hostname, puerto, contraseña)
  - Configura la variable `REDIS_URL` en App Service y/o GitHub Secrets

3. **Azure Storage Account**:
  - Obtén el nombre de la cuenta, el contenedor y la clave de acceso
  - Configura las variables de entorno correspondientes

### 5. Desplegar

Simplemente haz push a la rama `main` o `develop`:

```bash
git add .
git commit -m "Initial WordPress deployment"
git push origin main
```

GitHub Actions se encargará automáticamente del build y deployment.

## 🏗️ Desarrollo Local

### Configurar Entorno de Desarrollo

1. **Copiar archivo de entorno**:
```bash
cp .env.example .env
```

2. **Personalizar variables en .env**:
```bash
# Generar keys de WordPress
curl -s https://api.wordpress.org/secret-key/1.1/salt/
```

3. **Iniciar servicios locales**:
```bash
docker-compose up -d
```

4. **Acceder a la aplicación**:
- WordPress: http://localhost:8080
- phpMyAdmin: http://localhost:8081

### Comandos Útiles

```bash
# Ver logs de contenedores
docker-compose logs -f wordpress

# Acceder al contenedor WordPress
docker-compose exec wordpress bash

# Reiniciar servicios
docker-compose restart

# Detener todos los servicios
docker-compose down

# Limpiar volúmenes (⚠️ elimina datos)
docker-compose down -v
```

## 🔧 Configuración Avanzada

### Variables de Entorno Importantes

#### Producción (Azure App Service)
```bash
# Base de datos
MYSQL_HOST=tu-servidor.mysql.database.azure.com
MYSQL_DATABASE=wordpress
MYSQL_USERNAME=tu-usuario
MYSQL_PASSWORD=tu-password-secreto

# Redis
REDIS_URL=redis://:password@tu-redis.redis.cache.windows.net:6380/0?ssl=true

# Blob Storage
AZURE_STORAGE_ACCOUNT=tu-storage-account
AZURE_STORAGE_KEY=tu-storage-key-secreto
AZURE_STORAGE_CONTAINER=media

# WordPress
WP_ENVIRONMENT_TYPE=production
WORDPRESS_TABLE_PREFIX=wp_
```

#### Desarrollo Local
```bash
WORDPRESS_DB_HOST=mysql:3306
WORDPRESS_DB_NAME=wordpress
REDIS_URL=redis://redis:6379
WP_ENVIRONMENT_TYPE=development
WP_DEBUG=true
```

### Plugins Recomendados

El proyecto incluye configuración automática para:

- **Redis Object Cache**: Caché de objetos con Redis
- **W3 Total Cache**: Caché de páginas y optimización
- **Azure Storage**: Integración con Blob Storage
- **Health Check**: Monitoreo del estado del sitio

### Optimizaciones de Rendimiento

#### PHP (php.ini)
- Memory limit: 512M
- Upload max size: 64M
- OPCache habilitado
- Sesiones en Redis

#### Apache
- Compresión gzip habilitada
- Headers de caché configurados
- Seguridad mejorada

#### WordPress
- Object caching con Redis
- CDN con Blob Storage
- Optimización de base de datos

## 📊 Monitoreo y Logs

### Application Insights

Configurar Application Insights para monitoreo:

```bash
az monitor app-insights component create \
  --app wp-az-docker-template-insights \
  --location "East US 2" \
  --resource-group rg-wp-az-docker-template
```

### Logs Importantes

```bash
# Logs de App Service
az webapp log tail --name wp-az-docker-template --resource-group rg-wp-az-docker-template

# Logs de contenedor
docker-compose logs wordpress

# Logs de MySQL
az mysql flexible-server logs list --resource-group rg-wp-az-docker-template --name mysql-wp-az-docker-template
```

### Métricas a Monitorear

- CPU y memoria del App Service
- Latencia de respuesta de WordPress
- Conexiones a MySQL
- Hit ratio de Redis Cache
- Uso de Blob Storage

## 🔒 Seguridad

### Configuraciones de Seguridad Implementadas

1. **HTTPS Forzado**: Toda la comunicación está encriptada
2. **Secretos en Variables**: Contraseñas almacenadas de forma segura en App Service
3. **GitHub Container Registry**: Registro público/privado controlado por permisos del repositorio
4. **Firewall MySQL**: Solo permite conexiones desde App Service
5. **Headers de Seguridad**: X-Frame-Options, CSP, etc.

### Recomendaciones Adicionales

- Configurar Azure Front Door para WAF
- Habilitar Azure Security Center
- Configurar backups automáticos
- Implementar rotación de secretos

## 🚨 Troubleshooting

### Problemas Comunes

#### Error de Conexión a MySQL
```bash
# Verificar firewall rules
az mysql flexible-server firewall-rule list --resource-group rg-wp-az-docker-template --name mysql-wp-az-docker-template

# Verificar variables de entorno
az webapp config appsettings list --name wp-az-docker-template --resource-group rg-wp-az-docker-template
```

#### Error de Redis Connection
```bash
# Verificar estado de Redis
az redis show --name redis-wp-az-docker-template --resource-group rg-wp-az-docker-template --query provisioningState

# Verificar keys de Redis
az redis list-keys --name redis-wp-az-docker-template --resource-group rg-wp-az-docker-template
```

#### Problemas de Deployment
```bash
# Ver logs de deployment
az webapp log deployment list --name wp-az-docker-template --resource-group rg-wp-az-docker-template

# Ver logs de contenedor
az webapp log show --name wp-az-docker-template --resource-group rg-wp-az-docker-template
```

### Comandos de Diagnóstico

```bash
# Estado general de recursos
az group show --name rg-wp-az-docker-template

# Health check del App Service
az webapp browse --name wp-az-docker-template --resource-group rg-wp-az-docker-template

# Restart del App Service
az webapp restart --name wp-az-docker-template --resource-group rg-wp-az-docker-template
```

## 📈 Escalabilidad

### Escalado Vertical
```bash
# Escalar App Service Plan
az appservice plan update --name asp-wp-az-docker-template --resource-group rg-wp-az-docker-template --sku P2V3
```

### Escalado Horizontal
```bash
# Auto-scaling basado en CPU
az monitor autoscale create \
  --resource-group rg-wp-az-docker-template \
  --resource wp-az-docker-template \
  --resource-type Microsoft.Web/serverfarms \
  --name autoscale-wp-az-docker-template \
  --min-count 1 \
  --max-count 5 \
  --count 1
```

## 🔧 Mantenimiento

### Backups Automáticos

```bash
# Configurar backup del App Service
az webapp config backup update \
  --resource-group rg-wp-az-docker-template \
  --webapp-name wp-az-docker-template \
  --container-url "https://stvylpcms.blob.core.windows.net/backups" \
  --frequency 1440 \
  --retain-one true \
  --retention 30
```

### Actualizaciones de WordPress

Las actualizaciones se manejan a través del contenedor Docker. Para actualizar:

1. Actualizar la versión en el Dockerfile
2. Hacer commit y push
3. GitHub Actions desplegará automáticamente

## 📞 Soporte

Para problemas específicos:

1. **Issues de GitHub**: Crear un issue en el repositorio
2. **Logs de Azure**: Revisar Application Insights
3. **Documentación**: [Azure App Service](https://docs.microsoft.com/en-us/azure/app-service/)
4. **WordPress**: [WordPress.org Support](https://wordpress.org/support/)

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver archivo `LICENSE` para más detalles.

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crear una rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit los cambios (`git commit -am 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear un Pull Request

---

⭐ Si este proyecto te ha sido útil, ¡no olvides darle una estrella!