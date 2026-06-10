#!/bin/bash
# =============================================================================
# Script de publicación para laravel-inquilinos
# =============================================================================
# Uso:
#   ./scripts/publish.sh <tipo> "<mensaje>"
#   ./scripts/publish.sh patch "fix: corregir compatibilidad"
#   ./scripts/publish.sh minor "feat: nueva funcionalidad"
#   ./scripts/publish.sh major "feat!: actualización importante"
#
# Opciones:
#   --dry-run    Solo muestra qué haría sin ejecutar
#   --no-verify  Omite las verificaciones de código
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Funciones de output
step() { echo -e "${CYAN}📦 $1${NC}"; }
success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; exit 1; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# Variables
DRY_RUN=false
NO_VERIFY=false
TYPE=""
MESSAGE=""

# Parsear argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        major|minor|patch)
            TYPE="$1"
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --no-verify)
            NO_VERIFY=true
            shift
            ;;
        *)
            if [[ -z "$MESSAGE" ]]; then
                MESSAGE="$1"
            fi
            shift
            ;;
    esac
done

# Validar argumentos
if [[ -z "$TYPE" ]]; then
    error "Tipo de release requerido: major, minor o patch"
fi

if [[ -z "$MESSAGE" ]]; then
    error "Mensaje de commit requerido"
fi

echo ""
echo -e "${MAGENTA}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${MAGENTA}  📦 Laravel Multi-Inquilinos - Script de Publicación${NC}"
echo -e "${MAGENTA}═══════════════════════════════════════════════════════════════${NC}"
echo ""

if [[ "$DRY_RUN" == true ]]; then
    warning "MODO DRY-RUN: Solo se mostrarán las acciones sin ejecutarlas"
    echo ""
fi

# Verificar que estamos en la raíz del proyecto
if [[ ! -f "composer.json" ]]; then
    error "Este script debe ejecutarse desde la raíz del proyecto"
fi

# Verificar rama
step "Verificando rama actual..."
CURRENT_BRANCH=$(git branch --show-current)
if [[ "$CURRENT_BRANCH" != "main" && "$CURRENT_BRANCH" != "master" ]]; then
    error "Debes estar en la rama 'main' o 'master' para publicar. Rama actual: $CURRENT_BRANCH"
fi

# Verificar cambios pendientes
step "Verificando estado de Git..."
if [[ -z $(git status --porcelain) ]]; then
    warning "No hay cambios para commitear. ¿Olvidaste hacer cambios?"
    read -p "¿Deseas continuar de todas formas? (s/N): " CONTINUE
    if [[ "$CONTINUE" != "s" && "$CONTINUE" != "S" ]]; then
        exit 0
    fi
fi

# Verificaciones de código
if [[ "$NO_VERIFY" == false ]]; then
    step "Ejecutando verificaciones de código..."

    # Tests
    info "Ejecutando tests..."
    if [[ "$DRY_RUN" == false ]]; then
        if ! composer test; then
            error "Los tests fallaron. Usa --no-verify para omitir."
        fi
        success "Tests pasaron"
    else
        info "[DRY-RUN] Ejecutaría: composer test"
    fi

    # Format
    info "Verificando formato..."
    if [[ "$DRY_RUN" == false ]]; then
        composer format > /dev/null 2>&1
        success "Código formateado"
    else
        info "[DRY-RUN] Ejecutaría: composer format"
    fi

    # PHPStan
    info "Ejecutando análisis estático..."
    if [[ "$DRY_RUN" == false ]]; then
        if ! composer analyse; then
            error "PHPStan encontró errores. Usa --no-verify para omitir."
        fi
        success "Análisis estático pasó"
    else
        info "[DRY-RUN] Ejecutaría: composer analyse"
    fi
else
    warning "Verificaciones de código omitidas (--no-verify)"
fi

# Obtener última versión
step "Obteniendo última versión..."
LAST_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "v4.1.1")
info "Última versión: $LAST_TAG"

# Calcular nueva versión
VERSION=${LAST_TAG#v}
IFS='.' read -ra PARTS <<< "$VERSION"
MAJOR=${PARTS[0]:-4}
MINOR=${PARTS[1]:-1}
PATCH=${PARTS[2]:-1}

case $TYPE in
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    patch)
        PATCH=$((PATCH + 1))
        ;;
esac

NEW_VERSION="v${MAJOR}.${MINOR}.${PATCH}"
success "Nueva versión: $NEW_VERSION"

# Confirmar
echo ""
echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${YELLOW}  Resumen de la publicación${NC}"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
echo "  Tipo:        $TYPE"
echo "  Versión:     $LAST_TAG → $NEW_VERSION"
echo "  Mensaje:     $MESSAGE"
echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
echo ""

if [[ "$DRY_RUN" == false ]]; then
    read -p "¿Continuar con la publicación? (s/N): " CONFIRM
    if [[ "$CONFIRM" != "s" && "$CONFIRM" != "S" ]]; then
        warning "Publicación cancelada"
        exit 0
    fi
fi

# Git add
step "Agregando cambios al stage..."
if [[ "$DRY_RUN" == false ]]; then
    git add .
    success "Cambios agregados"
else
    info "[DRY-RUN] Ejecutaría: git add ."
fi

# Git commit
step "Creando commit..."
if [[ "$DRY_RUN" == false ]]; then
    git commit -m "$MESSAGE" --allow-empty
    success "Commit creado"
else
    info "[DRY-RUN] Ejecutaría: git commit -m \"$MESSAGE\""
fi

# Git tag
step "Creando tag $NEW_VERSION..."
if [[ "$DRY_RUN" == false ]]; then
    git tag -a "$NEW_VERSION" -m "Release $NEW_VERSION"
    success "Tag creado"
else
    info "[DRY-RUN] Ejecutaría: git tag -a $NEW_VERSION -m \"Release $NEW_VERSION\""
fi

# Git push
step "Subiendo cambios a GitHub..."
if [[ "$DRY_RUN" == false ]]; then
    git push origin "$CURRENT_BRANCH"
    git push origin "$NEW_VERSION"
    success "Cambios subidos"
else
    info "[DRY-RUN] Ejecutaría: git push origin $CURRENT_BRANCH"
    info "[DRY-RUN] Ejecutaría: git push origin $NEW_VERSION"
fi

# Resumen final
echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  🎉 ¡Publicación completada!${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
echo ""
echo "  Versión: $NEW_VERSION"
echo "  Tag:     https://github.com/edd-war/laravel-multi-inquilinos/releases/tag/$NEW_VERSION"
echo ""
echo "  Los workflows de GitHub Actions se ejecutarán automáticamente:"
echo "    • 03-release.yml → Crear GitHub Release"
echo "    • 04-validar-release-composer.yml → Validar paquete"
echo "    • 05-publish-composer.yml → Publicar en GitHub Packages"
echo ""
