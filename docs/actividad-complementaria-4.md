# Actividad Complementaria 4 — Evidencias CRUD de CitaVital

> Documento heredado de la revisión del repositorio original. Sus hashes, estado inicial y referencias de publicación corresponden a ese repositorio. Para esta copia independiente, consulta [Procedencia de CitaVital 2.0](procedencia.md).

## 1. Alcance y estado del repositorio

Revisión de código, rutas e historial para **Operaciones CRUD Completas, Validación de Formularios e Integración Eloquent-Vista**. Las evidencias describen implementaciones existentes; este documento no incorpora funcionalidades nuevas.

- Repositorio: <https://github.com/daveatencia1012-a11y/cita_vital-seguimiento-de-citas-m-dicas>.
- Rama: `main`. Estado inicial: árbol de trabajo e índice limpios.
- Raíz Git comprobada: `/home/joseph/Dave/Proyectos/electiva ii proyect/citavital`. No se trabajó sobre el repositorio superior.
- `c392efe`: `feat: implementar servicios de reservas y disponibilidad medica`. Ya contiene modelos, controladores, servicios, Form Requests, vistas, rutas y pruebas de los seis módulos.
- `62d61ef`: `docs: actualizar README de CitaVital`. Modifica únicamente el README; era HEAD local y remoto al iniciar la revisión.

Ambos commits se conservan. No se dividen ni se vuelven a atribuir sus implementaciones a commits nuevos. La incorporación real de esta revisión es este documento.

## 2. Tabla de entidades y operaciones reales

Los modelos se encuentran en [`app/Models`](../app/Models). Los controladores administrativos están en [`app/Http/Controllers/Administrativo`](../app/Http/Controllers/Administrativo).

| Entidad / modelo | CREATE | READ | UPDATE | Baja / DELETE |
| --- | --- | --- | --- | --- |
| Pacientes / `Patient` | `PacienteController::store`, `Patient::query()->create()` | `index`, `show`, `edit`; búsqueda, `paginate()`, relación `appointments()->with()` | `update`: `fill($request->validated())` y `save()` por PUT | `toggleState`: PATCH, invierte `is_active` y ejecuta `save()`. No hay DELETE físico |
| Médicos / `Doctor` | `MedicoController::store`, `create()` y `specialties()->sync()` dentro de una transacción | `index`, `edit`; `with()`, `withCount()`, filtros y `paginate()` | `update`: `update()` y `sync()`, con transacción y bloqueo | `toggleState`: PATCH y `save()` de `is_active`. No hay DELETE físico |
| Especialidades / `Specialty` | `EspecialidadController::store`, `Specialty::query()->create()` | `index`, `edit`; `withCount()`, filtros y `paginate()` | `update`: `fill()` y `save()` por PUT | `toggleState`: PATCH y `save()` de `is_active`. No hay DELETE físico |
| Disponibilidades / `Availability` | `DisponibilidadController::store` → `AvailabilityService::createAvailability` → `$doctor->availabilities()->create()` | `HorarioController::index`: `Availability::query()->get()` con filtros; `DisponibilidadController::edit` carga el médico | `update` → `updateAvailability` → `update()` por PUT; `toggleState` → `setAvailabilityActive` por PATCH | `destroy` → `deleteAvailability` → `delete()` físico, condicionado a conservar cobertura de citas futuras |
| Bloqueos / `AvailabilityBlock` | `BloqueoController::store` → `AvailabilityService::createBlock` → `$doctor->availabilityBlocks()->create()` | `HorarioController::index`: consultas `AvailabilityBlock::query()` para listado y agenda | Solo estado: `toggleState` → `setBlockActive` → `update(['is_active' => ...])` por PATCH | `destroy` → `deleteBlock` → `delete()` físico |
| Citas / `Appointment` | `Paciente\CitaController::store` → `AppointmentService::book` → `Appointment::query()->create()` | Controladores de citas de paciente y administrativo: `index`, `show`; paciente añade `history`; relaciones, filtros y paginación | PATCH para asistencia, inasistencia y cancelación; reprogramación por POST crea una nueva cita vinculada y actualiza la original | Cancelación lógica por estado; no hay ruta DELETE ni borrado físico de citas |

