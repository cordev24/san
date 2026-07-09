# MySan - Dockerfile para Sistema de Administración de Ahorros Grupales
# Stack: PHP 8.2 + Apache
#
# USO EN DESARROLLO:
#   docker compose up -d
#   (el código se monta como bind mount desde el host)
#
# USO EN PRODUCCIÓN:
#   docker build -t mysan:latest .
#   o con docker compose: docker compose -f docker-compose.prod.yml up -d
#   (el código se copia DENTRO de la imagen)

FROM php:8.2-apache

# ============================================================
# CONFIGURACIÓN DEL SISTEMA
# ============================================================

# Timezone
ENV TZ=America/Caracas
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Instalar extensiones y dependencias necesarias
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    zip \
    gd \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configurar AllowOverride para mod_rewrite
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# ============================================================
# SEGURIDAD
# ============================================================

# Ocultar versión de Apache y PHP
RUN sed -i 's/^ServerTokens.*/ServerTokens Prod/' /etc/apache2/conf-enabled/security.conf \
    && sed -i 's/^ServerSignature.*/ServerSignature Off/' /etc/apache2/conf-enabled/security.conf

# Deshabilitar TRACE (evita Cross-Site Tracing)
RUN echo "TraceEnable Off" >> /etc/apache2/apache2.conf

# ============================================================
# COPIA DEL CÓDIGO (producción)
# ============================================================

# Copiar el código fuente al contenedor
# NOTA: para desarrollo el compose sobreescribe esto con un bind mount
COPY . /var/www/html/

# Asegurar permisos correctos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads

# ============================================================
# CONFIGURACIÓN DE LOGS
# ============================================================

# Logs de Apache a stdout/stderr para Docker
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log

# Exponer puerto 80
EXPOSE 80

# ============================================================
# HEALTHCHECK (requiere curl)
# ============================================================
HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Forzar únicamente mpm_prefork y eliminar cualquier rastro de otros MPMs
RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && \
    rm -f /etc/apache2/mods-available/mpm_event.* /etc/apache2/mods-available/mpm_worker.* && \
    a2enmod mpm_prefork && \
    echo "=== VERIFIED MPM ===" && ls -la /etc/apache2/mods-enabled/mpm_*

# Iniciar Apache en foreground
CMD ["apache2-foreground"]
