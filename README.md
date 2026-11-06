# 🏥 CitaVital

### Sistema web para la gestión y seguimiento de citas médicas

**Proyecto de aula — Asignatura: Uso de Framework Laravel**

CitaVital es una aplicación web diseñada para facilitar la gestión y el seguimiento de citas médicas en una EPS o IPS.

La plataforma permite que los pacientes consulten la disponibilidad de los profesionales de salud, soliciten citas médicas y revisen su historial de atención. Además, incorpora un módulo administrativo para gestionar pacientes, médicos, especialidades, horarios, citas, inasistencias y estadísticas.

El proyecto fue desarrollado con fines académicos, aplicando los conceptos de enrutamiento, controladores, modelos, vistas y arquitectura MVC del framework Laravel.

---

## 📋 Tabla de contenido

1. Descripción del proyecto
2. Objetivos
3. Tecnologías utilizadas
4. Funcionalidades
5. Arquitectura del sistema
6. Estructura del proyecto
7. Requisitos previos
8. Instalación y configuración
9. Base de datos
10. Ejecución del proyecto
11. Usuarios de demostración
12. Pruebas automatizadas
13. Evidencias del sistema
14. Seguridad
15. Contexto académico

---

## 📖 1. Descripción del proyecto

CitaVital busca mejorar la organización de las citas médicas mediante una plataforma digital que centraliza la información de pacientes, médicos y horarios de atención.

El sistema cuenta con dos áreas principales:

**Portal del paciente:** permite consultar las especialidades y los médicos disponibles, reservar citas y realizar seguimiento a las solicitudes.

**Panel administrativo:** permite administrar los registros institucionales, configurar agendas médicas, gestionar citas, controlar inasistencias y visualizar indicadores estadísticos.

La plataforma está orientada a facilitar la interacción entre pacientes y personal administrativo, manteniendo una estructura organizada y un acceso diferenciado según el tipo de usuario.

---

## 2. Objetivos

### Objetivo general

Desarrollar una aplicación web que facilite la gestión, programación y seguimiento de citas médicas mediante una plataforma organizada, accesible y funcional.

### Objetivos específicos

- Implementar un sistema de acceso diferenciado para pacientes y administradores.
- Facilitar la consulta y reserva de citas médicas.
- Permitir la administración de pacientes, médicos y especialidades.
- Gestionar la disponibilidad y los horarios de atención.
- Controlar los estados de las citas médicas.
- Registrar y dar seguimiento a las inasistencias.
- Presentar estadísticas administrativas mediante información almacenada en la base de datos.
- Aplicar la arquitectura Modelo-Vista-Controlador mediante Laravel.

---

## 💻 3. Tecnologías utilizadas

| Tecnología | Versión de desarrollo | Función |
|---|---|---|
| PHP | 8.3 | Lenguaje del backend |
| Laravel | 13 | Framework principal |
| MySQL | 8.0 | Base de datos relacional |
| HTML5 | — | Estructura de las páginas |
| CSS3 | — | Estilos y diseño responsive |
| JavaScript | — | Interacciones del frontend |
| Blade | Laravel | Motor de plantillas |
| Vite | Según `package.json` | Gestión de recursos frontend |
| Node.js | 26 | Entorno de herramientas frontend |
| pnpm | 12 | Administración de paquetes |
| Composer | 2 | Dependencias PHP |
| Git | — | Control de versiones |

### Entorno de desarrollo

El proyecto fue desarrollado y probado localmente en Linux Mint.

La aplicación utiliza Laravel como framework principal, siguiendo una arquitectura monolítica con vistas Blade.

No requiere React, Vue ni un frontend desacoplado.

---

## ⚙️ 4. Funcionalidades

### Página principal

- Presentación institucional de CitaVital.
- Información sobre la plataforma.
- Acceso al portal de pacientes.
- Acceso al panel administrativo.
- Diseño adaptable a diferentes dispositivos.

### Portal del paciente

- Acceso demostrativo mediante número de cédula.
- Bienvenida personalizada.
- Consulta de especialidades médicas.
- Selección de médicos disponibles.
- Consulta de horarios de atención.
- Reserva de citas médicas.
- Visualización de próximas citas.
- Consulta del historial.
- Detalle de citas registradas.
- Cierre de sesión.

