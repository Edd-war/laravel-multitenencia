<?php

use Eddwar\Multitenencia\Actions\AccionHacerColaInquilinoReconocido;
use Eddwar\Multitenencia\Actions\AccionHacerInquilinoActual;
use Eddwar\Multitenencia\Actions\AccionMigrarInquilino;
use Eddwar\Multitenencia\Actions\AccionOlvidarInquilinoActual;
use Eddwar\Multitenencia\Jobs\InquilinoNoReconocido;
use Eddwar\Multitenencia\Jobs\InquilinoReconocido;
use Eddwar\Multitenencia\Models\Inquilino;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\CallQueuedClosure;

return [
    /*
     * Esta clase es responsable de determinar cuál inquilino debe ser el actual
     * para la solicitud dada.
     *
     * Esta clase debe extender de `Eddwar\Multitenencia\BuscadorDeInquilinos\BuscadorDeInquilinos`
     */
    'buscador_de_inquilinos' => null,

    /*
     * Estos campos son utilizados por el comando tenant:artisan para coincidir con uno o más inquilinos.
     */
    'campos_de_busqueda_artisan_para_inquilinos' => [
        'id',
    ],

    /*
     * Estas tareas se realizarán al cambiar de inquilino.
     *
     * Una tarea válida es cualquier clase que implemente Eddwar\Multitenencia\Tasks\TareaDeCambioDeInquilino
     */
    'tareas_de_cambio_de_inquilino' => [
        // \Eddwar\Multitenencia\Tasks\TareaDeCacheDePrefijos::class,
        // \Eddwar\Multitenencia\Tasks\TareaDelCambioDeBaseDeDatosDelInquilino::class,
        // \Eddwar\Multitenencia\Tasks\TareaDeCacheDeCambioDeRuta::class,
    ],

    /*
     * Esta clase es el modelo utilizado para almacenar la configuración de los inquilinos.
     *
     * Debe extender de `Eddwar\Multitenencia\Models\Inquilino::class` o
     * implementar la interfaz `Eddwar\Multitenencia\Contracts\EsInquilino::class`
     */
    'modelo_del_inquilino' => Inquilino::class,

    /*
     * Si hay un inquilino actual al despachar un trabajo, el ID del inquilino actual
     * se establecerá automáticamente en el trabajo. Cuando se ejecute el trabajo, el inquilino
     * establecido se convertirá en el actual.
     */
    'colas_reconocen_inquilinos_por_defecto' => true,

    /*
     * El nombre de la conexión para acceder a la base de datos del inquilino.
     *
     * Establézcalo en `null` para usar la conexión por defecto.
     */
    'nombre_de_conexion_de_la_base_de_datos_del_inquilino' => null,

    /*
     * El nombre de la conexión para acceder a la base de datos del propietario.
     */
    'nombre_de_conexion_de_la_base_de_datos_del_propietario' => null,

    /*
     * Esta clave se utilizará para asociar al inquilino actual en el contexto.
     */
    'clave_de_contexto_del_inquilino_actual' => 'tenantId',

    /*
     * Esta clave se utilizará para vincular al inquilino actual en el contenedor.
     */
    'clave_de_contenedor_del_inquilino_actual' => 'currentTenant',

    /*
     * Establézcalo en `true` si desea almacenar en caché las rutas de los inquilinos
     * en un archivo compartido usando la `TareaDeCacheDeCambioDeRuta`.
     */
    'cache_de_rutas_compartido' => false,

    /*
     * Puede personalizar parte del comportamiento de este paquete utilizando su propia acción personalizada.
     * Su acción personalizada siempre debe extender la acción por defecto.
     */
    'acciones' => [
        'accion_hacer_inquilino_actual' => AccionHacerInquilinoActual::class,
        'accion_olvidar_inquilino_actual' => AccionOlvidarInquilinoActual::class,
        'accion_hacer_cola_inquilino_reconocido' => AccionHacerColaInquilinoReconocido::class,
        'migrar_inquilino' => AccionMigrarInquilino::class,
    ],

    /*
     * Puede personalizar la forma en que el paquete resuelve lo encolable a un trabajo.
     *
     * Por ejemplo, usando el paquete laravel-actions (por Loris Leiva), puede
     * resolver JobDecorator para obtener getAction() de esta forma: JobDecorator::class => 'getAction'
     */
    'cola_a_trabajo' => [
        SendQueuedMailable::class => 'mailable',
        SendQueuedNotifications::class => 'notification',
        CallQueuedClosure::class => 'closure',
        CallQueuedListener::class => 'class',
        BroadcastEvent::class => 'event',
    ],

    /*
     * Interfaz que, una vez implementada, hará que el trabajo reconozca al inquilino.
     */
    'interfaz_reconoce_inquilinos' => InquilinoReconocido::class,

    /*
     * Interfaz que, una vez implementada, hará que el trabajo no reconozca al inquilino.
     */
    'interfaz_no_reconoce_inquilinos' => InquilinoNoReconocido::class,

    /*
     * Trabajos que reconocen al inquilino incluso si no implementan la interfaz InquilinoReconocido.
     */
    'trabajos_que_reconocen_inquilinos' => [
        // ...
    ],

    /*
     * Trabajos que no reconocen al inquilino incluso si no implementan la interfaz InquilinoNoReconocido.
     */
    'trabajos_que_no_reconocen_inquilinos' => [
        // ...
    ],

    /*
     * Las rutas donde se encuentran las migraciones del propietario y del inquilino.
     */
    'ruta_de_migraciones_del_propietario' => 'database/migrations/propietario',
    'ruta_de_migraciones_del_inquilino' => 'database/migrations/inquilinos',
];
