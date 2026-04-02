# MySan - Sistema de Administración de Ahorros Grupales

## Descripción General

Sistema web para administración de ahorros grupales tipo San/Susu. Permite gestionar grupos de ahorro donde los participantes contribuyen cuotas periódicas y se turnan para recibir el fondo total.

**Stack**: PHP 7.4+ vanilla + MySQL + Vanilla JS  
**Diseño**: Bento Grid con tema oscuro (Dark Theme)  
**Tipo**: 100% Offline - todos los assets incluidos localmente

---

## Módulos del Sistema

| Módulo | Ruta | Propósito | Color |
|--------|------|-----------|-------|
| Electrodomésticos | `/modules/electrodomesticos/` | Grupos para neveras, lavadoras, televisores | Violeta |
| Telefonía | `/modules/telefonia/` | Sans para smartphones | Verde Menta |
| Motocicletas | `/modules/motocicletas/` | Control de marcas, cilindradas y cuotas | Salmón |
| Gestión de Turnos | `/modules/turnos/` | Sorteo con animación de dados | Violeta |
| Comprobantes | `/modules/comprobantes/` | Recibos con códigos QR | Verde Menta |

---

## Estructura de Base de Datos

### Tablas Principales

- **usuarios** - Autenticación y perfiles (id, username, password, nombre, email, pregunta_secreta, respuesta_secreta)
- **categorías** - Electrodomésticos, Telefonía, Motocicciones (id, nombre, descripcion, color)
- **productos** - Catálogo de productos por categoría (id, categoria_id, nombre, marca, modelo, valor_total)
- **grupos_san** - Grupos de ahorro activos (id, producto_id, nombre, fecha_inicio, frecuencia, numero_cuotas, cupos_totales, cupos_ocupados, monto_cuota, estado)
- **participantes** - Miembros inscritos (id, grupo_san_id, nombre, apellido, cedula, telefono, direccion, ha_recibido, activo)
- **pagos** - Registro de cuotas (id, participante_id, numero_cuota, monto, fecha_pago, fecha_vencimiento, estado, metodo_pago)
- **turnos** - Sorteos y asignaciones (id, grupo_san_id, participante_id, numero_turno, fecha_turno, metodo_asignacion, estado)
- **comprobantes** - Recibos generados (id, tipo, pago_id, turno_id, codigo_qr, datos_json)

### Relaciones

```
categorias (1) → productos (N)
productos (1) → grupos_san (N)
grupos_san (1) → participantes (N)
participantes (1) → pagos (N)
grupos_san (1) → turnos (N)
participantes (1) → turnos (N)
```

---

## Archivos Clave

### Raíz
- `index.php` - Redirección a login.php
- `login.php` - Página de autenticación
- `dashboard.php` - Dashboard principal con Bento Grid
- `logout.php` - Cierre de sesión
- `crear-usuario.php` - Crear nuevos usuarios
- `recuperar-password.php` - Recuperación de contraseña

### Configuración
- `config/database.php` - Conexión PDO MySQL, funciones helper (requireLogin, getCurrentUser, jsonResponse)

### API
- `api/auth.php` - Endpoints de autenticación
- `api/grupos.php` - CRUD de grupos San
- `api/participantes.php` - CRUD de participantes
- `api/pagos.php` - Gestión de pagos
- `api/productos.php` - CRUD de productos
- `api/turnos.php` - Gestión de turnos
- `api/comprobantes.php` - Generación de comprobantes

### Módulos
- `modules/electrodomesticos/index.php` - Módulo principal
- `modules/electrodomesticos/pagos.php` - Gestión de pagos
- `modules/telefonia/index.php` - Módulo principal
- `modules/telefonia/pagos.php` - Gestión de pagos
- `modules/motocicletas/index.php` - Módulo principal (doble ancho en dashboard)
- `modules/motocicletas/pagos.php` - Gestión de pagos
- `modules/turnos/index.php` - Sistema de sorteos
- `modules/comprobantes/index.php` - Generación de recibos

### Assets
- `assets/css/` - Estilos (reset.css, variables.css, bento-grid.css, main.css)
- `assets/js/` - JavaScript (shared.js, grupos.js, participantes.js, productos.js, eliminar_grupo.js)
- `assets/fonts/` - Fuentes locales
- `assets/icons/` - Sprite SVG de Feather Icons

### Librerías
- `fpdf/` - Generación de PDFs

---

## Funcionalidades Principales

1. **Sistema de Autenticación**
   - Login con username/password
   - Sesiones PHP seguras
   - Recuperación de contraseña con pregunta secreta

2. **Dashboard Bento Grid**
   - 6 módulos con estadísticas en tiempo real
   - Responsive (Desktop/Tablet/Mobile)

3. **Gestión de Grupos San**
   - Crear grupos vinculados a productos
   - Configurar frecuencia (quincenal/mensual)
   - Cálculo automático de monto de cuota

4. **Inscripción de Participantes**
   - Registro con validación de Cédula única
   - Datos: nombre, apellido, cédula, teléfono, dirección

5. **Gestión de Pagos**
   - Seguimiento de cuotas (pendiente/pagado/atrasado)
   - Registro de fecha de vencimiento y método de pago

6. **Sistema de Turnos**
   - Sorteo aleatorio con animación de dados
   - Estados: pendiente/asignado/entregado

7. **Comprobantes con QR**
   - Generación offline de recibos
   - Estética de recibo térmico

---

## Credenciales por Defecto

- **Usuario**: admin
- **Contraseña**: 1234 (bcrypt hash)

---

## Paleta de Colores

| Variable | Valor | Uso |
|----------|-------|-----|
| --color-violeta | hsl(270, 80%, 65%) | Botones principales, módulo Electrodomésticos |
| --color-menta | hsl(160, 60%, 60%) | Éxito, módulo Telefonía, Comprobantes |
| --color-salmon | hsl(10, 75%, 70%) | Warnings, módulo Motocicletas |
| --color-surface | #1a1a1a | Fondo de tarjetas |
| --color-background | #0D0D0D | Fondo principal |
| --glass-background | rgba(26, 26, 26, 0.7) | Fondo con transparencia |
| --glass-border | rgba(255, 255, 255, 0.1) | Bordes transparentes |

---

## Animaciones y Efectos

- **Glassmorphism**: backdrop-blur en header y modales
- **Floating animation**: Login box flotante
- **Spin animation**: Dados girando (1s cubic-bezier)
- **Glow animation**: Ganadores brillando en violeta
- **Shimmer effect**: Barras de progreso
- **Hover effects**: Todas las tarjetas con transform y shadow

---

## Configuración de Base de Datos

```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mysan');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---

## Notas de Desarrollo

- No requiere dependencias externas (CDN)
- Todos los iconos, fuentes, CSS y JS son locales
- Usa QRCode.js para generación offline de códigos QR
- Usa FPDF para generación de PDFs
- Prepared statements en todas las consultas PDO

---

## Próximas Funcionalidades (pendientes)

- [ ] Formularios de registro de participantes
- [ ] Sistema de pagos completo
- [ ] Asignación manual de turnos
- [ ] Certificados de entrega
- [ ] Exportación PDF de comprobantes
- [ ] Reportes y estadísticas
- [ ] Notificaciones de vencimiento