### Panel administrativo

**Gestión de pacientes**

- Registro de pacientes.
- Búsqueda y consulta.
- Edición de información.
- Activación y desactivación.
- Consulta del historial de citas.

**Gestión de médicos**

- Registro de profesionales.
- Asignación de especialidades.
- Edición de información.
- Activación y desactivación.

**Gestión de especialidades**

- Registro y edición.
- Consulta de especialidades.
- Activación y desactivación.

**Gestión de horarios**

- Configuración de disponibilidad semanal.
- Definición de horarios de atención.
- Duración de consultas.
- Descansos y bloqueos.
- Generación de turnos médicos.
- Control de horarios ocupados.

**Gestión de citas**

- Consulta de citas registradas.
- Búsqueda y filtros.
- Confirmación de asistencia.
- Registro de inasistencias.
- Cancelación de citas.
- Reprogramación.
- Consulta del historial de modificaciones.

**Seguimiento de inasistencias**

- Identificación de citas perdidas.
- Registro de llamadas.
- Seguimiento a pacientes ausentes.
- Observaciones administrativas.
- Control de estados de seguimiento.

**Estadísticas**

- Indicadores generales.
- Citas programadas y realizadas.
- Cancelaciones e inasistencias.
- Estadísticas por médico.
- Estadísticas por especialidad.
- Gráficos de barras.
- Filtros por períodos y fechas.

---

## 5. Arquitectura del sistema

CitaVital implementa la arquitectura **Modelo-Vista-Controlador (MVC)** proporcionada por Laravel.

### Modelo

Representa las entidades y relaciones de la base de datos.

Los modelos utilizan Eloquent ORM para gestionar la persistencia de la información en MySQL.

### Vista

Las interfaces están desarrolladas utilizando Blade, HTML, CSS y JavaScript.

Se emplean layouts maestros, componentes reutilizables y vistas específicas para los distintos módulos.

### Controlador

Los controladores reciben las solicitudes HTTP, coordinan las operaciones necesarias y envían la información correspondiente a las vistas.

### Servicios

La aplicación también utiliza servicios para centralizar reglas de negocio como:

- Disponibilidad de médicos.
- Reservas de citas.
- Seguimiento de inasistencias.
- Estadísticas administrativas.

### Flujo general

```text
Usuario
   |
   v
Rutas Laravel
   |
   v
Middleware y validaciones
   |
   v
Controladores
   |
   v
Servicios y modelos Eloquent
   |
   v
Base de datos MySQL
   |
   v
Vistas Blade
   |
   v
HTML + CSS + JavaScript
```

---

## 6. Estructura del proyecto

```text
citavital/
|
├── app/
│   ├── Enums/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Administrativo/
│   │   │   └── Paciente/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   └── Services/
│
├── bootstrap/
│
├── config/
│
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
│
├── public/
│   └── images/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── layouts/
│       ├── components/
│       ├── administrativo/
│       └── paciente/
│
├── routes/
│   └── web.php
│
├── tests/
│
├── .env.example
├── artisan
├── composer.json
├── composer.lock
├── package.json
├── pnpm-lock.yaml
├── vite.config.js
└── README.md
```

La organización separa la presentación, la lógica de negocio, el acceso a los datos y las pruebas.

---

##  7. Requisitos previos

Para ejecutar CitaVital localmente se necesita:

- PHP 8.3 o una versión compatible con Laravel 13.
- Composer.
- MySQL 8.
- Node.js y pnpm compatibles con el proyecto.
- Git.
- Extensiones PHP necesarias para Laravel y MySQL, especialmente `pdo_mysql`.

También es necesario contar con permisos para crear una base de datos MySQL y ejecutar las migraciones.

Las versiones exactas utilizadas se encuentran reflejadas en los archivos de dependencias.

---

## 8. Instalación y configuración

### 8.1. Clonar el repositorio

```bash
git clone https://github.com/TU_USUARIO/citavital.git
```

Entrar en el proyecto:

```bash
cd citavital
```

Reemplazar `TU_USUARIO` por el usuario correspondiente de GitHub.

### 8.2. Instalar dependencias PHP

