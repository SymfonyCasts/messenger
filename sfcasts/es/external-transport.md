# Transporte para consumir mensajes externos

Acabamos de crear una nueva clase de mensaje y un manejador... y luego lo hemos instanciado y enviado directamente al bus de mensajes. Sí, acabamos de hacer algo totalmente... ¡aburrido! Pero... ¡en realidad es bastante parecido a nuestro objetivo real! Nuestro objetivo real es fingir que un sistema externo está poniendo mensajes en una cola de RabbitMQ... probablemente formateados como JSON... y nosotros leeremos esos mensajes, transformaremos ese JSON en un objeto `LogEmoji` y... básicamente lo enviaremos a través del bus de mensajes. En realidad es el mismo flujo básico: en ambos casos, creamos un objeto `LogEmoji`y lo pasamos a Messenger.

[[[ code('c0f66b6018') ]]]

## Crear un transporte dedicado

El primer paso es crear un transporte que lea estos mensajes desde la cola en la que los coloque el sistema exterior. Mantendremos los transportes `async` y`async_priority_high`: seguiremos enviando y recibiendo desde ellos. Pero ahora crea uno nuevo llamado, qué tal: `external_messages`. Utilizaré el mismo DSN porque seguimos consumiendo cosas de RabbitMQ. Pero para las opciones, en lugar de consumir mensajes de `message_high` o `messages_normal`, los consumiremos de cualquier cola que esté utilizando ese sistema externo - supongamos que se llama `messages_from_external`. Ponlo sólo en `~`.

[[[ code('5b68cfa12a') ]]]

Por cierto, es importante que utilicemos un transporte diferente que lea de una cola diferente para estos mensajes externos. ¿Por qué? Porque, como verás en unos minutos, estos mensajes externos necesitarán una lógica especial para descodificarlos en el objeto correcto. Adjuntaremos esa lógica especial al transporte.

De todos modos, por encima de esto añade `auto_setup: false`.

***TIP
Para soportar el reintento, deberás utilizar `auto_setup` y configurar algunas cosas más. Consulta el consejo siguiente para más detalles.
***

Bien, aquí ocurren algunas cosas importantes. La primera es que esta configuración de la cola significa que cuando consumamos del transporte `external_messages`, Messenger leerá los mensajes de una cola llamada `messages_from_external`. La segunda cosa importante es `auto_setup: false`. Esto le dice a Messenger que no intente crear esta cola. ¿Por qué? Bueno... Supongo que nuestra aplicación podría crear esa cola... eso probablemente estaría bien... pero como estamos esperando que un sistema externo envíe mensajes a esta cola, supongo que ese sistema querrá encargarse de asegurarse de que existe.

Ah, y probablemente también te hayas dado cuenta de que no he añadido ninguna configuración en `exchange`. Eso fue a propósito. El intercambio sólo se utiliza cuando se envía un mensaje. Y como no pensamos enviar nunca un mensaje a través de este transporte, esa parte del transporte no se utilizará nunca.

***TIP
Corrección: si utilizas AMQP y quieres que funcionen los "reintentos", tendrás que configurar una clave de enrutamiento y vinculación para que, si un mensaje debe enviarse a este transporte (para su reenvío), Messenger pueda adjuntar la clave de vinculación correcta para que el mensaje acabe en la cola `messages_from_external`. Consulta el [bloque de código](https://symfonycasts.com/screencast/messenger/external-transport#codeblock-5b68cfa12a) 
en esta página para ver un ejemplo actualizado.
***

Así que con sólo esto, deberíamos ser capaces de consumir desde el nuevo transporte. Ve a tu terminal y ejecuta:

```terminal
php bin/console messenger:consume -vv external_messages
```

Y... ¡explota! Esto es increíble.

> Error del canal del servidor: 404, mensaje: NOT_FOUND - no hay cola 'messages_from_external'

¡Estamos viendo nuestro `auto_setup: false` en acción! En lugar de crear esa cola cuando no existía, ha explotado. ¡Me encanta!

## Creando la cola a mano

Ahora vamos a imaginar que somos ese sistema "externo" y queremos crear esa cola. Copia el nombre de la cola - `messages_from_external` - y, dentro del Gestor de Conejos, crea una nueva cola con ese nombre. No te preocupes por las opciones: no nos importarán.

Y... ¡hola cola! Vamos a ver si podemos consumir mensajes de ella:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

¡Funciona! Bueno... todavía no hay ningún mensaje en la cola, pero se está comprobando felizmente si hay alguno.

## Poner un mensaje "externo" en la cola

Ahora, vamos a seguir fingiendo que somos el sistema "externo" que va a enviar mensajes a esta cola. En la pantalla de gestión de la cola, podemos publicar un mensaje en la cola. ¡Muy práctico!

Entonces... ¿qué aspecto tendrán estos mensajes? Bueno... pueden tener cualquier aspecto: JSON, XML, una imagen binaria, arte ASCII... lo que queramos. Sólo tendremos que asegurarnos de que nuestra aplicación Symfony pueda entender el mensaje; eso es algo en lo que trabajaremos en unos minutos.

Pensemos: si un sistema externo quiere enviar a nuestra app un comando para registrar un emoji... y puede elegir qué emoji mediante un número... entonces... ¿quizás el mensaje sea JSON con este aspecto? Una clave de `emoji` establecida en 2:

```json
{
  "emoji": 2
}
```

¡Publica! Vale, ¡ve a comprobar el trabajador! Woh... ¡ha explotado! ¡Genial!

> No se ha podido decodificar el mensaje con la serialización de PHP

Y luego muestra nuestro JSON. ¡Por supuesto! Si estás consumiendo un mensaje que fue colocado en la cola por un sistema externo... ese mensaje probablemente no estará en el formato de serialización PHP... y realmente no debería estarlo. No, el mensaje probablemente será JSON o XML. El problema es que nuestro transporte está intentando transformar ese JSON en un objeto utilizando el serializador de PHP por defecto. Literalmente, está llamando a `unserialize()` sobre ese JSON.

Tenemos que ser más inteligentes: cuando un transporte consume mensajes de un sistema externo, tiene que tener un serializador personalizado para que podamos tomar el control. Hagamos eso a continuación.
