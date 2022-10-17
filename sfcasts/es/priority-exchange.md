# Intercambio de prioridades AMQP

La idea de nuestros transportes `async` y `async_priority_high` es que podamos enviar algunos mensajes a `async_priority_high` y otros a `async`, con el objetivo de que esos mensajes acaben en diferentes "cubos"... o, más técnicamente, en diferentes "colas". Entonces podemos ordenar a nuestro trabajador que lea primero todos los mensajes de la cola a la que esté vinculado `async_priority_high` antes de leer los mensajes de la cola a la que esté vinculado el transporte `async`.

## La opción nombre_cola en Doctrine

Esto ya funcionaba antes con Doctrine, gracias a esta opción `queue_name: high`. El valor por defecto de esta opción es... `default`. Como recordatorio, voy a entrar rápidamente en mi base de datos:

```terminal
mysql -u root messenger_tutorial
```

Y veré cómo era esa tabla:

```terminal
DESCRIBE messenger_messages;
```

Sí, la columna `queue_name` era la clave para que esto funcionara. Los mensajes que se enviaban a `async_priority_high` tenían un `queue_name` fijado en `high`, y los que se enviaban al transporte `async` tenían un valor de `default`. Así que, aunque sólo teníamos una tabla de base de datos, funcionaba como dos colas: cuando consumíamos el transporte`async_priority_high`, consultaba todos los mensajes`WHERE queue_name="high"`.

El problema es que esta opción `queue_name` es específica del transporte Doctrine, y no tiene ningún efecto cuando se utiliza AMQP.

## ¿Enrutamiento de mensajes a... una cola?

Pero... a alto nivel... nuestro objetivo es el mismo: necesitamos dos colas. Necesitamos el transporte`async_priority_high` para enviar mensajes a una cola y el transporte `async`para enviar mensajes a otra cola.

Pero con AMQP... no envías un mensaje directamente a una cola... lo envías a un intercambio... y luego es responsabilidad del intercambio mirar sus reglas internas y averiguar a qué cola, o colas, debe ir realmente ese mensaje.

Esto significa que para que un mensaje llegue a una cola, tenemos que ajustar las cosas a nivel de intercambio. Y hay dos formas diferentes de hacerlo. En primer lugar, podríamos seguir teniendo un único intercambio y luego añadir algunas reglas internas -llamadas vinculaciones- para enseñar al intercambio que algunos mensajes deben ir a una cola y otros mensajes deben ir a otra cola. Te mostraré cómo hacerlo un poco más adelante.

La segunda opción no es tan genial, pero es un poco más sencilla. Por defecto, cuando Messenger crea un intercambio, lo hace de tipo `fanout`. Eso significa que cuando se envía un mensaje a este intercambio, se dirige a todas las colas que estén vinculadas a él. Así que si añadimos un segundo enlace a una segunda cola -quizás`messages_high_priority` -, cada mensaje que se envíe a este intercambio se dirigirá a ambas colas. ¡Se duplicaría! Eso... no es lo que queremos.

En su lugar, vamos a crear dos intercambios `fanout`, y cada intercambio dirigirá todos sus mensajes a una cola distinta. Tendremos dos intercambios y dos colas.

## Configurar un segundo intercambio

Vamos a configurar esto dentro de `messenger.yaml`. Debajo de `options` añade `exchange`y luego `name` con, qué tal, `messages_high_priority`. Debajo de esto, añade`queues` con una sola clave: `messages_high` ajustada a `null`.

[[[ code('121cf747af') ]]]

Esta configuración tiene tres efectos. En primer lugar, como tenemos activada la función `auto_setup`, la primera vez que hablemos con RabbitMQ, Messenger creará el intercambio`messages_high_priority`, la cola `messages_high` y los enlazará. El segundo efecto es que cuando enviemos mensajes a este transporte se enviarán al intercambio `messages_high_priority`. El tercer y último efecto es que cuando consumamos desde este transporte, Messenger leerá los mensajes de la cola `messages_high`.

Si esto aún no tiene todo el sentido... no te preocupes: vamos a ver esto en acción. En primer lugar, asegúrate de que tu trabajador no está en marcha: el nuestro está parado. Ahora vamos a eliminar tres fotos -una, dos y tres- y a subir cuatro fotos.

¡Genial! ¡Veamos qué ha pasado en RabbitMQ! Dentro del gestor, haz clic en "Intercambios". ¡Bien! ¡Tenemos un nuevo intercambio `messages_high_priority`! El intercambio original`messages` sigue enviando todos sus mensajes a una cola `messages`... pero el nuevo intercambio envía todos sus mensajes a una cola llamada `messages_high`. Eso es gracias a nuestra configuración `queues`.

Y... ¿qué hay dentro de cada cola? ¡Ve a comprobarlo! Es exactamente lo que queremos: los 3 mensajes borrados están esperando en la cola `messages` y las 4 fotos recién subidas están en `messages_high`. ¡Cada transporte está recibiendo con éxito sus mensajes en una cola distinta! Y eso significa que podemos consumirlos de forma independiente.

En la línea de comandos, normalmente le diríamos a Messenger que consuma de`async_priority_high` y luego de `async` para obtener nuestra entrega priorizada. Pero para mostrar claramente lo que ocurre, vamos a consumirlos de forma independiente por ahora. Empieza consumiendo mensajes del transporte `async`:

```terminal-silent
php bin/console messenger:consume -vv async
```

Comienza a procesar los objetos de `ImagePostDeletedEvent`... y se detiene después de esos tres. ¡Ya ha terminado! La cola está vacía. El comando no ha leído los mensajes de `messages_high`. Para ello, consume el transporte `async_priority_high`:

```terminal-silent
php bin/console messenger:consume -vv async_priority_high
```

¡Ya está! La forma más sencilla... pero no más elegante... de tener transportes priorizados con AMQP es enviar cada transporte a un intercambio diferente y configurarlo para que se dirija a una cola diferente. Más adelante... veremos la forma más elegante.

Antes de llegar ahí, ¿recuerdas cuando te hice comentar el `DelayStamp` antes de que empezáramos a usar RabbitMQ? A continuación, te mostraré por qué: volveremos a añadir ese `DelayStamp`y veremos la forma loca en que se "retrasan" los mensajes en RabbitMQ.
