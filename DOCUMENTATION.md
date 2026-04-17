# MySan - Documentación del Proyecto

## 1. Información General

**MySan** es un sistema de administración de ahorros grupales tipo "San" o "Susu" desarrollado en PHP vanilla con MySQL. Permite gestionar grupos de ahorro donde los participantes contribuyen cuotas periódicas y se turnan para recibir el fondo total.

### Stack Tecnológico
- **Backend**: PHP 7.4+ (vanilla, sin framework)
- **Base de Datos**: MySQL 5.7+
- **Frontend**: Vanilla JavaScript + CSS
- **Tipo**: 100% Offline - todos los assets incluidos localmente

---

## 2. Estructura de Archivos

```
mysan/
├── 📄 Archivos Raíz
│   ├── index.php              # Redirección a login.php
│   ├── login.php            # Página de autenticación
│   ├── dashboard.php       # Dashboard principal con Bento Grid
│   ├── logout.php         # Cierre de sesión
│   ├── crear-usuario.php  # Crear nuevos usuarios (admin)
│   └── recuperar-password.php # Recuperación de contraseña
│
├── 📁 config/
│   └── database.php      # Conexión PDO MySQL + funciones helper
│
├── 📁 api/              # Endpoints de la API REST
│   ├── auth.php         # Autenticación (login/logout)
│   ├── productos.php    # CRUD productos
│   ├── grupos.php      # CRUD grupos San
│   ├── participantes.php # CRUD participantes
│   ├── pagos.php      # Gestión de pagos
│   ├── turnos.php    # Gestión de turnos/sorteos
│   └── comprobantes.php # Generación de comprobantes/QR
│
├── 📁 modules/         # Módulos admin (vistas)
│   ├── electrodomesticos/  # Módulo electrodomésticos
│   │   ├── index.php
│   │   └── pagos.php
│   ├── telefonia/       # Módulo telefonía
│   │   ├── index.php
│   │   └── pagos.php
│   ├── motocycletas/   # Módulo motocicletas
│   │   ├── index.php
│   │   └── pagos.php
│   ├── turnos/        # Sistema de sorteos
│   │   └── index.php
│   ├── comprobantes/  # Generación de recibos
│   │   └── index.php
│   └── shared/       # Componentes compartidos
│       ├── sidebar.php
│       └── sidebar-footer.php
│
├── 📁 assets/
│   ├── css/           # Estilos CSS
│   │   ├── reset.css   # Reset CSS
│   │   ├── variables.css # Variables CSS (colores, spacing)
│   │   ├── bento-grid.css # Sistema de grid
│   │   └── main.css   # Estilos principales
│   ├── js/            # JavaScript
│   │   ├── shared.js      # Funciones compartidas
│   │   ├── grupos.js    # Lógica de grupos
│   │   ├── participantes.js # Lógica de participantes
│   │   ├── productos.js # Lógica de productos
│   │   ├── eliminar_grupo.js # Eliminar grupos
│   │   └── qrcode.min.js # Generación QR offline
│   ├── fonts/
│   │   └── inter.css  # Fuente Inter
│   └── icons/
│       └── feather-sprite.svg # Sprite de iconos SVG
│
├── 📁 fpdf/          # Librería para generación PDFs
│   ├── fpdf.php
│   └── font/       # Fuentes para FPDF
│
└── 📄 database.sql  # Esquema de base de datos
```

---

## 3. Descripción de Archivos

### 3.1 Archivos Raíz

| Archivo | Descripción | Responsabilidad |
|--------|-------------|----------------|
| `index.php` | Redirección | Redirecciona a `login.php` |
| `login.php` | Login | Autenticación de usuarios con username/password |
| `dashboard.php` | Dashboard principal | Muestra módulos en Bento Grid con estadísticas |
| `logout.php` | Logout | Destruye sesión y redirige a login |
| `crear-usuario.php` | Crear usuario | Formulario para crear nuevos usuarios admin |
| `recuperar-password.php` | Recuperar contraseña | Recupera contraseña mediante pregunta secreta |

### 3.2 API

| Endpoint | Métodos | Descripción |
|---------|--------|------------|
| `api/auth.php` | POST | Login/logout de usuarios |
| `api/productos.php` | GET, POST, PUT, DELETE | CRUD de productos |
| `api/grupos.php` | GET, POST, PUT, DELETE | CRUD de grupos San |
| `api/participantes.php` | GET, POST, PUT, DELETE | CRUD de participantes |
| `api/pagos.php` | GET, POST, PUT | Registro de pagos |
| `api/turnos.php` | GET, POST, PUT | Gestión de turnos/sorteos |
| `api/comprobantes.php` | GET, POST | Generación de comprobantes/QR |

### 3.3 Módulos

| Módulo | Color | Propósito |
|--------|------|----------|
| `electrodomesticos` | Violeta | Grupos para neveras, lavadoras, televisores |
| `telefonia` | Verde Menta | Grupos para smartphones |
| `motocycletas` | Salmón | Grupos para motocicletas |
| `turnos` | Violeta | Sistema de sorteos con animación de dados |
| `comprobantes` | Verde Menta | Recibos con códigos QR |

