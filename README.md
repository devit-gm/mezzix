# MEZZIX

> Sistema de gestión integral para eventos y restaurantes con Laravel 10

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 📋 Descripción

**MEZZIX** es una aplicación web full-stack desarrollada en Laravel que proporciona tres modos de operación distintos:

- **Modo Fichas**: Sistema de gestión de eventos con control de invitados, gastos y compras
- **Modo Mesas**: Sistema POS para restaurantes con gestión de mesas, camareros y comandas en tiempo real
- **Modo Agencia de Eventos**: Plataforma de inscripción a eventos públicos con gestión de capacidad y notificaciones

## ✨ Características Principales

### 🎫 Modo Fichas (Eventos)

- **Gestión de Fichas de Eventos**
  - Creación y administración de eventos con múltiples tipos (bodas, comuniones, bautizos, etc.)
  - Control de fechas, horarios y menús
  - Asignación de responsables y notas

- **Control de Invitados**
  - Registro de asistentes con datos de contacto
  - Límites configurables de invitados por ficha
  - Sistema de cobro por invitado adicional
  - Primer invitado gratis (configurable)
  - Grupos de invitados con tarifa especial

- **Gestión de Consumos**
  - Añadir productos/servicios a fichas
  - Familias de productos organizadas visualmente
  - Imágenes de productos con lazy loading y caché
  - Control de stock en tiempo real
  - Lectura de códigos de barras

- **Control de Gastos**
  - Registro de gastos asociados a cada ficha
  - Categorización de gastos
  - Cálculo automático de rentabilidad

- **Sistema de Compras y Albaranes**
  - **Gestión de Proveedores**
    - Ficha completa con datos fiscales y comerciales
    - Condiciones de pago y descuentos
    - Historial de compras y estadísticas
    - Estados activo/inactivo
    - Búsqueda avanzada y filtros
  - **Albaranes de Entrada**
    - Creación con selector de proveedor
    - Múltiples líneas de productos
    - Estados: Pendiente, Recibido, Facturado
    - Confirmación de recepción con actualización de stock
    - Generación de PDF con datos del proveedor
    - Filtros por proveedor, estado y fechas
  - Registro de compras con recibos
  - Control de inventario automático
  - Base de datos por sitio (multi-tenant)

### 🎉 Modo Agencia de Eventos

- **Gestión de Eventos Públicos**
  - Sistema de roles: solo administradores pueden crear eventos
  - Usuarios normales pueden inscribirse a eventos disponibles
  - Campos específicos: descripción, foto, ubicación, precio, aforo máximo
  - Control de inscritos actuales vs capacidad máxima

- **Sistema de Inscripciones**
  - Inscripción/cancelación con un clic
  - Validación de plazas disponibles
  - Contador en tiempo real de inscritos
  - Indicador visual de inscripción en catálogo (check verde)
  - Lista de inscritos con fecha de inscripción

- **Catálogo de Eventos**
  - Vista unificada para todos los usuarios
  - Información detallada: fecha, hora, ubicación, precio, aforo
  - Botón de inscripción/cancelación para usuarios básicos
  - Botones de edición/eliminación solo para administradores
  - Filtrado de eventos activos/finalizados

- **Notificaciones Push (Firebase)**
  - Notificación al usuario al inscribirse/cancelar
  - Notificación al creador del evento con ocupación actual (ej: "15/20 asistentes")
  - Notificación a todos los usuarios cuando se crea un nuevo evento
  - Soporte foreground y background
  - Click en notificación lleva al detalle del evento

- **Permisos y Seguridad**
  - Usuarios básicos solo ven y se inscriben a eventos
  - Solo administradores pueden crear/editar/eliminar eventos
  - Creadores de eventos pueden gestionar sus propios eventos
  - Control de permisos en botones y rutas

### 🍽️ Modo Mesas (Restaurante)

- **Grid Visual de Mesas**
  - Visualización en tiempo real del estado de todas las mesas
  - Estados: Libre, Ocupada, Cerrada
  - Código de colores intuitivo (verde, rojo, gris)
  - Información de camarero, comensales e importe en cada mesa
  - Vista de ticket detallado en cada mesa con productos y totales

- **Gestión de Mesas**
  - Generación masiva de mesas con prefijo personalizable
  - Creación individual de mesas
  - Edición de descripción y número
  - Reordenamiento drag & drop (próximamente)
  - Eliminación de mesas (solo si están libres)

- **Flujo de Trabajo para Camareros**
  1. **Abrir Mesa**: Asignar número de comensales y tomar la mesa
  2. **Tomar Mesa**: Asumir el control de una mesa de otro camarero
  3. **Añadir Consumos**: Productos y servicios desde familias visuales
  4. **Ver Ticket**: Consultar el detalle de consumos de cualquier mesa desde el grid
  5. **Cerrar Mesa**: Cobrar con múltiples métodos de pago y opción de propina
  6. **Liberar Mesa**: Resetear la mesa a estado libre

- **Panel de Estadísticas**
  - Mesas libres/ocupadas en tiempo real
  - Mis mesas activas
  - Mi facturación del turno

- **Control de Camareros**
  - Rol específico "Usuario Mesas" con menú simplificado
  - Acceso directo al grid desde el logo del navbar
  - Sin acceso a configuración ni gestión administrativa

### 💰 Sistema de Facturación e IVA

- **Gestión de Facturas**
  - Generación automática de facturas al cerrar mesas
  - Numeración secuencial automática por año
  - Datos del cliente (nombre, NIF/CIF, dirección)
  - Desglose completo de productos y servicios