```bash
composer install
```

### 8.3. Instalar dependencias frontend

```bash
pnpm install --frozen-lockfile
```

### 8.4. Preparar el entorno

Copiar el archivo de configuración:

```bash
cp .env.example .env
```

Generar la clave de Laravel:

```bash
php artisan key:generate
```

### 8.5. Configurar MySQL

Crear una base de datos:

```sql
CREATE DATABASE citavital
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Crear un usuario MySQL para el proyecto y concederle permisos sobre esa base.

En `.env`, configurar:

```dotenv
APP_NAME=CitaVital
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

APP_TIMEZONE=America/Bogota

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=citavital
DB_USERNAME=citavital_user
DB_PASSWORD=TU_CONTRASEÑA_MYSQL
```

Usar las credenciales propias del entorno local.

**No publicar el archivo `.env` ni compartir sus contraseñas.**

### 8.6. Ejecutar las migraciones

```bash
php artisan migrate
```

El sistema utiliza restricciones de integridad y triggers MySQL para prevenir turnos médicos superpuestos.

En entornos donde MySQL tiene habilitado el registro binario, la instalación de triggers puede requerir una configuración administrativa adicional.

Si MySQL devuelve el error 1419, revisar los permisos y la configuración del servidor antes de continuar.

### 8.7. Cargar datos ficticios

Para disponer de información de demostración:

```bash
php artisan db:seed --class=DemoDataSeeder
```

Esto carga información ficticia de pacientes, médicos, especialidades, horarios y citas.

### 8.8. Crear el administrador

Configurar una contraseña privada en `.env`:

```dotenv
CITAVITAL_ADMIN_PASSWORD=UNA_CONTRASEÑA_SEGURA
```

Ejecutar:

```bash
php artisan db:seed --class=AdminUserSeeder
```

La contraseña se almacena mediante un hash seguro.

### 8.9. Preparar los recursos frontend

```bash
pnpm build
```

Limpiar la configuración en caché:

```bash
php artisan optimize:clear
```

El proyecto estará preparado para ejecutarse localmente.

---

## ▶️ 9. Ejecución del proyecto

Iniciar el servidor de Laravel:

```bash
php artisan serve
```

La aplicación estará disponible normalmente en:

```text
http://127.0.0.1:8000
```

Para desarrollar con recarga de recursos frontend, abrir otra terminal y ejecutar:

```bash
pnpm dev
```

### Rutas principales

| Ruta | Descripción |
|---|---|
| `/` | Página principal |
| `/quienes-somos` | Información institucional |
| `/paciente/login` | Acceso del paciente |
| `/paciente/dashboard` | Panel del paciente |
| `/paciente/citas/solicitar` | Solicitud de citas |
| `/administrativo/login` | Acceso administrativo |
| `/administrativo/dashboard` | Dashboard administrativo |
| `/administrativo/pacientes` | Gestión de pacientes |
| `/administrativo/medicos` | Gestión de médicos |
| `/administrativo/especialidades` | Gestión de especialidades |
| `/administrativo/horarios` | Disponibilidad médica |
| `/administrativo/citas` | Gestión de citas |
| `/administrativo/seguimientos` | Seguimiento de inasistencias |
| `/administrativo/estadisticas` | Estadísticas |

Las rutas privadas requieren autenticación.

---

## 👥 10. Usuarios de demostración

### Administrador

El seeder permite crear un administrador utilizando el correo:

```text
administrativo.demo@uniajs.edu.co
```

La contraseña se configura mediante la variable:

```text
CITAVITAL_ADMIN_PASSWORD
```

No se incluye ninguna contraseña en este repositorio.

### Paciente

Para demostraciones locales, se dispone de pacientes ficticios.

Ejemplo de cédula:

```text
10000001
```

El acceso mediante cédula está limitado al modo demostrativo en entorno local.

**Importante:** este mecanismo no es adecuado para autenticar pacientes reales.

---

## 🗄️ 11. Base de datos

El sistema utiliza MySQL como motor de base de datos.

Entre sus principales entidades se encuentran:

| Tabla | Descripción |
|---|---|
| `users` | Usuarios administrativos |
| `patients` | Información de pacientes |
| `doctors` | Médicos |
| `specialties` | Especialidades |
| `doctor_specialty` | Relación entre médicos y especialidades |
| `availabilities` | Disponibilidad médica |
| `availability_blocks` | Bloqueos de horarios |
| `appointment_slots` | Turnos médicos |
| `appointments` | Citas registradas |
| `appointment_follow_ups` | Seguimientos |
| `administrative_audits` | Historial de operaciones |

La aplicación cuenta con claves foráneas, índices, transacciones y restricciones para proteger la consistencia de los datos.

Los triggers MySQL complementan las validaciones de Laravel para evitar que se registren turnos superpuestos para un mismo médico.

---

## 🧪 12. Pruebas automatizadas

CitaVital utiliza el sistema de pruebas de Laravel y PHPUnit.

Las pruebas verifican:

- Autenticación.
- Autorización.
- Validación de formularios.
- Registro y actualización de pacientes.
- Gestión de médicos y especialidades.
- Disponibilidad médica.
- Prevención de reservas duplicadas.
- Reserva y cancelación de citas.
- Reprogramaciones.
- Seguimiento de inasistencias.
- Estadísticas.
- Protección CSRF.
- Aislamiento entre pacientes.
- Comportamiento de sesiones.

### Base de datos de pruebas

Se debe utilizar una base independiente:

```text
citavital_testing
```

Esta base debe crearse antes de ejecutar las pruebas y configurarse en `phpunit.xml`.

**Nunca ejecutar la suite destructiva sobre la base principal `citavital`.**

### Ejecutar las pruebas

```bash
composer test
```

### Verificar formato de código

```bash
vendor/bin/pint --test
```

### Compilar el frontend

```bash
pnpm build
```

### Resultado de la última verificación del proyecto

La última ejecución reportada durante el desarrollo obtuvo:

- **181 pruebas aprobadas.**
- **1107 aserciones.**
- Verificación de formato correcta.
- Compilación de frontend correcta.

Estos resultados corresponden al entorno local de desarrollo y pueden variar si se modifica el proyecto.

---

## 13. Evidencias del sistema

Las principales pantallas de CitaVital son:

- Página de inicio.
- Portal administrativo.
- Gestión de pacientes.
- Gestión de médicos.
- Gestión de especialidades.
- Configuración de horarios.
- Portal del paciente.
- Reserva de citas.
- Gestión y seguimiento de citas.
- Estadísticas.

Las capturas de pantalla pueden incorporarse a la documentación académica del proyecto.

---

## 14. Seguridad y limitaciones

CitaVital incorpora:

- Validaciones del servidor.
- Autenticación administrativa.
- Protección de rutas mediante middleware.
- Protección CSRF.
- Hash de contraseñas.
- Limitación de intentos de inicio de sesión.
- Sesiones de pacientes y administradores.
- Transacciones MySQL.
- Restricciones contra reservas duplicadas.
- Control de acceso a los registros personales.

### Limitación importante

CitaVital es un **prototipo académico**.

El acceso del paciente mediante número de cédula está diseñado exclusivamente para demostraciones locales con información ficticia.

No debe utilizarse para almacenar o gestionar información médica real sin implementar mecanismos adicionales de autenticación, protección de datos, seguridad y cumplimiento normativo.

---

## 15. Contexto académico

**Proyecto:** CitaVital — Sistema web para la gestión y seguimiento de citas médicas.

**Asignatura:** Uso de Framework Laravel.

**Tipo:** Proyecto de aula.

**Propósito académico:**

Aplicar los conceptos fundamentales del desarrollo de aplicaciones web mediante Laravel, incluyendo:

- Enrutamiento.
- Controladores.
- Arquitectura MVC.
- Modelos Eloquent.
- Migraciones.
- Vistas Blade.
- Componentes reutilizables.
- Validación de formularios.
- Autenticación y autorización.
- Integración con MySQL.
- Pruebas de funcionamiento.

El desarrollo permite demostrar la aplicación práctica de estos conceptos en un sistema de gestión de citas médicas.

---

## Licencia

Proyecto desarrollado con fines académicos.

La publicación del código no implica autorización para utilizar datos reales de pacientes ni para desplegar el prototipo como sistema clínico de producción.