<#
.SYNOPSIS
    Script de publicación para laravel-inquilinos
.DESCRIPTION
    Automatiza el proceso de release: verifica código, hace commit, crea tag y push
.PARAMETER Type
    Tipo de release: major, minor o patch
.PARAMETER Message
    Mensaje del commit (debe seguir Conventional Commits)
.PARAMETER DryRun
    Solo muestra qué haría sin ejecutar
.PARAMETER NoVerify
    Omite las verificaciones de código (tests, format)
.EXAMPLE
    .\publish.ps1 -Type patch -Message "fix: corregir compatibilidad"
.EXAMPLE
    .\publish.ps1 -Type minor -Message "feat: nueva funcionalidad" -DryRun
#>

param(
	[Parameter(Mandatory = $true)]
	[ValidateSet("major", "minor", "patch")]
	[string]$Type,

	[Parameter(Mandatory = $true)]
	[string]$Message,

	[switch]$DryRun,

	[switch]$NoVerify
)

$ErrorActionPreference = "Stop"

# Colores para output
function Write-Step { param($msg) Write-Host "📦 $msg" -ForegroundColor Cyan }
function Write-Success { param($msg) Write-Host "✅ $msg" -ForegroundColor Green }
function Write-Warning { param($msg) Write-Host "⚠️  $msg" -ForegroundColor Yellow }
function Write-Error { param($msg) Write-Host "❌ $msg" -ForegroundColor Red }
function Write-Info { param($msg) Write-Host "ℹ️  $msg" -ForegroundColor Blue }

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Magenta
Write-Host "  📦 Laravel Multi-Inquilinos - Script de Publicación" -ForegroundColor Magenta
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Magenta
Write-Host ""

if ($DryRun) {
	Write-Warning "MODO DRY-RUN: Solo se mostrarán las acciones sin ejecutarlas"
	Write-Host ""
}

# Verificar que estamos en la raíz del proyecto
if (-not (Test-Path "composer.json")) {
	Write-Error "Este script debe ejecutarse desde la raíz del proyecto"
	exit 1
}

# Verificar la rama actual
$currentBranch = git branch --show-current
if ($currentBranch -ne "main" -and $currentBranch -ne "master") {
	Write-Warning "No estás en la rama 'main' ni 'master' (rama actual: $currentBranch)"
	if (-not $DryRun) {
		$continue = Read-Host "¿Deseas continuar en esta rama de todas formas? (s/N)"
		if ($continue -ne "s" -and $continue -ne "S") {
			exit 0
		}
	}
}

# Verificar estado de git
Write-Step "Verificando estado de Git..."
$gitStatus = git status --porcelain
if (-not $gitStatus) {
	Write-Warning "No hay cambios para commitear. ¿Olvidaste hacer cambios?"
	if (-not $DryRun) {
		$continue = Read-Host "¿Deseas continuar de todas formas (por ejemplo, para generar solo el tag)? (s/N)"
		if ($continue -ne "s" -and $continue -ne "S") {
			exit 0
		}
	}
}

# Verificaciones de código
if (-not $NoVerify) {
	Write-Step "Ejecutando verificaciones de código..."

	# Tests
	Write-Info "Ejecutando tests..."
	if (-not $DryRun) {
		$testResult = composer test -- --no-progress 2>$null
		if ($LASTEXITCODE -ne 0) {
			Write-Error "Los tests fallaron. Usa -NoVerify para omitir."
			# Re-ejecutar sin redirección para que el usuario vea el error real
			composer test
			exit 1
		}
		Write-Success "Tests pasaron"
	}
	else {
		Write-Info "[DRY-RUN] Ejecutaría: composer test"
	}

	# Format
	Write-Info "Verificando formato..."
	if (-not $DryRun) {
		composer format 2>&1 | Out-Null
		Write-Success "Código formateado"
	}
	else {
		Write-Info "[DRY-RUN] Ejecutaría: composer format"
	}

	# PHPStan
	Write-Info "Ejecutando análisis estático..."
	if (-not $DryRun) {
		$analyseResult = composer analyse -- --no-progress 2>$null
		if ($LASTEXITCODE -ne 0) {
			Write-Error "PHPStan encontró errores. Usa -NoVerify para omitir."
			# Re-ejecutar sin redirección para ver errores
			composer analyse
			exit 1
		}
		Write-Success "Análisis estático pasó"
	}
	else {
		Write-Info "[DRY-RUN] Ejecutaría: composer analyse"
	}
}
else {
	Write-Warning "Verificaciones de código omitidas (-NoVerify)"
}