- **Cálculo de IVA**
  - Sistema adaptado a precios PVP (con IVA incluido)
  - Cálculo automático de base imponible: `PVP / (1 + IVA/100)`
  - Soporte para múltiples tipos de IVA: 0%, 4%, 10%, 21%
  - Desglose detallado por tipo de IVA en facturas
  - Visualización de IVA en resúmenes de mesas

- **Facturación de Mesas**
  - Modal de facturación con datos del cliente opcionales
  - Generación de PDF con diseño profesional
  - Visualización de facturas emitidas con filtros por fecha
  - Búsqueda y consulta de facturas históricas
  - Estadísticas: total facturas, base imponible, total IVA, importe total

- **Gestión de Sitios Multi-tenant**
  - Datos fiscales por sitio: CIF, dirección, teléfono
  - Información del emisor en facturas
  - Logo personalizado por restaurante/negocio

### 📊 Informes y Reportes

#### Modo Fichas
- Balance de fichas por fechas
- Listado de fichas pendientes/cerradas
- Próximas reservas
- Productos más vendidos
- Facturación automática con envío por email

#### Modo Mesas
- Ventas por productos con desglose de IVA
- Ventas por camareros
- Ocupación de mesas
- Histórico de mesas cerradas
- Facturas emitidas con totales

### 🔐 Sistema de Permisos

- **Roles Integrados**: Administrador, Editor, Usuario, Usuario Mesas
- **Permisos Granulares**: Basado en Spatie Laravel Permission
- **Multi-sitio**: Soporte para múltiples restaurantes/eventos con base de datos independiente por sitio

### 📱 Notificaciones

- **Email**: Configuración SMTP personalizable por sitio
- **SMS**: Integración con Twilio
- **WhatsApp**: Mensajes automáticos vía Twilio WhatsApp API
- **Firebase Cloud Messaging**: Notificaciones push (en desarrollo)

### 🎨 Interfaz de Usuario

- **Diseño Responsive**: Bootstrap 5 con CSS Grid optimizado (3/4/5 columnas)
- **Temas Personalizables**: SCSS por sitio (app.scss, eldespiste.scss)
- **Optimización de Imágenes**:
  - Lazy loading nativo
  - Cache HTTP (1 año para imágenes, 1 mes para CSS/JS)
  - Cache busting con timestamps
  - Atributos width/height para prevenir layout shifts
- **Iconos**: Bootstrap Icons
- **Modo Oscuro**: (en desarrollo)

### 📱 PWA y Modo Offline

- **Progressive Web App**:
  - Instalable en dispositivos móviles y escritorio
  - Service Worker con estrategias de caché inteligentes
  - Manifest configurado para cada sitio
  - Splash screens personalizados

- **Funcionamiento Offline**:
  - Caché de assets estáticos (CSS, JS, imágenes)
  - Caché dinámico de páginas visitadas
  - Cola de operaciones con IndexedDB
  - Sincronización automática al recuperar conexión
  - Background Sync API para reintentos

- **Indicador de Estado**:
  - Verde: Online y sincronizado
  - Amarillo: Offline con operaciones pendientes
  - Rojo: Error de sincronización
  - Notificaciones toast de cambios de estado

- **Sincronización Inteligente**:
  - Detección automática de conexión
  - Sincronización manual forzada
  - Gestión de cola de operaciones
  - Persistencia de datos pendientes

## 🚀 Instalación

### Requisitos Previos

- PHP >= 8.1
- Composer
- MySQL >= 8.0 o MariaDB >= 10.3
- Node.js >= 16.x (para compilar assets)
- Extensiones PHP: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, GD

### Paso a Paso

1. **Clonar el repositorio**

```bash
git clone https://github.com/devit-gm/agsuitepro.git
cd agsuitepro
```

2. **Instalar dependencias**

```bash
composer install
npm install
```

3. **Configurar entorno**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos**

Edita `.env` con tus credenciales:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agsuitepro
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña

# Base de datos por sitio (multi-tenancy)
DB_DATABASE_SITE=agsuitepro_site1
```

5. **Ejecutar migraciones**

```bash
php artisan migrate --seed
```

6. **Compilar assets**

```bash
npm run dev      # Desarrollo
npm run build    # Producción
```

7. **Configurar permisos**

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

8. **Configurar servidor web**

Para Apache, asegúrate de que el DocumentRoot apunte a la carpeta `public/` y que `mod_rewrite` esté habilitado.

Copia `public/htaccess.txt` a `public/.htaccess` si no existe.

9. **Iniciar servidor de desarrollo**

```bash
php artisan serve
```

Accede a `http://localhost:8000`

### Credenciales por Defecto

```
Email: admin@agsuitepro.com
Password: admin123
```

**⚠️ IMPORTANTE**: Cambia estas credenciales inmediatamente en producción.

## ⚙️ Configuración

### Modo de Operación

Configura el modo desde **Ajustes** en la interfaz web o directamente en base de datos:

**Modo Fichas (Eventos)**:
```sql
UPDATE ajustes SET modo_operacion = 'fichas';
UPDATE ajustes SET mostrar_usuarios = 1;
UPDATE ajustes SET mostrar_gastos = 1;
UPDATE ajustes SET mostrar_compras = 1;
```

