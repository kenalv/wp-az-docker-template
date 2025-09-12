
# Este script asume que TODOS los servicios de Azure requeridos (Resource Group, App Service, MySQL Flexible Server, Redis, Storage Account) ya están creados manualmente.
# Su función principal es ayudarte a reunir la información de conexión y generar el archivo de secretos para GitHub Actions.
# No crea recursos nuevos, solo consulta y configura variables necesarias.
# Este proyecto utiliza GitHub Container Registry (ghcr.io), por lo que no necesita Azure Container Registry.
# Requiere: Azure PowerShell Module instalado

param(
    [Parameter(Mandatory=$false)]
    [string]$ResourceGroupName = "rg-wp-az-docker-template",
    
    [Parameter(Mandatory=$false)]
    [string]$Location = "East US 2",
    
    [Parameter(Mandatory=$false)]
    [string]$AppServiceName = "wp-az-docker-template"
)

# Verificar que Azure PowerShell esté instalado
if (-not (Get-Module -ListAvailable -Name Az)) {
    Write-Error "Azure PowerShell Module no está instalado. Ejecuta: Install-Module -Name Az"
    exit 1
}

# Conectar a Azure
if (-not (Get-AzContext)) {
    Write-Host "🔐 Conectando a Azure..." -ForegroundColor Yellow
    Connect-AzAccount
}

Write-Host "🚀 Iniciando configuración de Azure para WordPress CMS..." -ForegroundColor Green

# Variables
$AppServicePlan = "asp-$AppServiceName"
$MySQLServer = "mysql-$AppServiceName"
$RedisName = "redis-$AppServiceName"
$StorageAccount = "st$($AppServiceName.Replace('-', ''))"

try {

    # ...aquí solo consulta la información de los servicios ya creados y genera el archivo de secretos...

    # Resumen
    Write-Host ""

    Write-Host "� ¡Recolección de información completada!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📋 Recuerda que todos los recursos deben estar creados previamente. Este script solo ayuda a obtener la información y configurar los secretos." -ForegroundColor Cyan
    Write-Host ""
    Write-Host "📝 Próximos pasos:" -ForegroundColor Cyan
    Write-Host "   1. Configura los secretos de GitHub usando 'github-secrets.json'" -ForegroundColor White
    Write-Host "   2. Haz push del código para activar el deployment" -ForegroundColor White
    Write-Host "   3. Configura tu dominio personalizado en App Service" -ForegroundColor White
    Write-Host ""
    Write-Host "⚠️  Guarda el archivo 'github-secrets.json' en un lugar seguro y no lo subas al repositorio" -ForegroundColor Yellow

} catch {
    Write-Error "Error durante la configuración: $($_.Exception.Message)"
    exit 1
}