# Obtener última versión
Write-Step "Obteniendo última versión..."
$lastTag = try {
	git describe --tags --abbrev=0 2>$null
} catch {
	$null
}
if (-not $lastTag) {
	$lastTag = "v4.1.1"
	Write-Warning "No se encontró tag anterior. Usando $lastTag como base."
}
Write-Info "Última versión: $lastTag"

# Calcular nueva versión
$versionParts = $lastTag -replace "^v", "" -split "\."
$major = [int]$versionParts[0]
$minor = [int]$versionParts[1]
$patch = [int]$versionParts[2]

switch ($Type) {
	"major" { $major++; $minor = 0; $patch = 0 }
	"minor" { $minor++; $patch = 0 }
	"patch" { $patch++ }
}

$newVersion = "v$major.$minor.$patch"
Write-Success "Nueva versión: $newVersion"

# Confirmar
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Yellow
Write-Host "  Resumen de la publicación" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Yellow
Write-Host "  Tipo:        $Type" -ForegroundColor White
Write-Host "  Versión:     $lastTag → $newVersion" -ForegroundColor White
Write-Host "  Mensaje:     $Message" -ForegroundColor White
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Yellow
Write-Host ""

if (-not $DryRun) {
	$confirm = Read-Host "¿Continuar con la publicación? (s/N)"
	if ($confirm -ne "s" -and $confirm -ne "S") {
		Write-Warning "Publicación cancelada"
		exit 0
	}
}

# Git add
Write-Step "Agregando cambios al stage..."
if (-not $DryRun) {
	git add .
	Write-Success "Cambios agregados"
}
else {
	Write-Info "[DRY-RUN] Ejecutaría: git add ."
}

# Git commit
Write-Step "Creando commit..."
if (-not $DryRun) {
	git commit -m $Message --allow-empty
	if ($LASTEXITCODE -ne 0) {
		Write-Error "Error al crear commit"
		exit 1
	}
	Write-Success "Commit creado"
}
else {
	Write-Info "[DRY-RUN] Ejecutaría: git commit -m `"$Message`""
}

# Git tag
Write-Step "Creando tag $newVersion..."
if (-not $DryRun) {
	git tag -a $newVersion -m "Release $newVersion"
	if ($LASTEXITCODE -ne 0) {
		Write-Error "Error al crear tag"
		exit 1
	}
	Write-Success "Tag creado"
}
else {
	Write-Info "[DRY-RUN] Ejecutaría: git tag -a $newVersion -m `"Release $newVersion`""
}

# Git push
Write-Step "Subiendo cambios a GitHub..."
if (-not $DryRun) {
	git push origin $currentBranch
	if ($LASTEXITCODE -ne 0) {
		Write-Error "Error al hacer push de commits"
		exit 1
	}
	git push origin $newVersion
	if ($LASTEXITCODE -ne 0) {
		Write-Error "Error al hacer push del tag"
		exit 1
	}
	Write-Success "Cambios subidos"
}
else {
	Write-Info "[DRY-RUN] Ejecutaría: git push origin $currentBranch"
	Write-Info "[DRY-RUN] Ejecutaría: git push origin $newVersion"
}

# Resumen final
Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  🎉 ¡Publicación completada!" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "  Versión: $newVersion" -ForegroundColor White
Write-Host "  Tag:     https://github.com/edd-war/laravel-multi-inquilinos/releases/tag/$newVersion" -ForegroundColor White
Write-Host ""
Write-Host "  Los workflows de GitHub Actions se ejecutarán automáticamente:" -ForegroundColor Gray
Write-Host "    • 03-release.yml → Crear GitHub Release" -ForegroundColor Gray
Write-Host "    • 04-validar-release-composer.yml → Validar paquete" -ForegroundColor Gray
Write-Host "    • 05-publish-composer.yml → Publicar en GitHub Packages" -ForegroundColor Gray
Write-Host ""