**Modo Mesas (Restaurante)**:
```sql
UPDATE ajustes SET modo_operacion = 'mesas';
UPDATE ajustes SET mostrar_usuarios = 0;
UPDATE ajustes SET mostrar_gastos = 0;
UPDATE ajustes SET mostrar_compras = 0;
```

### Generar Mesas Iniciales

Desde la interfaz en **Mesas > Generar Mesas** o con el seeder:

```bash
php artisan db:seed --class=MesasSeeder
```

Esto crea 20 mesas por defecto en estado "libre".

### Configuración de Email

Edita en **Ajustes > Configuración de Email** o en `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu_email@gmail.com
MAIL_PASSWORD=tu_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Configuración de SMS/WhatsApp (Twilio)

```env
TWILIO_SID=tu_account_sid
TWILIO_AUTH_TOKEN=tu_auth_token
TWILIO_PHONE_NUMBER=+34123456789
TWILIO_WHATSAPP_NUMBER=whatsapp:+34123456789
```

### Firebase (Notificaciones Push)

Coloca tu archivo de credenciales en `storage/firebase-credentials.json` (este archivo está en `.gitignore` por seguridad).

```env
FIREBASE_CREDENTIALS=storage/firebase-credentials.json
```

## 📖 Uso

### Modo Fichas

#### Crear una Ficha

1. Ir a **Fichas > Nueva Ficha**
2. Rellenar datos: tipo, fecha, hora, menú, responsables
3. Guardar

#### Añadir Invitados

1. Abrir ficha → **Asistentes**
2. Hacer clic en **+ Añadir Invitado**
3. Rellenar nombre, teléfono, email
4. Guardar

#### Añadir Consumos

1. Abrir ficha → **Lista**
2. Seleccionar familia de productos
3. Hacer clic en productos para añadir
4. Modificar cantidades con +/-

#### Registrar Gastos

1. Abrir ficha → **Gastos**
2. Hacer clic en **+ Añadir Gasto**
3. Rellenar concepto e importe
4. Guardar

#### Cerrar Ficha

1. Abrir ficha → **Resumen**
2. Revisar totales
3. Seleccionar método de pago
4. Hacer clic en **Enviar**
5. Opcionalmente marcar "Facturar" para envío por email

### Modo Mesas

#### Flujo Completo de Mesa

**1. Abrir Mesa**

- En el grid, hacer clic en una mesa verde (Libre)
- En el modal, introducir número de comensales
- Hacer clic en **Abrir Mesa**
- La mesa cambia a rojo (Ocupada) con tu nombre

**2. Añadir Consumos**

- Hacer clic en la mesa roja
- Ir a **Familias** → seleccionar familia
- Hacer clic en productos para añadir
- Volver a **Lista** para revisar

**3. Cerrar Mesa**

- En el grid, hacer clic en la mesa ocupada
- Hacer clic en **Cerrar Mesa**
- Revisar consumos en el modal con desglose de IVA
- Seleccionar método de pago (efectivo, tarjeta, etc.)
- Opcionalmente añadir propina
- **Opcionalmente facturar**: marcar checkbox e introducir datos del cliente
- Hacer clic en **Cobrar**
- La mesa cambia a gris (Cerrada)
- Si se facturó, se genera PDF con desglose de IVA

**4. Liberar Mesa**

- Hacer clic en la mesa gris (Cerrada)
- Hacer clic en **Liberar**
- La mesa vuelve a verde (Libre)

#### Gestión de Mesas (Admin)

**Generar Mesas en Lote**
- Ir a **Mesas** (botón en navbar superior)
- Hacer clic en **Generar Mesas**
- Establecer prefijo (ej: "Mesa ") y cantidad (ej: 15)
- Hacer clic en **Generar**

**Editar Mesa**
- Hacer clic en el icono de lápiz (esquina superior derecha de la mesa)
- Modificar descripción o número
- Guardar

**Eliminar Mesa**
- Solo posible si está en estado "Libre"
- Hacer clic en el icono de papelera
- Confirmar eliminación

## 🗂️ Estructura del Proyecto

```
agsuitepro/
├── app/
│   ├── Console/           # Comandos Artisan
│   ├── Enums/            # Enumeraciones (EstadoMesa, TipoFicha, etc.)
│   ├── Exceptions/       # Manejadores de excepciones
│   ├── Http/
│   │   ├── Controllers/  # Controladores principales
│   │   │   ├── FichasController.php      # Fichas + Mesas
│   │   │   ├── FacturaMesaController.php # Facturación de mesas
│   │   │   ├── ProductosController.php   # Productos
│   │   │   ├── FamiliasController.php    # Familias
│   │   │   ├── UsuariosController.php    # Usuarios
│   │   │   ├── AlbaranesController.php   # Gestión de albaranes
│   │   │   ├── InformesController.php    # Reportes
│   │   │   ├── AjustesController.php     # Configuración
│   │   │   ├── SitiosController.php      # Gestión multi-tenant
│   │   │   ├── WhatsAppController.php    # WhatsApp API
│   │   │   └── SmsController.php         # SMS Twilio
│   │   ├── Middleware/   # Middlewares (DetectSite, VerificarRol)
│   │   └── Kernel.php    # Registro de middlewares
│   ├── Models/           # Modelos Eloquent
│   │   ├── Ficha.php     # Fichas/Mesas con scopes
│   │   ├── FacturaMesa.php # Facturas con cálculo de IVA
│   │   ├── Producto.php  # Con métodos baseImponible() e importeIva() - UUID PK
│   │   ├── Servicio.php  # Con métodos baseImponible() e importeIva()
│   │   ├── Familia.php
│   │   ├── Albaran.php   # Albaranes con conexión 'site'
│   │   ├── AlbaranLinea.php # Líneas de albaran con UUID FK
│   │   ├── User.php
│   │   ├── Ajustes.php
│   │   ├── Site.php      # Gestión multi-tenant
│   │   └── ...
│   ├── Providers/        # Service Providers
│   ├── Services/         # Servicios (TwilioService, etc.)
│   └── helpers.php       # Funciones globales (fichaRoute, cachedImage)
├── config/
│   ├── app.php           # Configuración general
│   ├── database.php      # Conexiones DB (central + site)
│   ├── permission.php    # Spatie Permissions
│   ├── services.php      # APIs externas (Twilio, Firebase)
│   └── twilio.php        # Configuración Twilio
├── database/
│   ├── migrations/       # Migraciones de base de datos
│   ├── seeders/          # Seeders (MesasSeeder, RolesSeeder)
│   └── factories/        # Factories para testing
├── public/
│   ├── css/              # CSS compilado
│   ├── js/               # JavaScript compilado
│   ├── images/           # Imágenes públicas
│   ├── .htaccess         # Configuración Apache con cache
│   └── index.php         # Entry point
├── resources/
│   ├── css/              # CSS fuente
│   ├── js/
│   │   └── app.js        # JavaScript principal (Bootstrap, listeners)
│   ├── sass/
│   │   ├── app.scss      # Estilos globales
│   │   └── eldespiste.scss # Tema personalizado
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php   # Layout principal
│   │   ├── fichas/
│   │   │   ├── index.blade.php # Lista de fichas
│   │   │   ├── create.blade.php
│   │   │   ├── edit.blade.php
│   │   │   ├── lista.blade.php # Consumos
│   │   │   ├── familias.blade.php
│   │   │   ├── productos.blade.php
│   │   │   ├── usuarios.blade.php # Invitados
│   │   │   ├── gastos.blade.php
│   │   │   ├── resumen.blade.php # Con desglose de IVA
│   │   │   ├── mesas-grid.blade.php # Grid de mesas
│   │   │   ├── pdf-mesa.blade.php # Plantilla PDF factura
│   │   │   └── modales/
│   │   │       ├── abrir-mesa.blade.php
│   │   │       ├── cerrar-mesa.blade.php # Con facturación
│   │   │       └── facturar-mesa.blade.php
│   │   ├── facturas/
│   │   │   ├── index.blade.php # Listado de facturas
│   │   │   └── show.blade.php  # Detalle de factura
│   │   ├── productos/
│   │   ├── familias/
│   │   ├── albaranes/
│   │   │   ├── index.blade.php   # Listado de albaranes
│   │   │   ├── create.blade.php  # Crear albarán
│   │   │   ├── edit.blade.php    # Editar albarán
│   │   │   └── show.blade.php    # Detalle con confirmación
│   │   ├── usuarios/
│   │   ├── ajustes/
│   │   └── informes/
│   └── lang/
│       ├── es.json        # Traducciones español
│       └── es/            # Traducciones Laravel
├── routes/
│   ├── web.php           # Rutas web principales
│   ├── api.php           # Rutas API (futuro)
│   └── channels.php      # Broadcasting (futuro)
├── storage/
│   ├── app/              # Archivos subidos
│   ├── logs/             # Logs Laravel
│   └── framework/        # Cache, sessions, views compiladas
├── tests/                # Tests unitarios y feature
├── .env                  # Variables de entorno (NO en Git)
├── .gitignore            # Archivos ignorados por Git
├── composer.json         # Dependencias PHP
├── package.json          # Dependencias Node.js
├── vite.config.js        # Configuración Vite
├── webpack.mix.js        # Mix (legacy)
└── README.md             # Este archivo
```

## 🛠️ Tecnologías

### Backend
- **Laravel 10**: Framework PHP moderno con routing, ORM, autenticación
- **PHP 8.1+**: Tipado fuerte, enums, atributos
- **MySQL/MariaDB**: Base de datos relacional
- **Eloquent ORM**: Gestión de modelos y relaciones

### Frontend
- **Bootstrap 5**: Framework CSS responsive
- **Blade Templates**: Motor de plantillas de Laravel
- **JavaScript Vanilla**: Sin frameworks pesados, listeners nativos
- **Bootstrap Icons**: Iconografía
- **CSS Grid**: Layouts modernos y flexibles

### Integraciones
- **Twilio**: SMS y WhatsApp Business API
- **Firebase**: Notificaciones push (FCM)
- **DomPDF**: Generación de PDFs
- **Snappy/wkhtmltopdf**: PDFs avanzados con HTML/CSS

### Herramientas
- **Composer**: Gestor de dependencias PHP
- **npm**: Gestor de paquetes Node.js
- **Vite**: Build tool y HMR para desarrollo
- **Laravel Mix**: Alternativa a Vite (legacy)
- **Git**: Control de versiones

## 🔒 Seguridad

- **Autenticación**: Laravel Sanctum + sesiones
- **Autorización**: Spatie Laravel Permission con roles y permisos
- **Protección CSRF**: Tokens en formularios
- **Validación**: Request validation en controladores
- **Sanitización**: Htmlspecialchars en vistas Blade
- **Credenciales**: Variables de entorno en `.env` (no versionado)
- **Firebase Credentials**: Archivo JSON en `.gitignore`
- **HTTPS**: Recomendado en producción con certificado SSL

### Buenas Prácticas Implementadas

- `.env` y `*.key` en `.gitignore`
- `storage/firebase-credentials.json` excluido de Git
- Regenerar `APP_KEY` en cada instalación
- Usar contraseñas seguras y 2FA para cuentas admin
- Mantener Laravel y dependencias actualizadas

## 📊 Base de Datos

### Tablas Principales

#### `users`
Usuarios del sistema con roles (Admin, Editor, Usuario, Usuario Mesas).

#### `fichas`
Núcleo del sistema. Almacena fichas de eventos O mesas de restaurante.

**Campos clave**:
- `tipo`: Tipo de ficha (1-Boda, 2-Comunión, etc.) o 5-Mesa
- `modo`: `'ficha'` o `'mesa'`
- `estado`: 0-Pendiente, 1-Confirmada, 2-Cerrada, 3-Cancelada
- `estado_mesa`: `'libre'`, `'ocupada'`, `'cerrada'` (solo modo mesas)
- `numero_mesa`: Identificador de mesa (VARCHAR)
- `camarero_id`: Usuario asignado a la mesa
- `numero_comensales`: Cantidad de personas
- `hora_apertura`, `hora_cierre`: Timestamps de apertura/cierre

#### `facturas_mesa`
Facturas generadas al cerrar mesas.

**Campos clave**:
- `numero_factura`: Numeración secuencial (ej: 2025/00001)
- `ficha_id`: Relación con la mesa
- `fecha`: Fecha de emisión
- `cliente_nombre`, `cliente_nif`: Datos del cliente
- `subtotal`: Base imponible total
- `total_iva`: Suma de todas las cuotas de IVA
- `total`: Importe total a pagar
- `detalles`: JSON con líneas de detalle, desglose de IVA y datos de mesa

#### `fichas_productos`
Relación muchos-a-muchos entre fichas y productos con cantidad y precio.

#### `fichas_servicios`
Relación muchos-a-muchos entre fichas y servicios.

#### `fichas_usuarios`
Invitados/asistentes de una ficha (modo fichas).

#### `fichas_gastos`
Gastos asociados a fichas (modo fichas).

#### `productos`
Catálogo de productos con stock, precio, imagen, familia. Usa UUID como clave primaria.

#### `familias`
Categorías de productos con imagen.

#### `albaranes` (por sitio)
Gestión de albaranes de entrada/compras.

**Campos clave**:
- `numero_albaran`: Número único del albarán
- `proveedor`: Nombre del proveedor
- `nif`: NIF/CIF del proveedor
- `fecha`: Fecha del albarán
- `estado`: 'pendiente', 'recibido', 'facturado'
- `total`: Importe total del albarán
- `usuario_id`: Usuario que creó el albarán (referencia sin FK)
- `fecha_recepcion`: Fecha de confirmación de recepción

#### `albaran_lineas` (por sitio)
Líneas de detalle de los albaranes.

**Campos clave**:
- `albaran_id`: FK a albaranes (CASCADE DELETE)
- `producto_id`: FK a productos.uuid (CHAR(36), CASCADE DELETE)
- `cantidad`: Cantidad recibida
- `precio_coste`: Precio de coste unitario
- `subtotal`: Cantidad × precio_coste (calculado automáticamente)

#### `servicios`
Servicios adicionales (DJ, fotografía, etc.).

#### `recibos`
Recibos de compra a proveedores.

#### `ajustes`
Configuración global del sitio (modo_operacion, precios, SMTP, etc.).

#### `sitios`
Gestión multi-tenant con datos fiscales.

**Campos clave**:
- `nombre`: Nombre del negocio
- `dominio`: Dominio del sitio
- `cif`: CIF/NIF fiscal
- `direccion`: Dirección completa
- `telefono`: Teléfono de contacto
- `db_host`, `db_name`, `db_user`, `db_password`: Conexión a base de datos del sitio
- `mail_*`: Configuración SMTP específica del sitio
- `ruta_logo`, `ruta_logo_nav`: Rutas a logos personalizados

#### `permissions`, `roles`, `role_has_permissions`, `model_has_roles`
Sistema de permisos de Spatie.

### Migraciones Importantes

- `create_fichas_table`: Estructura base de fichas/mesas
- `add_mesas_fields_to_fichas`: Campos para modo mesas (estado_mesa, camarero_id, etc.)
- `create_productos_table`, `create_familias_table`: Catálogo con IVA
- `create_fichas_productos_table`: Pivot para consumos
- `create_ajustes_table`: Configuración
- `create_facturas_mesa_table`: Sistema de facturación
- `add_fiscal_fields_to_sitios_table`: CIF, dirección y teléfono para sitios
- `create_albaranes_site_table`: Sistema de albaranes por sitio
- `create_albaran_lineas_site_table`: Líneas de albaranes con FK a productos.uuid

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Tests específicos
php artisan test --filter=FichasTest
php artisan test --filter=MesasTest

# Con coverage
php artisan test --coverage
```

