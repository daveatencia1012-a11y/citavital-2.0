<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Credenciales del administrador de demostración
    |--------------------------------------------------------------------------
    |
    | La contraseña se lee exclusivamente del entorno. Nunca debe escribirse
    | en el código ni en archivos versionados.
    |
    */

    'admin_password' => env('CITAVITAL_ADMIN_PASSWORD'),

    'admin_email' => 'administrativo.demo@uniajs.edu.co',

    /*
    |--------------------------------------------------------------------------
    | Acceso demostrativo del portal del paciente
    |--------------------------------------------------------------------------
    |
    | CitaVital es un prototipo académico. El acceso mediante cédula se habilita
    | únicamente en el entorno local y solo para documentos ficticios creados
    | para la demostración. El servicio PatientAccessService vuelve a validar
    | ambas condiciones, por lo que activar esta opción en otro entorno no
    | expone información de pacientes reales.
    |
    */

    'patient_demo_login' => env(
        'CITAVITAL_PATIENT_DEMO_LOGIN',
        env('APP_ENV', 'production') === 'local',
    ),

    'patient_demo_document_prefixes' => ['100000'],
];