---

## 4. Base de Datos

### 4.1 Tablas

| Tabla | Descripción |
|------|----------|
| `usuarios` | Autenticación y perfiles de admin |
| `categorías` | Categories: Electrodomesticos, Telefonia, Motocycletas |
| `productos` | Catálogo de productos por categoría |
| `grupos_san` | Grupos de ahorro activos |
| `participantes` | Miembros inscritos en cada grupo |
| `pagos` | Registro de cuotas (pendiente/pagado/atrasado) |
| `turnos` | Sorteos y asignaciones de turno |
| `comprobantes` | Recibos generados con QR |

### 4.2 Relaciones

```
categorías (1) → productos (N)
productos (1) → grupos_san (N)
grupos_san (1) → participantes (N)
participantes (1) → pagos (N)
grupos_san (1) → turnos (N)
participantes (1) → turnos (N)
```

### 4.3 Campo de Precios

**Actualmente:**
- `productos.valor_total` - Precio en Bolivares (Bs)
- `grupos_san.monto_cuota` - Calculado de valor_total / numero_cuotas
- `pagos.monto` - Monto fijo de cada cuota

---

## 5. Funcionalidades

### 5.1 Autenticación
- Login con username/password
- Sesiones PHP seguras
- Recuperación de contraseña con pregunta secreta
- Crear nuevos usuarios (solo admin)

### 5.2 Dashboard
- Bento Grid con 6 módulos
- Estadísticas en tiempo real
- Responsive (Desktop/Tablet/Mobile)

### 5.3 Gestión de Grupos San
- Crear grupos vinculados a productos
- Configurar frecuencia (quincenal/mensual)
- Cálculo automático de monto de cuota

### 5.4 Inscripción de Participantes
- Registro con validación de Cédula única
- Datos: nombre, apellido, cédula, teléfono, dirección

### 5.5 Gestión de Pagos
- Seguimiento de cuotas (pendiente/pagado/atrasado)
- Registro de fecha de vencimiento y método de pago

### 5.6 Sistema de Turnos
- Sorteo aleatorio con animación de dados
- Estados: pendiente/asignado/entregado

### 5.7 Comprobantes
- Generación offline de recibos
- Códigos QR
- Estética de recibo térmico

---

## 6. Rutas y URLs

| Ruta | Descripción |
|------|------------|
| `/` | Redirecciona a login |
| `/login.php` | Página de login |
| `/dashboard.php` | Dashboard principal |
| `/crear-usuario.php` | Crear usuario |
| `/recuperar-password.php` | Recuperar contraseña |
| `/modules/electrodomesticos/` | Módulo electrodomésticos |
| `/modules/telefonia/` | Módulo telefonía |
| `/modules/motocycletas/` | Módulo motocicletas |
| `/modules/turnos/` | Gestión de turnos |
| `/modules/comprobantes/` | Comprobantes |

---

## 7. Variables de Entorno

### config/database.php
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mysan');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

## 8. Credenciales por Defecto

- **Usuario**: admin
- **Contraseña**: 1234 (bcrypt hash)

---

## 9. Paleta de Colores

| Variable | Valor | Uso |
|----------|-------|-----|
| --color-violeta | hsl(270, 80%, 65%) | Botones, módulo Electrodomesticos |
| --color-menta | hsl(160, 60%, 60%) | Éxito, módulo Telefonía |
| --color-salmon | hsl(10, 75%, 70%) | Warnings, módulo Motocycletas |
| --color-surface | #1a1a1a | Fondo de tarjetas |
| --color-background | #0D0D0D | Fondo principal |

**Tema Claro (Apple):**
- --color-primary: hsl(175, 55%, 45%) - Cyan
- --color-background: #f5f5f7
- --color-surface: #ffffff

---

## 10. Archivos JavaScript

| Archivo | Descripción |
|--------|------------|
| `shared.js` | Funciones compartidas (openModal, closeModal, showNotification) |
| `grupos.js` | CRUD de grupos San |
| `participantes.js` | CRUD de participantes |
| `productos.js` | CRUD de productos |
| `eliminar_grupo.js` | Eliminación de grupos |

---

## 11. Notas de Desarrollo

- No requiere dependencias externas (CDN)
- Todos los iconos, fuentes, CSS y JS son locales
- Usa QRCode.js para generación offline de códigos QR
- Usa FPDF para generación de PDFs
- Prepared statements en todas las consultas PDO
- Tema claro Apple con colores cyan como primario

---

## 12. Pendientes / Mejoras Futuras

- [ ] Registrar precios en USD (actualmente en Bs)
- [ ] Notificaciones de vencimiento
- [ ] Reportes y estadísticas
- [ ] Exportación PDF de comprobantes
- [ ] Certificados de entrega