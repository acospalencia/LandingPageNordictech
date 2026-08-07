# Landing Page y Portal de Soporte NordicTech

Sitio corporativo y plataforma web de tickets desarrollada con HTML,
JavaScript y PHP para alojamiento compartido. Utiliza la base de datos de
WordPress mediante las credenciales de `wp-config.php`, pero no necesita
cargar WordPress para procesar las solicitudes.

Producción: `https://nordictech-corp.com/`

## Funcionalidades

- Landing corporativa con formulario de contacto.
- Registro, aprobación, inicio de sesión y recuperación de contraseña.
- Portal de clientes para crear, consultar y comentar tickets propios.
- Panel de técnicos para atender tickets asignados y registrar bitácoras.
- Panel administrativo para asignar técnicos, cambiar estados y administrar
  usuarios, roles y datos operativos.
- Notificaciones por correo para tickets, cambios de estado, recuperación de
  contraseña y comentarios del cliente.
- Descarga directa de la aplicación Android desde el panel técnico.

## Roles y rutas

| Rol | ID | Panel |
| --- | ---: | --- |
| Cliente | 1 | `/pages/PortalClientes.php` |
| Técnico | 2 | `/pages/Tecnicos.php` |
| Administrador | 3 | `/pages/Dashboard.php` |

Las páginas protegidas validan sesión y rol en PHP. `Login.php` dirige cada
sesión activa a su panel correspondiente para evitar ciclos de redirección.

## Tickets y comentarios

Los clientes consultan únicamente los tickets de su cuenta. Mientras un
ticket esté `Abierto` o `En Proceso`, pueden agregar comentarios de hasta
2000 caracteres. El servidor valida sesión, rol, propiedad y estado.

Cada comentario se agrega a la bitácora con este formato:

```text
[06/08/2026 03:45 PM] - Cliente: Nombre del cliente - Comentario
```

Después se notifica a `tickets@nordictech-corp.com` y, cuando existe, al
correo del técnico asignado mediante copia oculta.

## Envío de correo

Los correos se envían inmediatamente mediante `mail()` del hosting usando
`assets/php/correo.php`. Los flujos activos no insertan registros en
`cola_correos` ni dependen del antiguo cron.

Remitentes corporativos conservados:

- `no-reply@nordictech-corp.com`
- `soporte@nordictech-corp.com`

En operaciones transaccionales, si `mail()` rechaza el mensaje, la
actualización relacionada se revierte. `procesar_cola.php` se conserva como
componente legado; su cron debe permanecer desactivado en el hosting.

## Instalación

1. Selecciona PHP 8.0 o superior en el hosting.
2. Sube `index.html`, `pages`, `assets` y `database` al directorio público.
3. Verifica que `wp-config.php` sea accesible desde las rutas actuales.
4. Ejecuta las migraciones necesarias de `database/migrations` con phpMyAdmin.
5. Confirma que las sesiones PHP y `mail()` estén habilitados.
6. Prueba el acceso con una cuenta de cada rol.

No publiques `wp-config.php`, contraseñas ni respaldos de producción.

## Aplicación Android y caché

El panel técnico descarga:

```text
/assets/downloads/AsistenciaNordictech-corregida.apk?v=1.1
```

El nombre descargado se conserva como `AsistenciaNordictech-corregida.apk`.
Al reemplazar el APK, incrementa el parámetro (`v=1.2`, `v=1.3`, etc.) para
evitar que el navegador o CDN entregue una compilación anterior. La versión
debe coincidir con `ApplicationDisplayVersion` de la aplicación y con
`APP_VERSION` de la API.

## Estructura

```text
LandingPageNordictech/
├── index.html                  Landing corporativa
├── pages/                      Vistas por rol
├── assets/css/                 Estilos compartidos
├── assets/js/                  Lógica de interfaz
├── assets/php/                 Endpoints web y correo
├── assets/downloads/           APK publicado en producción
└── database/migrations/        Cambios de esquema
```

## Verificación antes de publicar

- Iniciar sesión como cliente, técnico y administrador.
- Comprobar que cada rol llegue únicamente a su panel.
- Crear un ticket y verificar el correo recibido por soporte.
- Comentar un ticket y confirmar la notificación al técnico asignado.
- Probar la recuperación de contraseña.
- Descargar el APK y verificar que sea la versión `1.1`.
- Confirmar que no se creen filas nuevas en `cola_correos`.

## Seguridad

- Mantén HTTPS activo y las cookies de sesión protegidas.
- Conserva las validaciones de rol y propiedad en los endpoints PHP.
- Escapa el contenido de usuario antes de mostrarlo en HTML o correos.
- No publiques credenciales, respaldos SQL ni ZIP con información sensible.
