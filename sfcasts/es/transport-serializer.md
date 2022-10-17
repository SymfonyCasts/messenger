# Serializador de transporte personalizado

Si un sistema externo envía mensajes a una cola que vamos a leer, esos mensajes probablemente se enviarán como JSON o XML. Añadimos un mensaje formateado como JSON. Para leerlos, configuramos un transporte llamado `external_messages`. Pero cuando consumimos ese mensaje JSON... ¡explotó! ¿Por qué? Porque el serializador por defecto de todos los transportes es el `PhpSerializer`. Básicamente, está intentando llamar a`unserialize()` en nuestro JSON. Eso... no va a funcionar.

No, si estás consumiendo mensajes que provienen de un sistema externo, vas a necesitar un serializador personalizado para tu transporte. Crear un serializador personalizado es... en realidad una experiencia muy agradable.

## Creación de la clase serializadora personalizada

Dentro de nuestro directorio `src/Messenger/`... aunque esta clase podría vivir en cualquier lugar .. vamos a crear una nueva clase PHP llamada `ExternalJsonMessengerSerializer`. La única regla es que debe implementar `SerializerInterface`. Pero, ¡cuidado! Hay dos `SerializerInterface`: uno es del componente Serializador. Queremos el otro: el del componente Messenger. Iré al menú "Generar código" -o Comando + N en un Mac- y seleccionaré "Implementar métodos" para añadir los dos que requiere esta interfaz: `decode()` y `encode()`.

[[[ code('dbcb2ac20c') ]]]

## El método encode()

La idea es muy sencilla: cuando enviemos un mensaje a través de un transporte que utilice este serializador, el transporte llamará al método `encode()` y nos pasará el objeto `Envelope` que contiene el mensaje. Nuestro trabajo consiste en convertirlo en un formato de cadena que pueda enviarse al transporte. Fíjate en que esto devuelve un array. Pero si miras el `SerializerInterface`, este método debería devolver un array con dos claves `body` - el cuerpo del mensaje - y `headers` - las cabeceras que deban enviarse.

Bonito, ¿verdad? Pero... en realidad nunca vamos a enviar ningún mensaje a través de nuestro transporte externo... así que no necesitamos este método. Para demostrar que nunca será llamado, lanza un nuevo `Exception` con:

> El transporte y el serializador no están pensados para enviar mensajes

[[[ code('105fea8568') ]]]

Eso me dará un suave recordatorio en caso de que haga una tontería y dirija un mensaje a un transporte que utilice este serializador por accidente.

***TIP
En realidad, si quieres que tus mensajes se vuelvan a entregar, tienes que implementar el método `encode()`. Consulta el bloque de código de esta página para ver un ejemplo, que incluye una pequeña actualización de `decode()`.
***

[[[ code('bca382fd36') ]]]

## El método decode()

El método en el que debemos centrarnos es `decode()`. Cuando un trabajador consume un mensaje de un transporte, éste llama a `decode()` en su serializador. Nuestro trabajo consiste en leer el mensaje de la cola y convertirlo en un objeto `Envelope` con el objeto mensaje dentro. Si compruebas el `SerializerInterface` una vez más, verás que el argumento que se nos pasa - `$encodedEnvelope` - es en realidad una matriz con las mismas dos claves que vimos hace un momento: `body`
y `headers`.

Separemos primero las piezas: `$body = $encodedEnvelope['body']` y`$headers = $encodedEnvelope['headers']`. El `$body` será el JSON en bruto del mensaje. Hablaremos de las cabeceras más adelante: ahora está vacío.

[[[ code('edfb7589bd') ]]]

## Convertir el JSON en el sobre

Bien, recuerda nuestro objetivo aquí: convertir este JSON en un objeto `LogEmoji` y luego ponerlo en un objeto `Envelope`. ¿Cómo? ¡Hagámoslo sencillo! Empieza con`$data = json_decode($body, true)` para convertir el JSON en una matriz asociativa.

[[[ code('f3d4436b47') ]]]

Todavía no voy a hacer ninguna comprobación de errores... como comprobar que se trata de un JSON válido - lo haremos un poco más tarde. Ahora digamos `$message = new LogEmoji($data['emoji'])`
porque `emoji` es la clave del JSON que hemos decidido que contenga el `$emojiIndex`.

[[[ code('940d706826') ]]]

Por último, tenemos que devolver un objeto `Envelope`. Recuerda: un `Envelope` no es más que una pequeña envoltura del mensaje en sí... y también puede contener algunos sellos. En la parte inferior, devuelve `new Envelope()` y pon dentro `$message`.

[[[ code('42d4fefb2d') ]]]

## Configurar el serializador en el transporte

¡Ya está! ¡Estamos en la cresta de la ola! Esto ya es un serializador totalmente funcional que puede leer mensajes de una cola. Pero nuestro transporte no empezará a utilizarlo "mágicamente": tenemos que configurarlo. Y... ¡ya sabemos cómo! Ya hemos aprendido que cada transporte puede tener una opción `serializer`. Debajo del transporte externo, añade`serializer` y ponle el id de nuestro servicio, que es el mismo que el nombre de la clase: `App\Messenger\`... y luego iré a copiar el nombre de la clase:`ExternalJsonMessengerSerializer`.

[[[ code('5f97551e8f') ]]]

Por eso hemos creado un transporte separado con una cola separada: sólo queremos que los mensajes externos utilicen nuestro `ExternalJsonMessengerSerializer`. Los otros dos transportes - `async` y `async_priority_high` - seguirán utilizando el PhpSerializer más sencillo... lo cual es perfecto.

Bien, ¡probemos esto! En primer lugar, busca un terminal abierto y sigue los registros:

```terminal
tail -f var/log/dev.log
```

Y despejaré la pantalla. Luego, en mi otro terminal, consumiré los mensajes del transporte `external_messages`:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

¡Perfecto! Todavía no hay mensajes... así que sólo queda esperar. Pero esperamos que cuando publiquemos este mensaje en la cola, sea consumido por nuestro trabajador, descodificado correctamente, ¡y que se registre un emoji! Ah, vale - vamos a intentarlo. Publica! Ah, y vuelve a pasar por el terminal.... ¡ahí está! Tenemos un mensaje importante: queso: ha recibido el mensaje y lo ha gestionado aquí abajo.

Así que... ¡lo hemos conseguido! ¡Somos lo máximo!

Pero... cuando creamos el `Envelope`, no pusimos ningún sello. ¿Deberíamos haberlo hecho? ¿Un mensaje que pasa por el flujo "normal" tiene algunos sellos que deberíamos añadir manualmente aquí? Vamos a sumergirnos en el flujo de trabajo de un mensaje y sus sellos, a continuación.
