# Procedencia de CitaVital 2.0

Este repositorio se creó como una copia independiente del código existente de CitaVital, a petición de su propietario. Sus commits agrupan la incorporación de archivos por responsabilidad; no representan las etapas históricas de desarrollo ni nuevas implementaciones realizadas durante la copia.

## Origen

- Carpeta original: `citavital`.
- Revisión de origen: `93d44a6` (`actualizacion`).
- Historial original conservado en esa carpeta: `c392efe`, `62d61ef`, `1c70acf` y `93d44a6`.
- Carpeta nueva: `citavital-2.0`, con repositorio Git independiente y rama `main`.
- No se copiaron el directorio `.git` original, archivos ignorados, entornos privados, bases locales, logs ni dependencias instaladas.
- `.env.example` es una plantilla pública con credenciales vacías, no el entorno privado del propietario.

## Organización de la importación

Los commits incorporan la base Laravel, persistencia Eloquent, acceso, interfaz compartida, gestión de pacientes, médicos y especialidades, horarios y bloqueos, citas, seguimiento e indicadores, pruebas y documentación. Son agrupaciones de un proyecto ya construido: los estados intermedios pueden depender de archivos incorporados en commits posteriores. El punto de uso es la importación completa.

Las fechas de los commits son las del momento de su creación. No se modificaron fechas ni commits del repositorio original.

## Documentación heredada y comprobaciones

El README y `actividad-complementaria-4.md` conservan referencias al repositorio y commits originales como contexto histórico. No son referencias a hashes de este repositorio nuevo, ni una confirmación de publicación de esta copia en GitHub.

La copia no modifica código funcional. Se verifican igualdad de archivos de código con el origen, exclusiones y contenido preparado para cada commit. No se ejecutan migraciones, seeders, pruebas de base de datos ni compilación durante la importación. Las dependencias y el entorno deben configurarse de nuevo para ejecutar la aplicación; cualquier prueba que reconstruya tablas debe utilizar exclusivamente `citavital_testing`.
