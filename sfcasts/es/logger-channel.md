# Configuración del canal de registro y autoconexión

Éste es nuestro objetivo... y el resultado final va a ser muy bueno: aprovechar nuestro middleware -y el hecho de que estamos añadiendo este identificador único a cada mensaje- para registrar todo el ciclo de vida de un mensaje en un archivo. Quiero ver cuándo se despachó originalmente un mensaje, cuándo se envió al transporte, cuándo se recibió del transporte y cuándo se gestionó.

## Añadir un gestor de registros

Antes de entrar en el tema del middleware, vamos a configurar un nuevo canal de registro que registre en un nuevo archivo. Abre `config/packages/dev/monolog.yaml` y añade una nueva clave `channels`. Espera... eso no es correcto. Un canal de registro es, en cierto modo, una "categoría", y puedes controlar cómo se gestionan los mensajes de registro de cada categoría. No queremos añadirlo aquí porque entonces ese nuevo canal sólo existiría en el entorno de desarrollo. No, queremos que el canal exista en todos los entornos... aunque decidamos dar un tratamiento especial a esos mensajes sólo en `dev`.

Para ello, directamente dentro de `config/packages`, crea un nuevo archivo llamado`monolog.yaml`... aunque... recuerda: los nombres de estos archivos de configuración no son importantes. Lo que es importante es añadir una clave `monolog`, y luego `channels` establecer una matriz con uno nuevo - ¿qué tal `messenger_audit`.

[[[ code('71c5f61745') ]]]

Gracias a esto, ahora tenemos un nuevo servicio de registro en el contenedor para este canal. Vamos a encontrarlo: en tu terminal, ejecuta:

```terminal
php bin/console debug:container messenger_audit
```

Ahí está: `monolog.logger.messenger_audit` - lo utilizaremos en un momento. Pero antes, quiero hacer que cualquier registro de este canal se guarde en un nuevo archivo en el entorno`dev`. Retrocede en `config/packages/dev/monolog.yaml`, copia el manejador de`main`, pégalo y cambia la clave a `messenger`... aunque podría ser cualquier cosa. Actualiza el archivo para que se llame `messenger.log` y -aquí está la magia- en lugar de decir: registrar todos los mensajes excepto los del canal `event`, cámbialo para que sólo registre los mensajes que están en ese canal `messenger_audit`.

[[[ code('4ca2bf0605') ]]]

## Autoconexión del registrador de canales

¡Genial! Para utilizar este servicio, no podemos simplemente autocablear el canal normal`LoggerInterface`... porque eso nos dará el registrador principal. Este es uno de esos casos en los que tenemos varios servicios en el contenedor que utilizan todos la misma clase o interfaz.

Para hacerlo deseable, de nuevo en `services.yaml`, añade un nuevo bind global:`$messengerAuditLogger` que apunte al id del servicio: cópialo del terminal, y pégalo como `@monolog.logger.messenger_audit`.

[[[ code('e8c2081f75') ]]]

Gracias a esto, si utilizamos un argumento llamado `$messengerAuditLogger` en el constructor de un servicio o en un controlador, Symfony nos pasará ese servicio. Por cierto, a partir de Symfony 4.2, en lugar de vincularse sólo al nombre del argumento, también puedes vincularte al nombre y al tipo diciendo`Psr\Log\LoggerInterface $messengerAuditLogger`. Eso sólo hace las cosas más específicas: Symfony nos pasaría este servicio para cualquier argumento que tenga este nombre y el tipo-indicación `LoggerInterface`.

En cualquier caso, tenemos un nuevo canal de registro, ese canal registrará en un archivo especial, y el servicio de registro para ese canal es deseable. ¡Es hora de ponerse a trabajar!

Cierra los archivos de configuración del monolog y ve a `AuditMiddleware`. Añade un`public function __construct()` con un argumento `LoggerInterface $messengerAuditLogger` - el mismo nombre que usamos en la configuración. Llamaré a la propiedad en sí `$logger`, y terminaré esto con `$this->logger = $messengerAuditLogger`.

[[[ code('315b4fbc04') ]]]

## Configurar el contexto

Abajo, en `handle()`, elimina el `dump()` y crea una nueva variable llamada `$context`. Además del mensaje de registro propiamente dicho, es un hecho poco conocido que puedes pasar información extra al registrador... ¡lo cual es súper útil! Vamos a crear una clave llamada `id` configurada con el id único, y otra llamada `class` configurada con la clase del mensaje original. Podemos conseguirlo con`get_class($envelope->getMessage())`.

[[[ code('68ef1f5d3f') ]]]

A continuación, ¡hagamos el registro! Es un poco más interesante de lo que cabría esperar. ¿Cómo podemos averiguar si el mensaje actual se acaba de enviar o se acaba de recibir de forma asíncrona desde un transporte? Y si acaba de ser despachado, ¿cómo podemos averiguar si el mensaje será tratado ahora mismo o enviado a un transporte para más tarde? La respuesta... ¡está en los sellos!
