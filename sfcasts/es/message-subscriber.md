# Configuración avanzada del manipulador: Suscriptores del Manejador

Abre `DeleteImagePostHandler`. Lo principal que necesita saber un bus de mensajes es el vínculo entre la clase de mensajes `DeleteImagePost` y su manejador. Necesita saber que cuando despachamos un objeto `DeleteImagePost`, debe llamar a`DeleteImagePostHandler`.

¿Cómo sabe Messenger que estas dos clases están conectadas? Lo sabe porque nuestro manipulador implementa `MessageHandlerInterface` -esto lo "marca" como manipulador de mensajes- y porque su método `__invoke()` tiene un tipo de referencia con `DeleteImagePost`. Si sigues estas dos reglas -implementar esa interfaz y crear un método `__invoke()`con un argumento con tipo de referencia con la clase de mensajes- entonces... ¡ya está!

Busca tu terminal y ejecuta:

```terminal
php bin/console debug:messenger
```

¡Si! Esto lo demuestra: `DeleteImagePost` es manejado por `DeleteImagePostHandler`.

Entonces... en `config/services.yaml`, nos pusimos un poco más elegantes. Al organizar cada tipo de mensaje -comandos, eventos y consultas- en diferentes directorios, pudimos añadir una etiqueta a cada servicio. Esto da un poco más de información a Messenger. Dice:

> Quiero que hagas la conexión normal entre la `DeleteImagePost`
> clase de mensaje y `DeleteImagePostHandler`... pero sólo quiero que le digas
> al "bus de comandos" sobre esa conexión... porque ese es el único bus al que
> voy a enviar ese mensaje.

También lo vemos en `debug:messenger`: el bus de comandos conoce la conexión`DeleteImagePost` y `DeleteImagePostHandler` y los otros dos buses conocen otros enlaces de mensajes y manejadores de mensajes. Ah, y como recordatorio, si todo esto de las "etiquetas" te confunde... sáltalo. Organiza un poco más las cosas, pero puedes tener con la misma eficacia un solo bus que lo maneje todo.

En cualquier caso, este sistema es rápido de usar, pero hay algunas cosas que no puedes cambiar. Por ejemplo, el método de tu manejador debe llamarse `__invoke()`... eso es lo que busca Symfony. Y como una clase sólo puede tener un método llamado `__invoke()`, esto significa que no puedes tener un único manejador que maneje varias clases de mensajes diferentes. De todas formas, no me gusta hacer esto, prefiero una clase de mensaje por manejador... pero es una limitación técnica.

## MessageHandlerInterface

Ahora que hemos revisado todo esto... resulta que esto es sólo una parte de la historia. Si queremos, podemos tomar más control de cómo se vincula una clase de mensaje con su manejador... incluyendo alguna configuración extra.

¿Cómo? En lugar de implementar `MessageHandlerInterface`, implementa`MessageSubscriberInterface`.

[[[ code('09d6bf3c0e') ]]]

Esto es un cambio menos grande de lo que parece. Si abres`MessageSubscriberInterface`, extiende `MessageHandlerInterface`. Así, seguimos implementando efectivamente la misma interfaz... pero ahora nos vemos obligados a tener un nuevo método: `getHandledMessages()`.

En la parte inferior de mi clase, iré a Código -> Generar -o Comando + N en un Mac- y seleccionaré "Implementar métodos".

En cuanto implementemos esta interfaz, en lugar de buscar por arte de magia el método`__invoke()` y comprobar el tipo-indicación del argumento para saber qué clase de mensaje debe manejar, Symfony llamará a este método. ¿Nuestro trabajo aquí? Decirle exactamente qué clases manejamos, qué método debe llamar y... ¡algunas otras cosas divertidas!

[[[ code('9dc404486a') ]]]

## Configuración del manejo de mensajes

Lo más fácil que puedes poner aquí es `yield DeleteImagePost::class`. No pienses demasiado en ese rendimiento... es sólo azúcar sintáctico. También podrías devolver un array con una cadena `DeleteImagePost::class` dentro.

[[[ code('0ac50455c8') ]]]

¿Qué diferencia supone eso? Vuelve a ejecutar `debug:messenger`.

```terminal-silent
php bin/console debug:messenger
```

Y... no supuso ninguna diferencia. Con esta configuración súper sencilla, le hemos dicho a Messenger que esta clase maneja objetos `DeleteImagePost`... y luego Messenger sigue asumiendo que debe ejecutar un método llamado `__invoke()`.

Pero técnicamente, esta sugerencia de tipo ya no es necesaria. Elimina eso y vuelve a ejecutarlo:

```terminal
php bin/console debug:messenger
```

Sigue viendo la conexión entre la clase de mensaje y el manejador.

## Controlar el método y manejar varias clases

Vale... pero como probablemente deberíamos utilizar las sugerencias de tipo... esto no es tan interesante todavía. ¿Qué más podemos hacer?

Bueno, asignando esto a un array, podemos añadir algo de configuración. Por ejemplo, podemos decir `'method' => '__invoke'`. Sí, ahora podemos controlar qué método llamará Messenger. Esto es especialmente útil si decides que quieres añadir otro rendimiento para manejar un segundo mensaje... y quieres que Messenger llame a un método diferente.

[[[ code('8fdab60f6b') ]]]

## Prioridad del manipulador

¿Qué más podemos poner aquí? Una opción es `priority` - pongámosla a... 10.

[[[ code('1d6175bba9') ]]]

Esta opción es... mucho menos interesante de lo que puede parecer en un principio. Ya hablamos antes de los transportes prioritarios: en `config/packages/messenger.yaml`creamos dos transportes - `async` y `async_priority_high` - y dirigimos mensajes diferentes a cada uno. Lo hicimos así para que, al ejecutar nuestro trabajador, podamos decirle que siempre lea primero los mensajes de `async_priority_high` antes de leer los de `async`. Eso hace que `async_priority_high` sea un lugar al que enviemos mensajes de "mayor" prioridad.

La opción `priority` es aquí... menos potente. Si envías un mensaje a un transporte con prioridad 0 y luego envías otro mensaje a ese mismo transporte con prioridad 10, ¿qué crees que pasará? ¿Qué mensaje se tratará primero?

La respuesta: el primer mensaje enviado, el de menor prioridad. Básicamente, Messenger siempre leerá los mensajes según el principio de "primero en entrar, primero en salir": siempre leerá primero los mensajes más antiguos. El `priority` no influye en esto.

Entonces... ¿qué hace? Bueno, si `DeleteImagePost` tuviera dos manejadores... y uno tuviera la prioridad por defecto de cero y otro tuviera 10, el manejador con prioridad 10 sería llamado primero. Esto no suele ser importante, pero podría serlo si tuvieras dos manejadores de eventos y realmente necesitaras que ocurrieran en un orden determinado.

A continuación, vamos a hablar de otra opción que puedes pasar aquí, la más potente. Se llama `from_transport` y te permite, más o menos, enviar diferentes "manejadores" de un mensaje a diferentes transportes para que cada uno pueda ser consumido independientemente.
