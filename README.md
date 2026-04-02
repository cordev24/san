# MySan - Sistema de Administración de Ahorros Grupales

![MySan](https://img.shields.io/badge/MySan-Sistema%20San%2FSusu-blueviolet)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![Offline](https://img.shields.io/badge/Offline-Ready-green)

Sistema web moderno para la administración de ahorros grupales (San/Susu) con interfaz Bento Grid en tema oscuro. Completamente offline con todos los assets locales.

## 🎨 Características Principales

- **Tema Oscuro Premium**: Diseño Bento Grid con glassmorphism y colores pastel neón
- **100% Offline**: Todos los iconos, fuentes, CSS y JS incluidos localmente
- **4 Módulos Principales**:
  - 📦 **Electrodomésticos**: Gestión de grupos para neveras, lavadoras, televisores
  - 📱 **Telefonía**: Administración de Sans para smartphones de alta gama
  - 🏍️ **Motocicletas**: Control de marcas, cilindradas y cuotas (módulo destacado)
  - 🎲 **Gestión de Turnos**: Sistema de sorteo con animación de dados
- **Comprobantes Digitales**: Generación de recibos con códigos QR offline
- **Responsive Design**: Adaptable a desktop, tablet y móvil

## 🎨 Paleta de Colores

- **Fondo**: `#0D0D0D` (Negro profundo)
- **Violeta Eléctrico**: `hsl(270, 80%, 65%)` - Botones principales y acentos
- **Verde Menta**: `hsl(160, 60%, 60%)` - Indicadores de éxito y Telefonía
- **Salmón Suave**: `hsl(10, 75%, 70%)` - Advertencias y Motocicletas

## 📋 Requisitos del Sistema

- **Servidor Web**: Apache 2.4+ (WAMP, XAMPP, LAMP)
- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Navegador**: Chrome, Firefox, Edge (versiones recientes)

## 🚀 Instalación

### 1. Clonar/Copiar el Proyecto

```bash
# Copiar la carpeta mysan a tu directorio web
# Ejemplo para WAMP:
C:\wamp\www\mysan
```

### 2. Configurar la Base de Datos

```bash
# Abrir phpMyAdmin o MySQL CLI
# Importar el archivo database.sql
mysql -u root -p < database.sql
```

O desde phpMyAdmin:
1. Crear base de datos `mysan`
2. Importar `database.sql`

### 3. Configurar Conexión

Editar `config/database.php` si es necesario:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'mysan');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Acceder al Sistema

```
http://localhost/mysan
```

**Credenciales por defecto:**
- Usuario: `admin`
- Contraseña: `admin123`

## 📁 Estructura del Proyecto

```
mysan/
├── assets/
│   ├── css/
│   │   ├── reset.css
│   │   ├── variables.css
│   │   ├── bento-grid.css
│   │   └── main.css
│   ├── js/
│   │   ├── qrcode.min.js (Offline QR generation)
│   │   └── modules/
│   ├── fonts/
│   │   └── inter.css
│   └── icons/
│       └── feather-sprite.svg
├── config/
│   └── database.php
├── modules/
│   ├── electrodomesticos/
│   ├── telefonia/
│   ├── motocicletas/
│   ├── turnos/
│   └── comprobantes/
├── api/
│   └── auth.php
├── index.php (redirect to login)
├── login.php
├── dashboard.php
├── logout.php
└── database.sql
```

## 🎯 Funcionalidades Implementadas

### ✅ Sistema de Autenticación
- Login con Bento Box flotante
- Sesiones seguras con PHP
- Logout funcional

### ✅ Dashboard Principal
- Bento Grid responsive
- 6 módulos principales
- Estadísticas en tiempo real
- Navegación intuitiva

### ✅ Módulo Electrodomésticos
- Listado de productos disponibles
- Grupos San activos
- Indicadores de progreso (8/10 cupos)
- Tarjetas con iconos lineales

### ✅ Módulo Telefonía
- Grid de smartphones
- Indicadores Verde Menta
- Seguimiento de marcas y modelos

### ✅ Módulo Motocicletas
- **Bento Box doble ancho**
- **Borde Salmón Suave destacado**
- Badge "Alto Valor"
- Control de marcas y cilindradas