Persistencia delegada: [`AvailabilityService.php`](../app/Services/AvailabilityService.php) y [`AppointmentService.php`](../app/Services/AppointmentService.php). Usan transacciones y bloqueos para las operaciones que coordinan agenda y reservas. `deleteBlock()` ejecuta directamente `delete()`.

## 3. Rutas, métodos HTTP y controladores

Fuente: [`routes/web.php`](../routes/web.php). Se contrastó con `php artisan route:list --except-vendor`, que terminó correctamente y mostró 56 rutas de la aplicación.

### Escritura administrativa

En esta tabla las URI llevan el prefijo **`/administrativo`** y los nombres el prefijo **`administrativo.`**. Los controladores pertenecen a `App\Http\Controllers\Administrativo`.

| HTTP | URI relativa | Nombre relativo | Controlador y método |
| --- | --- | --- | --- |
| POST | `/pacientes` | `pacientes.store` | `PacienteController::store` |
| PUT | `/pacientes/{patient}` | `pacientes.update` | `PacienteController::update` |
| PATCH | `/pacientes/{patient}/estado` | `pacientes.estado` | `PacienteController::toggleState` |
| POST | `/medicos` | `medicos.store` | `MedicoController::store` |
| PUT | `/medicos/{doctor}` | `medicos.update` | `MedicoController::update` |
| PATCH | `/medicos/{doctor}/estado` | `medicos.estado` | `MedicoController::toggleState` |
| POST | `/especialidades` | `especialidades.store` | `EspecialidadController::store` |
| PUT | `/especialidades/{specialty}` | `especialidades.update` | `EspecialidadController::update` |
| PATCH | `/especialidades/{specialty}/estado` | `especialidades.estado` | `EspecialidadController::toggleState` |
| POST | `/horarios/disponibilidades` | `horarios.disponibilidades.store` | `DisponibilidadController::store` |
| PUT | `/horarios/disponibilidades/{availability}` | `horarios.disponibilidades.update` | `DisponibilidadController::update` |
| PATCH | `/horarios/disponibilidades/{availability}/estado` | `horarios.disponibilidades.estado` | `DisponibilidadController::toggleState` |
| DELETE | `/horarios/disponibilidades/{availability}` | `horarios.disponibilidades.destroy` | `DisponibilidadController::destroy` |
| POST | `/horarios/bloqueos` | `horarios.bloqueos.store` | `BloqueoController::store` |
| PATCH | `/horarios/bloqueos/{block}/estado` | `horarios.bloqueos.estado` | `BloqueoController::toggleState` |
| DELETE | `/horarios/bloqueos/{block}` | `horarios.bloqueos.destroy` | `BloqueoController::destroy` |
| POST | `/horarios/generar` | `horarios.generar` | `HorarioController::generate` |
| PATCH | `/citas/{appointment}/asistencia` | `citas.asistencia` | `CitaController::complete` |
| PATCH | `/citas/{appointment}/inasistencia` | `citas.inasistencia` | `CitaController::missed` |
| PATCH | `/citas/{appointment}/cancelar` | `citas.cancelar` | `CitaController::cancel` |
| POST | `/citas/{appointment}/disponibilidad` | `citas.disponibilidad` | `CitaController::availability` |
| POST | `/citas/{appointment}/reprogramar` | `citas.reprogramar` | `CitaController::reschedule` |

El alta de citas es **POST `/paciente/citas`**, nombre **`paciente.citas.store`**, controlador [`App\Http\Controllers\Paciente\CitaController::store`](../app/Http/Controllers/Paciente/CitaController.php).

### Lectura y formularios Blade

Todas las rutas de esta tabla son GET (también admiten HEAD). Los nombres administrativos llevan el prefijo `administrativo.`. Los archivos Blade se ubican bajo [`resources/views`](../resources/views).

