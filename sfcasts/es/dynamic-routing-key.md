# Clave de enrutamiento AMQP dinámica (AmqpStamp)

Repitamos la configuración del nuevo intercambio para el transporte `async_priority_high`: queremos que éste entregue al mismo intercambio directo, pero que utilice una clave de enrutamiento diferente para dirigir los mensajes a una cola distinta.

Cambia el intercambio a `messages`, establece el tipo a `direct`, y luego utiliza`default_publish_routing_key` para adjuntar automáticamente una clave de enrutamiento llamada `high`a cada mensaje.

A continuación, para la cola `messages_high`, esto le dice a Messenger que queremos que esta cola se cree y se vincule al intercambio. Eso está bien, pero ahora necesitamos que esa vinculación tenga una clave de enrutamiento. Establece `binding_keys` como `[high]`.

[[[ code('1528a0f27c') ]]]

¿Cómo podemos hacer que Messenger cree esa nueva cola y añada el nuevo enlace? Simplemente realiza cualquier operación que utilice este transporte... ¡como subir una foto! Bien, ve a comprobar el gestor de RabbitMQ: empieza por Intercambios.

Sí, todavía tenemos un solo intercambio `messages`... ¡pero ahora tiene dos enlaces! Si envías un mensaje a este intercambio con una clave de enrutamiento `high`, se enviará a `message_high`.

Haz clic en "Colas" para ver... bien: una nueva cola `messages_high` con un mensaje esperando dentro.

Y... ¡hemos terminado! Esta nueva configuración tiene el mismo resultado final: cada transporte entrega finalmente los mensajes a una cola diferente. Vamos a consumir los mensajes en espera: consume `async_priority_high` y luego `async`.

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

Y los consume en el orden correcto: gestionando primero `AddPonkaToImage` porque está en la cola de alta prioridad y pasando después a los mensajes de la otra cola.

Por cierto, cuando consumimos desde el transporte `async`, por ejemplo, entre bastidores, significa que Messenger está leyendo mensajes de cualquier cola que esté configurada para ese transporte. En nuestra aplicación, cada transporte tiene configurada sólo una cola, pero podrías configurar varias colas bajo un transporte e incluso establecer diferentes claves de enlace para cada una. Pero cuando consumas ese transporte, estarás consumiendo mensajes de todas las colas que hayas configurado.

## Claves de enrutamiento dinámicas

Volvamos atrás y veamos todo el flujo. Cuando enviamos un objeto `AddPonkaToImage`, nuestra configuración de enrutamiento de Messenger siempre lo dirige al transporte `async_priority_high`. Esto hace que el mensaje se envíe al intercambio `messages` con una clave de enrutamiento establecida en `high`... y la lógica de enlace significa que finalmente se entregará a la cola `messages_high`.

Debido a la forma en que funciona el enrutamiento de Messenger -el hecho de enrutar una clase a un transporte- cada clase de mensaje se entregará siempre a la misma cola. ¿Pero qué pasaría si quisieras controlar esto dinámicamente? ¿Y si, en el momento de enviar un mensaje, necesitaras enviar ese mensaje a un transporte diferente al normal? Tal vez decidas que ese mensaje concreto de`AddPonkaToImage` no es importante y que debe ser enviado a `async`.

Bueno... eso no es posible con Messenger: cada clase se enruta siempre a un transporte específico. Pero este resultado final es posible... si sabes cómo aprovechar las claves de enrutamiento.

Este es el truco: ¿qué pasaría si pudiéramos publicar un objeto `AddPonkaToImage`... pero decirle a Messenger que cuando lo envíe al intercambio, utilice la clave de enrutamiento `normal` en lugar de `high`? Sí, el mensaje seguiría siendo técnicamente enrutado al transporte `async_priority_high`... pero en última instancia acabaría en la cola de`messages_normal`. ¡Eso sería todo!

¿Es posible? ¡Totalmente! Abre `ImagePostController` y busca dónde enviamos el mensaje. Después de `DelayStamp`, añade un nuevo `AmqpStamp` - pero ten cuidado de no elegir `AmqpReceivedStamp` - eso es algo diferente... y no nos sirve. Este sello acepta unos cuantos argumentos y el primero -¡juego! - es la clave de enrutamiento a utilizar! Pasa este `normal`.

[[[ code('d1e8c4baab') ]]]

¡Vamos a probarlo! Detén el trabajador para que podamos ver lo que ocurre internamente. Luego, sube una foto, ve al gestor de RabbitMQ, haz clic en colas... actualiza hasta que veas el mensaje en la cola correcta... tenemos que esperar el retraso... ¡y ahí está! Acabó en `messages_normal`.

## ¿Qué más puedes personalizar en un mensaje Amqp?

Por cierto, si miras dentro de esta clase `AmqpStamp`, el segundo y tercer argumento son para algo llamado `$flags` y `$attributes`. Estos son un poco más avanzados, pero pueden resultar útiles. Pulsa Shift+Shift para abrir un archivo llamado `Connection.php` - asegúrate de abrir el que está en el directorio `AmqpExt`. Ahora busca un método llamado `publishOnExchange()`.

Cuando se envía un mensaje a RabbitMQ, éste es el método de bajo nivel que realmente hace ese envío. Aquí se utilizan los `$flags` y `$attributes` del sello Se pasan como tercer y cuarto argumento a algún método de `$exchange->publish()`. Mantén pulsado Cmd o Ctrl y haz clic para saltar a ese método.

Esto nos hace saltar a un "stub" -un método y una declaración "falsos"... porque esta clase -llamada `AMQPExchange` no es algo que vayas a encontrar en tu directorio `vendor/`. No, esta clase proviene de la extensión AMQP PHP que hemos instalado antes.

Así que, si encuentras que realmente necesitas controlar algo sobre cómo se publica un mensaje a través de esta extensión, puedes hacerlo con las clases `$flags` y`$attributes`. Los documentos de arriba hacen un buen trabajo mostrándote las opciones.

Y... ¡eso es todo para AMQP y RabbitMQ! Seguro que hay más cosas que aprender sobre RabbitMQ -es un tema enorme por sí mismo-, pero ahora tienes un firme conocimiento de sus conceptos más importantes y de cómo funcionan. Y, a menos que necesites hacer cosas muy avanzadas, entiendes bastante para trabajar con Messenger.

A continuación, hasta ahora hemos estado enviando mensajes desde nuestra aplicación Symfony y consumiéndolos desde esa misma aplicación. Pero no siempre es así. Uno de los poderes de un "broker de mensajes" como RabbitMQ es la capacidad de enviar mensajes desde un sistema y manejarlos en un sistema totalmente diferente... quizás en un servidor totalmente diferente o escrito en un lenguaje totalmente diferente. ¡Una locura!

Pero si vamos a utilizar Messenger para enviar mensajes a una cola que luego será manejada por una aplicación totalmente diferente... probablemente necesitemos codificar esos mensajes como JSON... en lugar del formato serializado de PHP que estamos utilizando ahora.