## 📦 Despliegue

### Requisitos del Servidor

- PHP >= 8.1 con extensiones requeridas
- MySQL >= 8.0
- Apache con mod_rewrite o Nginx
- Composer
- SSL/TLS (certificado HTTPS)

### Pasos de Despliegue

1. **Subir archivos al servidor** (excluir `node_modules`, `.env`, `storage/app`)

2. **Clonar repositorio o FTP**

```bash
git clone https://github.com/devit-gm/agsuitepro.git /var/www/agsuitepro
cd /var/www/agsuitepro
```

3. **Configurar `.env` en producción**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_DATABASE=tu_base_datos_produccion
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña_segura
```

4. **Instalar dependencias**

```bash
composer install --optimize-autoloader --no-dev
```

5. **Compilar assets**

```bash
npm ci
npm run build
```

6. **Optimizar aplicación**

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

7. **Permisos**

```bash
chown -R www-data:www-data /var/www/agsuitepro
chmod -R 755 /var/www/agsuitepro
chmod -R 775 storage bootstrap/cache
```

8. **Configurar Virtual Host (Apache)**

```apache
<VirtualHost *:80>
    ServerName tudominio.com
    DocumentRoot /var/www/agsuitepro/public

    <Directory /var/www/agsuitepro/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/agsuitepro_error.log
    CustomLog ${APACHE_LOG_DIR}/agsuitepro_access.log combined