### ✅ Gestión de Turnos
- **Animación de dados rotando**
- Sorteo aleatorio de ganadores
- **Nombre del ganador brilla en Violeta Eléctrico**
- Timeline rotativa con fechas pasadas/actuales/futuras
- Toggle Quincenal/Mensual
- Cálculo automático de montos

### ✅ Comprobantes
- Generación de recibos de pago
- **Códigos QR offline** (qrcode.js)
- Estética de recibo térmico
- Fondo oscuro con texto blanco nítido
- Modal con glassmorphism

## 🎨 Características de Diseño

### Bento Grid System
- Grid CSS responsive de 12 columnas
- Breakpoints: Desktop (1600px), Tablet (768px), Mobile (480px)
- Tarjetas con glassmorphism y blur

### Animaciones
- Floating animation para login
- Spin animation para dados (1s cubic-bezier)
- Glow animation para ganadores
- Shimmer effect en progress bars
- Hover effects en todas las tarjetas

### Glassmorphism
- Background: `rgba(26, 26, 26, 0.7)`
- Backdrop blur: `20px`
- Bordes semitransparentes
- Sombras con glow effects

## 🔧 Personalización

### Cambiar Colores

Editar `assets/css/variables.css`:

```css
:root {
  --color-violeta: hsl(270, 80%, 65%);
  --color-menta: hsl(160, 60%, 60%);
  --color-salmon: hsl(10, 75%, 70%);
}
```

### Agregar Nuevos Productos

```sql
INSERT INTO productos (categoria_id, nombre, marca, modelo, valor_total) 
VALUES (1, 'Producto Nuevo', 'Marca', 'Modelo', 10000.00);
```

### Crear Nuevo Usuario

```php
$password = password_hash('nueva_contraseña', PASSWORD_DEFAULT);
// Insertar en tabla usuarios
```

## 📊 Base de Datos

### Tablas Principales

- `usuarios` - Autenticación y perfiles
- `categorias` - Electrodomésticos, Telefonía, Motocicletas
- `productos` - Catálogo de productos
- `grupos_san` - Grupos de ahorro activos
- `participantes` - Inscritos en cada grupo
- `pagos` - Registro de cuotas
- `turnos` - Sorteos y asignaciones
- `comprobantes` - Recibos generados

## 🌐 Compatibilidad de Navegadores

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Edge 90+
- ✅ Safari 14+
- ⚠️ IE11 (no soportado)

## 🔒 Seguridad

- Passwords hasheados con `bcrypt`
- Sesiones PHP seguras
- Prepared statements (PDO)
- Validación de inputs
- CSRF protection recomendado para producción

## 📝 Notas de Desarrollo

### Offline Assets
Todos los recursos están incluidos localmente:
- ✅ Inter Font (system fallback)
- ✅ Feather Icons (SVG sprite)
- ✅ QRCode.js (minified)
- ✅ CSS completo
- ✅ No CDN dependencies

### Próximas Funcionalidades
- [ ] Formularios de registro de participantes
- [ ] Sistema de pagos completo
- [ ] Asignación manual de turnos
- [ ] Certificados de entrega
- [ ] Exportación PDF de comprobantes
- [ ] Reportes y estadísticas
- [ ] Notificaciones de vencimiento

## 🐛 Solución de Problemas

### Error de Conexión a Base de Datos
```
Verificar credenciales en config/database.php
Asegurar que MySQL esté corriendo
Verificar que la base de datos 'mysan' existe
```

### Iconos no se Muestran
```
Verificar que feather-sprite.svg está incluido
Revisar rutas relativas en includes
```

### QR Codes no Generan
```
Verificar que qrcode.min.js está cargado
Revisar consola del navegador para errores
```

## 👨‍💻 Créditos

- **Diseño**: Bento Grid con tema oscuro personalizado
- **Iconos**: Feather Icons
- **Fuentes**: Inter (Google Fonts)
- **QR Codes**: QRCode.js
- **Framework**: Vanilla PHP + CSS + JavaScript

## 📄 Licencia

Este proyecto es de uso personal/educativo. 

---

**MySan** - Sistema de Administración de Ahorros Grupales
Desarrollado con ❤️ usando Bento Grid y Dark Theme