| Entidad | Ruta nombrada → método | Vistas reales |
| --- | --- | --- |
| Pacientes | `pacientes.index/create/show/edit` → métodos homónimos de `PacienteController` | `administrativo/pacientes/{index,create,show,edit}.blade.php`; `partials/formulario.blade.php` |
| Médicos | `medicos.index/create/edit` → métodos homónimos de `MedicoController` | `administrativo/medicos/{index,create,edit}.blade.php`; `partials/formulario.blade.php` |
| Especialidades | `especialidades.index/create/edit` → métodos homónimos de `EspecialidadController` | `administrativo/especialidades/{index,create,edit}.blade.php`; `partials/formulario.blade.php` |
| Disponibilidades | `horarios.index` → `HorarioController::index`; `horarios.disponibilidades.edit` → `DisponibilidadController::edit` | `administrativo/horarios/{index,edit}.blade.php`; `partials/campos-disponibilidad.blade.php` |
| Bloqueos | `horarios.index` → `HorarioController::index` | `administrativo/horarios/index.blade.php`, incluye listado y formulario de alta |
| Citas administrativas | `citas.index/show/edit` → `CitaController::index/show/edit` | `administrativo/citas/{index,show,reprogramar}.blade.php` |
| Citas del paciente | `paciente.citas.index/solicitar/historial/show` → `Paciente\CitaController::index/create/history/show` (nombres completos) | `paciente/citas/{index,solicitar,historial,show}.blade.php` |

Las llaves en las rutas de archivos de la tabla abrevian varios archivos, no son nombres literales. No existen páginas independientes `show` para médicos o especialidades: la consulta se ofrece en listados y edición.

## 4. Form Requests y validaciones

Fuentes: [`app/Http/Requests/Administrativo`](../app/Http/Requests/Administrativo) y [`app/Http/Requests/Paciente/StoreCitaRequest.php`](../app/Http/Requests/Paciente/StoreCitaRequest.php).

| Operación | Form Requests | Reglas verificadas |
| --- | --- | --- |
| Alta y edición de pacientes | `StorePacienteRequest`, `UpdatePacienteRequest` | Cédula requerida de 8 o 10 dígitos, única; fecha de nacimiento no futura; seguro del enum; teléfono principal de 10 dígitos comenzando por 3 |
| Alta y edición de médicos | `StoreMedicoRequest`, `UpdateMedicoRequest` | Código requerido y único; nombres y apellidos hasta 100 caracteres; al menos una especialidad; IDs existentes y sin repetición |
| Alta y edición de especialidades | `StoreEspecialidadRequest`, `UpdateEspecialidadRequest` | Nombre requerido, único y máximo 100; descripción opcional hasta 2000 |
| Alta y edición de disponibilidades | `StoreDisponibilidadRequest`, `UpdateDisponibilidadRequest` | Día entre 1 y 7, horas `H:i`, duración entre 5 y 240 minutos; en alta exige médico activo existente |
| Alta de bloqueos | `StoreBloqueoRequest` | Médico activo; tipo permitido; motivo hasta 150 caracteres; fecha final posterior al inicio |
| Reserva del paciente | `Paciente\StoreCitaRequest` | Especialidad y médico activos existentes; turno existente; fecha `Y-m-d` no pasada; paciente autenticado |
| Consulta y operaciones de citas | `ListarCitasRequest`, `OperacionCitaRequest`, `ReprogramarCitaRequest` | Filtros de listado; notas opcionales hasta 2000 caracteres; fecha de reprogramación dentro de los próximos 90 días; turno requerido al confirmar |
| Generación de turnos | `GenerarTurnosRequest` | Validación del médico y rango solicitados |

Ejemplos exactos de reglas existentes:

```php
// StorePacienteRequest::rules()
'birth_date' => ['required', 'date', 'before_or_equal:today'],
'primary_phone' => ['required', 'string', 'regex:/^3\d{9}$/'],

// UpdatePacienteRequest::rules(): ignora el modelo obtenido de la ruta.
Rule::unique('patients', 'document_number')->ignore($paciente),

// StoreBloqueoRequest::rules()
'ends_at' => ['required', 'date', 'after:starts_at'],
```

Los Requests incluyen mensajes, por ejemplo: «Ya existe un paciente registrado con esta cédula.» y «La fecha y hora final debe ser posterior al inicio.». Los servicios añaden validaciones de negocio mediante `ValidationException::withMessages()`: solapamientos, cobertura de citas, disponibilidad real del turno y transiciones de estado.

Los métodos `toggleState` y `destroy` de catálogos/horarios no reciben un Form Request específico: operan sobre modelos resueltos por la ruta, bajo `auth:web` y `admin`, sin formulario de edición de campos. Los servicios aplican las restricciones de agenda. No se afirma que cada endpoint tenga un Request dedicado.

