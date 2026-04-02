# MySan - Docker Deployment

## Requisitos Previos

- [Docker Desktop](https://www.docker.com/products/docker-desktop) instalado
- [Docker Compose](https://docs.docker.com/compose/install/) (incluido en Docker Desktop)

## Stack

| Servicio | Imagen | Versión |
|----------|--------|---------|
| Web | PHP | 8.2 |
| Servidor | Apache | 2.4 |
| Base de Datos | MySQL | 8.0 |
| Admin DB | phpMyAdmin | Latest |

## Estructura de Archivos

```
mysan/
├── Dockerfile              # Imagen PHP + Apache
├── docker-compose.yml      # Orquestación de servicios
├── .dockerignore           # Archivos a excluir
├── .env.example            # Variables de entorno ejemplo
├── config/
│   └── database.php        # Configuración de BD (actualizada para Docker)
└── ...
```

## Pasos de Instalación

### 1. Clonar/Copiar el Proyecto

```bash
# Copiar la carpeta mysan a tu directorio de proyectos
cd /ruta/a/tu/proyecto
```

### 2. Configurar Variables de Entorno

Copiar `.env.example` a `.env` y ajustar si es necesario:

```bash
cp .env.example .env
```

### 3. Iniciar los Contenedores

```bash
# Linux/Mac
docker-compose up -d

# Windows (PowerShell)
docker-compose up -d
```

### 4. Verificar que los Servicios Estén Corriendo

```bash
docker-compose ps
```

Deberías ver:
- `mysan-web`     → Puerto 8080
- `mysan-db`      → Puerto 3306
- `mysan-phpmyadmin` → Puerto 8081

### 5. Acceder al Sistema

| Servicio | URL |
|----------|-----|
| **MySan** | http://localhost:8080 |
| **phpMyAdmin** | http://localhost:8081 |

### 6. Credenciales

#### MySan (Aplicación)
- Usuario: `admin`
- Contraseña: `1234`

#### phpMyAdmin
- Usuario: `root`
- Contraseña: `rootpassword`

#### MySQL (CLI)
```bash
docker exec -it mysan-db mysql -u root -prootpassword
```

---

## Comandos Útiles

### Iniciar servicios
```bash
docker-compose up -d
```

### Detener servicios
```bash
docker-compose down
```

### Ver logs
```bash
# Todos los servicios
docker-compose logs -f

# Solo web
docker-compose logs -f web

# Solo base de datos
docker-compose logs -f db
```

### Reiniciar servicios
```bash
docker-compose restart
```

### Rebuild (después de cambios en código)
```bash
docker-compose build web
docker-compose up -d
```

### Acceder al contenedor web
```bash
docker exec -it mysan-web bash
```

### Ver uso de recursos
```bash
docker stats
```

### Limpiar todo (cuidado: elimina la base de datos)
```bash
docker-compose down -v
```

---

## Resolución de Problemas

### Puerto 8080 en uso
Editar `docker-compose.yml` y cambiar el puerto:
```yaml
ports:
  - "8082:80"  # Cambiar a otro puerto
```

### Error de conexión a la base de datos
1. Verificar que el contenedor MySQL esté corriendo:
   ```bash
   docker-compose ps
   ```
2. Ver logs de la base de datos:
   ```bash
   docker-compose logs db
   ```
3. Esperar a que MySQL esté listo (puede tomar 30-60 segundos)

### Permisos denegados
```bash
# En Linux
sudo chown -R $USER:$USER .
```

### Reiniciar la base de datos (reset completo)
```bash
docker-compose down -v
docker-compose up -d
```

---

## Notas de Desarrollo

### Configuración de Base de Datos

El archivo `config/database.php` ya está configurado para Docker:
- Por defecto usa `db` como host (nombre del servicio en docker-compose)
- Soporta variables de entorno para producción

### Persistencia de Datos

Los datos de MySQL se guardan en un volumen Docker llamado `mysql_data`. 
Para eliminar completamente y empezar desde cero:

```bash
docker-compose down -v
```

### Acceso a Archivos

Los archivos del proyecto se montan en `/var/www/html` dentro del contenedor.
Cualquier cambio en tu máquina local se reflejará automáticamente.

---

## Producción

Para un entorno de producción, considera:

1. Usar SSL/HTTPS
2. Cambiar todas las contraseñas por defecto
3. Usar secretos de Docker o un gestor de variables
4. Configurar backups automáticos de la base de datos
5. Usar un proxy inverso (nginx-proxy, Traefik)

---

## Licencia

Este proyecto es de uso personal/educativo.
