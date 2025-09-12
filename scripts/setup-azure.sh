
#!/bin/bash

# Este script asume que TODOS los servicios de Azure requeridos (Resource Group, App Service, MySQL Flexible Server, Redis, Storage Account) ya están creados manualmente.
# Su función principal es ayudarte a reunir la información de conexión y generar el archivo de secretos para GitHub Actions.
# No crea recursos nuevos, solo consulta y configura variables necesarias.
# Este proyecto utiliza GitHub Container Registry (ghcr.io), por lo que no necesita Azure Container Registry.
# Requiere: Azure CLI instalado y autenticado

set -e

# Variables de configuración (personalizar según tus necesidades)
RESOURCE_GROUP="rg-wp-az-docker-template"
LOCATION="East US 2"
APP_SERVICE_PLAN="asp-wp-az-docker-template"
APP_SERVICE_NAME="wp-az-docker-template"
MYSQL_SERVER_NAME="mysql-wp-az-docker-template"
REDIS_NAME="redis-wp-az-docker-template"
STORAGE_ACCOUNT="stvylpcms"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Verificar que Azure CLI esté instalado
if ! command -v az &> /dev/null; then
    print_error "Azure CLI no está instalado. Por favor, instálalo primero."
    exit 1
fi

# Verificar que el usuario esté autenticado
if ! az account show &> /dev/null; then
    print_error "No estás autenticado en Azure CLI. Ejecuta 'az login' primero."
    exit 1
fi


print_info "🚀 Reuniendo información de los servicios Azure ya creados para WordPress CMS..."

# IMPORTANTE: Si algún servicio no existe, créalo manualmente en el portal de Azure antes de continuar.


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


# ...existing code...


echo ""
echo "🎉 ¡Recolección de información completada!"
echo ""
echo "📋 Recuerda que todos los recursos deben estar creados previamente. Este script solo ayuda a obtener la información y configurar los secretos."
echo ""
echo "📝 Próximos pasos:"
echo "   1. Configura los secretos de GitHub usando 'github-secrets.json'"
echo "   2. Haz push del código para activar el deployment"
echo "   3. Configura tu dominio personalizado en App Service"
echo ""
print_warning "Guarda el archivo 'github-secrets.json' en un lugar seguro y no lo subas al repositorio"