## 5. CSRF, métodos simulados, errores y recuperación de datos

- Los formularios de escritura auditados incluyen `@csrf`.
- Los parciales `administrativo/pacientes/partials/formulario.blade.php`, `administrativo/medicos/partials/formulario.blade.php` y `administrativo/especialidades/partials/formulario.blade.php` usan POST y añaden `@method('PUT')` al editar.
- `administrativo/horarios/edit.blade.php` usa `@method('PUT')` para guardar cambios y `@method('DELETE')` para eliminar.
- `administrativo/horarios/index.blade.php` contiene `@method('DELETE')` para franjas y bloqueos, y `@method('PATCH')` para sus estados.
- Los listados de pacientes, médicos y especialidades y el detalle de citas usan `@method('PATCH')` en cambios de estado.
- Los parciales de formularios muestran errores con `@error` y recuperan valores con `old()`; por ejemplo, `old('document_number', $paciente?->document_number)` y `old('specialties', $medico?->specialties->pluck('id')->all() ?? [])`.
- `administrativo/horarios/partials/campos-disponibilidad.blade.php` recupera día, horas, duración y estado; el alta de bloqueos recupera tipo, motivo e intervalo con `old()`.
- [`administrativo/partials/alertas.blade.php`](../resources/views/administrativo/partials/alertas.blade.php) y [`paciente/partials/alertas.blade.php`](../resources/views/paciente/partials/alertas.blade.php) muestran `$errors->all()` y mensajes de sesión.

**Alcance real de la recuperación:** el asistente `paciente/citas/solicitar.blade.php` reconstruye sus selecciones desde parámetros GET. `Paciente\CitaController::backToWizard()` redirige a `paciente.citas.solicitar` con esos parámetros y llama a `withErrors()->withInput()`. La vista no utiliza `old()` directamente. En reprogramación se usa `old('notes')`, pero no se recuperan todos los campos mediante ese helper. Por tanto, hay evidencia de `old()` en los formularios de gestión, no un uso uniforme en todos los flujos.

## 6. Redirecciones, Flash e integración Eloquent–Blade

Ejemplo existente en [`PacienteController::store`](../app/Http/Controllers/Administrativo/PacienteController.php):

```php
$paciente = Patient::query()->create([
    ...$request->validated(),
    'is_active' => true,
]);

return redirect()
    ->route('administrativo.pacientes.show', $paciente)
    ->with('status', 'Paciente registrado correctamente.');
```

El detalle consulta `$patient->appointments()->with(...)`, pagina el resultado y lo entrega a `administrativo.pacientes.show`. La vista representa los modelos y sus relaciones. El mismo circuito se observa en los demás módulos: ruta → Request/controlador → Eloquent o servicio → vista Blade o redirección.

Otros mensajes reales:

- `MedicoController::update`: «Médico actualizado correctamente.», ruta `administrativo.medicos.index`.
- `EspecialidadController::store`: «Especialidad registrada correctamente.», ruta `administrativo.especialidades.index`.
- `DisponibilidadController::destroy`: «Franja de disponibilidad eliminada correctamente.», ruta `administrativo.horarios.index` conservando el médico seleccionado.
- `BloqueoController::destroy`: «Bloqueo eliminado correctamente.», misma ruta de horarios.
- `Administrativo\CitaController::cancel`: «Cita cancelada. Se conserva el historial.».

Las altas, ediciones y eliminaciones físicas utilizan rutas nombradas. Los cambios de estado y asistencia/cancelación de citas utilizan `back()->with('status', ...)`. La generación de disponibilidad para reprogramar redirige a una ruta nombrada, pero no agrega Flash de éxito. No se presenta el uso de rutas nombradas y Flash como universal a cada acción auxiliar.

## 7. Desactivación lógica y conservación de historiales

`Patient`, `Doctor` y `Specialty` conservan su fila y relaciones al cambiar `is_active`. Esto es **desactivación lógica**, no `delete()` ni el mecanismo `SoftDeletes`/`deleted_at` de Laravel. Médicos y especialidades pueden mostrar un Flash `warning` si conservan citas futuras; esas citas no se cancelan automáticamente.