</VirtualHost>
```

9. **Certificado SSL con Let's Encrypt**

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d tudominio.com
```

10. **Cron para tareas programadas**

```bash
crontab -e
# Añadir:
* * * * * cd /var/www/agsuitepro && php artisan schedule:run >> /dev/null 2>&1
```

## 🤝 Contribución

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

### Estándares de Código

- Seguir PSR-12 para PHP
- Documentar funciones con PHPDoc
- Escribir tests para nuevas funcionalidades
- Usar nombres descriptivos en inglés para código, español para UI

## 🐛 Reporte de Bugs

Si encuentras un bug, por favor abre un issue en GitHub con:

- Descripción detallada del problema
- Pasos para reproducir
- Comportamiento esperado vs. actual
- Capturas de pantalla (si aplica)
- Versión de Laravel, PHP y navegador

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## 👥 Autores

- **David Gómez** - *Desarrollo principal* - [@devit-gm](https://github.com/devit-gm)

## 🙏 Agradecimientos

- Laravel Framework por su elegante sintaxis
- Bootstrap por el sistema de diseño
- Spatie por Laravel Permission
- Twilio por las APIs de comunicación
- Comunidad open source por inspiración y soporte

## 📞 Soporte

Para preguntas y soporte:

- **Email**: davgomruiz@gmail.com
- **GitHub Issues**: https://github.com/devit-gm/agsuitepro/issues
- **Documentación**: https://github.com/devit-gm/agsuitepro/wiki

---

**Desarrollado con ❤️ en España** 

**Versión**: 2025 Diciembre con Sistema de Proveedores y Modo Offline


## 📝 Novedades recientes

### Gestión de Proveedores (Diciembre 2025)

Sistema completo de gestión de proveedores integrado con albaranes de compra.

#### Características principales:
- **Ficha completa de proveedor**:
  - Datos fiscales: Nombre, CIF, dirección completa
  - Información de contacto: Email, teléfono, persona de contacto
  - Condiciones comerciales: Días de pago, descuento general, condiciones de pago
  - Datos bancarios: Cuenta bancaria para pagos
  - Notas internas para seguimiento
  - Estado: Activo/Inactivo

- **Integración con albaranes**:
  - Selector de proveedor en albaranes (reemplaza campos manuales)
  - Campos eliminados: `proveedor`, `nif`, `contacto` (ahora se obtienen de la relación)
  - Nuevos campos: `proveedor_id` (FK), `numero_albaran`, `fecha_albaran`
  - Historial completo de compras por proveedor
  - Estadísticas: Total albaranes, compras totales, compra media

- **Funcionalidades**:
  - CRUD completo con validación
  - Búsqueda avanzada por nombre, CIF, email o teléfono
  - Filtros por estado (activo/inactivo)
  - Vista detallada con historial de albaranes
  - Gráficos de evolución de compras (últimos 12 meses)
  - Soft delete para mantener histórico
  - UUID único por proveedor

#### Arquitectura:
- Base de datos por sitio: `protected $connection = 'site'`
- Modelo: `App\Models\Proveedor`
- Controlador: `App\Http\Controllers\ProveedoresController`
- Rutas: `/proveedores` con resource completo
- Validación: `'proveedor_id' => 'required|exists:site.proveedores,id'`

#### Migración desde datos antiguos:
Script SQL incluido en `database/scripts/migrar_proveedores_existentes.sql`:
1. Crea proveedores únicos desde albaranes existentes
2. Asigna `proveedor_id` a cada albarán
3. Muestra estadísticas de migración

### Modo Offline PWA (Diciembre 2025)

Sistema avanzado de funcionamiento offline para garantizar operatividad sin conexión.

#### Service Worker v3:
- **Estrategias de caché**:
  - Cache-First: Assets estáticos (CSS, JS, imágenes, fuentes)
  - Network-First: Vistas HTML y datos dinámicos
  - Network-Only: Peticiones API críticas

- **Gestión inteligente de caché**:
  - Cache estático (v1): Assets del frontend
  - Cache dinámico (v1): Páginas visitadas
  - Cache API (v1): Respuestas de endpoints
  - Límite de 50 elementos por cache dinámico
  - Limpieza automática de caches antiguas

- **Sincronización en background**:
  - Cola de operaciones pendientes en IndexedDB
  - Background Sync API para reintento automático
  - Sincronización manual forzada disponible
  - Detección de conexión en tiempo real

#### Offline Manager:
- **Indicador visual de estado**:
  - Verde: Online y sincronizado
  - Amarillo: Offline (operaciones en cola)
  - Rojo: Error de sincronización

- **Notificaciones toast**:
  - Cambio de estado online/offline
  - Operaciones guardadas en cola
  - Sincronización completada
  - Errores de sincronización

- **API pública**:
  ```javascript
  OfflineManager.forceSyncNow()  // Forzar sincronización
  OfflineManager.getStatus()     // Obtener estado actual
  ```

#### Funcionalidades offline:
- Navegación completa por la aplicación
- Visualización de datos cacheados
- Guardado de operaciones en cola
- Sincronización automática al recuperar conexión
- Persistencia de cambios pendientes

#### Configuración:
- Service Worker: `public/sw.js`
- Manager: `public/js/offline-manager.js`
- Registro en: `resources/views/layouts/app.blade.php`
- Manifest PWA: `public/__manifest.json`

### Módulo de Albaranes (Diciembre 2025)

- Sistema completo de gestión de albaranes de entrada/compras
- **Características principales**:
  - Creación de albaranes con múltiples líneas de productos
  - Integración con sistema de proveedores
  - Estados: Pendiente, Recibido, Facturado
  - Confirmación de recepción con actualización automática de stock
  - Asociación de productos mediante UUID (soporte multi-tenant)
  - Cálculo automático de subtotales e importes totales
  - Filtros por proveedor, estado y fechas
  - Generación de PDF con datos del proveedor
  - Interfaz responsive con footer buttons (icon-only)
- **Arquitectura**:
  - Base de datos por sitio (no en central)
  - Foreign keys: `albaranes.proveedor_id` → `proveedores.id` (SET NULL)
  - Foreign keys: `albaran_lineas.producto_id` → `productos.uuid` (CASCADE)
  - Modelos con conexión explícita: `protected $connection = 'site'`
  - Validación UUID: `'producto_id' => 'required|string|size:36'`
- **SQL para deployment**:
  ```sql
  CREATE TABLE proveedores (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    uuid char(36) NOT NULL UNIQUE,
    nombre varchar(255) NOT NULL,
    cif varchar(255) DEFAULT NULL,
    email varchar(255) DEFAULT NULL,
    telefono varchar(255) DEFAULT NULL,
    direccion text DEFAULT NULL,
    ciudad varchar(255) DEFAULT NULL,
    codigo_postal varchar(255) DEFAULT NULL,
    pais varchar(255) NOT NULL DEFAULT 'España',
    contacto_principal varchar(255) DEFAULT NULL,
    condiciones_pago text DEFAULT NULL,
    dias_pago int NOT NULL DEFAULT 30,
    cuenta_bancaria varchar(255) DEFAULT NULL,
    notas text DEFAULT NULL,
    activo tinyint(1) NOT NULL DEFAULT 1,
    descuento_general decimal(5,2) NOT NULL DEFAULT 0.00,
    created_at timestamp NULL,
    updated_at timestamp NULL,
    deleted_at timestamp NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  CREATE TABLE albaranes (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    proveedor_id bigint unsigned DEFAULT NULL,
    numero_albaran varchar(255) DEFAULT NULL,
    fecha_albaran date DEFAULT NULL,
    fecha date NOT NULL,
    estado enum('pendiente','recibido','facturado') NOT NULL DEFAULT 'pendiente',
    total decimal(10,2) NOT NULL DEFAULT 0.00,
    observaciones text DEFAULT NULL,
    usuario_id bigint unsigned DEFAULT NULL,
    fecha_recepcion datetime DEFAULT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    KEY idx_proveedor_id (proveedor_id),
    KEY idx_usuario_id (usuario_id),
    CONSTRAINT fk_albaranes_proveedor
      FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  CREATE TABLE albaran_lineas (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    albaran_id bigint unsigned NOT NULL,
    producto_id char(36) NOT NULL,
    cantidad decimal(10,2) NOT NULL,
    precio_coste decimal(10,2) NOT NULL,
    subtotal decimal(10,2) NOT NULL,
    created_at timestamp NULL DEFAULT NULL,
    updated_at timestamp NULL DEFAULT NULL,
    KEY idx_albaran_id (albaran_id),
    KEY idx_producto_id (producto_id),
    CONSTRAINT fk_albaran_lineas_albaran 
      FOREIGN KEY (albaran_id) REFERENCES albaranes(id) ON DELETE CASCADE,
    CONSTRAINT fk_albaran_lineas_producto 
      FOREIGN KEY (producto_id) REFERENCES productos(uuid) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  ```

### Vista de Ticket en Mesas (Diciembre 2025)

- Visualización del ticket completo directamente desde el grid de mesas
- **Características**:
  - Modal con listado de todos los productos y servicios de la mesa
  - Muestra cantidades, precios unitarios y subtotales
  - Cálculo de total general
  - Desglose de IVA si está configurado
  - Accesible desde cualquier mesa ocupada o cerrada
  - No requiere ser el camarero asignado para consultar

### Recordatorio unificado multi-tenant para reservas y eventos

- El sistema de recordatorios ahora es multi-tenant y configurable por sitio.
- Se ha unificado la lógica de notificación para reservas y eventos:
  - Dos campos de días de antelación: uno para reservas y otro para eventos (solo visible en modo fichas).
  - Un solo switch para activar/desactivar el envío de recordatorio por email y otro para notificación push (aplican a ambos tipos).
- El recordatorio de eventos solo se envía para fichas de tipo 4 (eventos).
- Los usuarios notificados para eventos se obtienen desde la base central, filtrando por el identificador del sitio.
- El campo de días de antelación para eventos no aparece ni se requiere en modo mesas.
- Si el campo no está presente en el formulario, el sistema asigna un valor por defecto para evitar errores.
- Se han añadido instrucciones SQL para añadir los nuevos campos a la tabla ajustes en cada base de datos de sitio:

```sql
ALTER TABLE ajustes ADD COLUMN recordatorio_reservas_dias INT NOT NULL DEFAULT 1 AFTER recordatorio_reservas_minutos;
ALTER TABLE ajustes ADD COLUMN limite_inscripcion_dias_eventos INT NOT NULL DEFAULT 1 AFTER facturar_ficha_automaticamente;
```

### Uso

Configura los recordatorios desde Ajustes > Recordatorios:

- Días de antelación para reservas
- Días de antelación para eventos (solo modo fichas)
- Enviar recordatorio por email (sí/no)
- Enviar recordatorio por notificación push (sí/no)

El comando `php artisan reservas:verificar-proximas` recorre todos los sitios no centrales y envía los recordatorios según la configuración de cada uno.

---


## 🆕 Novedades 2025.11 (Recordatorios y Cron Multi-sitio)

- 🔔 **Recordatorio de reservas configurable por días**: Ahora puedes configurar desde Ajustes cuántos días antes se envía el recordatorio de reservas.
- 📧 **Notificaciones de recordatorio**: Se envían tanto por email como por notificación push (Firebase) al usuario creador de la reserva.
- 🛠️ **Comando Artisan multi-sitio**: El comando `reservas:verificar-proximas` recorre automáticamente todos los sitios configurados y ejecuta la lógica de notificación para cada uno (multi-tenant real).
- 🌐 **URL segura para cron**: Puedes programar el cron en IONOS u otro hosting llamando a una URL protegida con token secreto, que ejecuta el comando para todos los sitios.
- 📝 **Instrucciones para cron en IONOS**: Añadidas recomendaciones y ejemplo de ruta segura para programar el cron en hostings compartidos.
- ⚠️ **Advertencias sobre .htaccess y URLs amigables**: Añadidas recomendaciones para evitar problemas con index.php en la URL y asegurar la redirección www/sin www.

### Configuración del recordatorio de reservas

1. Ve a **Ajustes > Recordatorio de reservas** y elige los días de antelación.
2. Activa/desactiva notificación por email y push según prefieras.
3. El sistema notificará automáticamente a los usuarios con reservas para el día configurado.

### Programar el cron en IONOS (o similar)

1. Añade en tu `.env`:
  ```
  CRON_SECRET=tu_token_secreto
  ```
2. La URL para el cron será:
  ```
  https://tudominio.com/cron/reservas-verificar/tu_token_secreto
  ```
3. Programa el cron en el panel de IONOS usando esa URL.

### Multi-tenant automático

El comando recorre todos los sitios (tabla `sitios`) y ejecuta la lógica de notificación para cada uno, usando la conexión y configuración correspondiente.

### .htaccess y URLs amigables

Asegúrate de que tu dominio apunte a la carpeta `public/` y que el archivo `.htaccess` sea el estándar de Laravel. Si tienes problemas con index.php en la URL o con www/sin www, revisa la sección de instalación y las recomendaciones del README.

---

### 📝 Changelog

#### v2025.12 - Módulo de Albaranes y Vista de Ticket en Mesas
- ✨ Sistema completo de gestión de albaranes de entrada
- ✨ Confirmación de recepción con actualización automática de stock
- ✨ Soporte para UUID en productos (CHAR(36))
- ✨ Arquitectura multi-tenant: albaranes por sitio
- ✨ Vista de ticket completa en grid de mesas
- ✨ Modal de ticket accesible desde cualquier mesa
- 🎨 Interfaz responsive con footer buttons (icon-only)
- 🐛 Corrección de validación para UUID (string|size:36)
- 🐛 Foreign keys correctas: producto_id → productos.uuid

#### v2025.11 - Recordatorio de Reservas y Cron Multi-sitio
- 🔔 Recordatorio de reservas configurable por días (Ajustes)
- 📧 Notificaciones de recordatorio por email y push (Firebase)
- 🛠️ Comando Artisan multi-sitio: recorre todos los sitios y ejecuta la lógica para cada uno
- 🌐 URL segura para cron programable en IONOS/hosting compartido
- 📝 Instrucciones y advertencias para cron multi-tenant y .htaccess

#### v2025.11 - Sistema de Facturación e IVA
- ✨ Sistema completo de facturación para mesas
- ✨ Cálculo automático de IVA desde precios PVP
- ✨ Desglose de IVA por tipo (0%, 4%, 10%, 21%)
- ✨ Generación de PDFs con diseño profesional
- ✨ Gestión de facturas emitidas con filtros
- ✨ Datos fiscales por sitio (CIF, dirección, teléfono)
- 🐛 Corrección de cálculos de IVA en informes
- 🐛 Protección de consultas en layout para sitio central
- 🎨 Interfaz responsive optimizada para móviles
- 🎨 Diseño mejorado de modales y formularios

#### v2025.11 - Modo Mesas y Control de Stock
- ✨ Grid visual de mesas con estados en tiempo real
- ✨ Gestión completa de mesas (abrir, cerrar, liberar)
- ✨ Control de stock automático
- ✨ Lectura de códigos de barras
- ✨ Panel de estadísticas para camareros
- 🎨 Optimización de imágenes con lazy loading y cache