Disponibilidades y bloqueos permiten tanto activación/desactivación como eliminación física. Antes de eliminar una disponibilidad activa, `AvailabilityService::deleteAvailability()` comprueba que el cambio preserve la cobertura de citas futuras.

Las citas se cancelan actualizando su estado, fecha y motivo de cancelación, liberando su vínculo con el turno y conservando la fila. Reprogramar crea una nueva cita con `rescheduled_from_id`, actualiza la original y registra auditoría; una cita originalmente perdida conserva ese estado. No hay eliminación física de historiales de citas.

## 8. Límites observados y evaluación académica

La revisión encuentra evidencia de las ocho categorías solicitadas: POST de creación, READ Eloquent, PUT/PATCH de actualización, DELETE cuando corresponde, Form Requests, errores y `old()`, redirecciones/Flash e integración modelo–controlador–Blade.

No obstante, **CRUD no significa aquí cuatro endpoints uniformes por cada entidad**:

1. Bloqueos solo tienen UPDATE de estado; no existen formulario ni endpoint para editar sus fechas o motivo. Si la rúbrica exige editar esos campos, esa parte queda pendiente; no se implementó una ampliación del módulo durante esta auditoría.
2. Pacientes, médicos y especialidades usan desactivación; citas usan cancelación. No deben presentarse como DELETE físico.
3. La reprogramación usa POST porque genera otro registro; las demás transiciones de citas usan PATCH. No existe PUT genérico de citas.
4. No todas las redirecciones son por nombre ni todos los formularios recuperan todos sus campos con `old()`, como se explica en las secciones anteriores.

No se identificó una corrección indispensable para evidenciar las categorías generales de la actividad. El cumplimiento se sustenta en inspección de código y registro de rutas, con estos límites explícitos; no equivale a una certificación nueva de ejecución de cada flujo ni a una calificación del profesor.

## 9. Pruebas existentes y verificaciones de esta revisión

El commit `c392efe` ya incluye estas fuentes de pruebas relacionadas:

- `tests/Feature/Administrativo/PacienteAdministrativoTest.php`.
- `tests/Feature/Administrativo/MedicoAdministrativoTest.php`.
- `tests/Feature/Administrativo/EspecialidadAdministrativaTest.php`.
- `tests/Feature/Administrativo/HorarioAdministrativoTest.php`.
- `tests/Feature/Administrativo/GestionCitasTest.php`.
- `tests/Feature/Paciente/SolicitudCitaPacienteTest.php`.
- `tests/Feature/Paciente/CitasPacienteTest.php`.

Se verificaron raíz Git, estado, remoto, rama, historial y rutas registradas. PHP y Composer estaban disponibles. Como el único cambio es documental, **no se ejecutaron nuevamente `composer test`, `vendor/bin/pint --test` ni `pnpm build`**. No se atribuyen resultados de pruebas ni capturas inexistentes a esta revisión.

[`phpunit.xml`](../phpunit.xml) fuerza `DB_DATABASE=citavital_testing`, conexión MySQL y `DB_URL` vacío. [`tests/TestCase.php`](../tests/TestCase.php) comprueba el nombre real de la base antes de permitir migraciones de pruebas. Esta auditoría no ejecutó migraciones, seeders ni operaciones CRUD sobre la base principal.

## 10. Seguridad y trazabilidad

La revisión de rutas versionadas no encontró `.env` real, bases locales, volcados SQL, claves privadas, logs ni dependencias instaladas incorporadas al repositorio. `.env.example` contiene campos de credenciales vacíos; los valores ilustrativos del README son marcadores, y las coincidencias de contraseñas en las pruebas revisadas son generación aleatoria. El análisis de patrones no detectó tokens ni claves privadas; no constituye una garantía absoluta sobre cualquier secreto posible.

El documento no contiene credenciales ni datos clínicos. Antes de confirmarlo se debe revisar el contenido preparado mediante `git diff --cached`, su lista con `git diff --cached --name-only` y espacios/conflictos con `git diff --cached --check`. La publicación es un avance normal de `main`, sin forzar ni modificar commits publicados.

Para reproducir la atribución histórica:

```bash
git show --stat c392efe -- app routes resources/views tests
git show --stat 62d61ef
git log --oneline -- app routes resources/views tests
php artisan route:list --except-vendor
```
