# Registro del ciclo de vida de los mensajes del middleware

Nuestro middleware es llamado en dos situaciones diferentes. En primer lugar, se llama cuando se envía inicialmente el mensaje. Por ejemplo, en`ImagePostController`, en el momento en que llamamos a `$messageBus->dispatch()`, se llama a todo el middleware -independientemente de si el mensaje se gestionará de forma asíncrona o no. Y en segundo lugar, cuando el trabajador - `bin/console messenger:consume` - recibe un mensaje del transporte, lo devuelve al bus y se llama de nuevo al middleware.

Esto es lo más complicado del middleware: intentar averiguar en qué situación se encuentra actualmente. Afortunadamente, Messenger añade "sellos" al `Envelope` a lo largo del camino, y éstos nos dicen exactamente lo que está pasando.

## ¿Se ha recibido el mensaje desde el transporte? SelloRecibido

Por ejemplo, cuando se recibe un mensaje de un transporte, Messenger añade un`ReceivedStamp`. Así, si `$envelope->last(ReceivedStamp::class)`, entonces este mensaje está siendo procesado por el trabajador y acaba de ser recibido desde un transporte.

[[[ code('209bc336b0') ]]]

Vamos a registrarlo: `$this->logger->info()` con una sintaxis especial:

> [{id}] Recibido y gestionado {clase}

A continuación, pasa `$context` como segundo argumento. La matriz `$context` es genial por dos razones. En primer lugar, cada gestor de registro lo recibe y puede hacer lo que quiera con él; normalmente el `$context` se imprime al final del mensaje de registro. Y en segundo lugar, si utilizas estos pequeños comodines de `{}`, ¡los valores del contexto se rellenarán automáticamente!

[[[ code('12a5f84b02') ]]]

Si el mensaje no se acaba de recibir, di `$this->logger->info()` y empieza de la misma manera:

> [{id}] Manejo o envío de {clase}

[[[ code('2c51728cbd') ]]]

En este punto, sabemos que el mensaje se acaba de enviar... pero no sabemos si se va a manejar en este momento o se va a enviar a un transporte. Lo mejoraremos en unos minutos.

Pero antes, ¡probemos! Pon en marcha el trabajador y dile que lea del transporte `async`:

```terminal-silent
php bin/console messenger:consume -vv async
```

Ah, ¡creo que tenemos algunos mensajes de antes todavía en la cola! Cuando termine, despejemos la pantalla. Abramos también otra pestaña y creemos el nuevo archivo de registro - `messenger.log` - si no está ya ahí:

```terminal
touch var/log/messenger.log
```

Luego, colócalo en la cola para que podamos ver los mensajes:

```terminal
tail -f var/log/messenger.log
```

¡Oh, qué bien! Esto ya tiene unas cuantas líneas de los antiguos mensajes que acaba de procesar. Borremos eso para tener pantallas frescas que mirar.

¡Hora de probar! Muévete y sube una nueva foto. Vuelve a tu terminal y... ¡sí! Los dos mensajes de registro ya están ahí: "Manipulando o enviando" y luego "Recibido y manipulando" cuando se recibió el mensaje del transporte... que fue casi instantáneo. Sabemos que estas entradas de registro son para el mismo mensaje gracias al identificador único que aparece al principio.

## Determinar si el mensaje se maneja o se envía

Pero... podemos hacer algo mejor que decir "manipulación o envío". ¿Cómo? Esta línea`$stack->next()->handle()` se encarga de llamar al siguiente middleware... que a su vez llamará al siguiente middleware y así sucesivamente. Como nuestro código de registro está por encima de esto, significa que nuestro código está siendo potencialmente llamado antes de que otros middleware hagan su trabajo. De hecho, nuestro código se está ejecutando antes que el middleware principal que es responsable de manejar o enviar el mensaje.

Entonces... ¿cómo podemos determinar si el mensaje será enviado o manejado inmediatamente... antes de que el mensaje sea realmente enviado o manejado inmediatamente? No podemos!

Compruébalo: elimina el `return` y en su lugar di`$envelope = $stack->next()->handle()`. Luego, mueve esa línea por encima de nuestro código y, al final, `return $envelope`.

[[[ code('e1632c7ff0') ]]]

Si no hiciéramos nada más... el resultado sería prácticamente el mismo: registraríamos exactamente los mismos mensajes... pero técnicamente, las entradas de registro se producirían después de que el mensaje se enviara o gestionara en lugar de antes.

Pero! fíjate en que cuando llamamos a `$stack->next()->handle()` para ejecutar el resto del middleware, obtenemos de vuelta un `$envelope`... ¡que puede contener nuevos sellos! De hecho, si el mensaje fue enviado a un transporte en lugar de ser manejado inmediatamente, se marcará con un `SentStamp`.

Si añadimos `elseif` `$envelope->last(SentStamp::class)` , sabremos que ese mensaje fue enviado, no gestionado. Utiliza `$this->logger->info()` con nuestro truco `{id}`y `sent {class}`.

[[[ code('38cabed984') ]]]

A continuación, ahora sabemos que definitivamente estamos "Manejando la sincronización". El mensaje superior "Recibido y manipulado" sigue siendo cierto, pero lo cambiaré para que sólo diga "Recibido": un mensaje siempre se manipula cuando se recibe, así que eso era redundante.

[[[ code('3f3fe5054b') ]]]

De acuerdo Vamos a borrar nuestra pantalla de registro y a reiniciar el trabajador:

```terminal-silent
php bin/console messenger:consume -vv async
```

Sube una foto... luego pasa... y ve al archivo de registro. ¡Sí! ¡Enviada, y luego Recibida! Si hubiéramos subido 5 fotos, podríamos utilizar el id único para identificar cada mensaje individualmente.

Pulsa intro varias veces: Quiero ver un ejemplo aún más genial. ¡Elimina una foto y vuelve a pasar por encima! ¡Recuerda que esto envía dos mensajes! La parte del id. único hace aún más evidente lo que está ocurriendo: `DeletePhotoFile` se envió al transporte, luego `DeleteImagePost` se gestionó de forma sincrónica... y después se recibió y procesó`DeletePhotoFile`.

En realidad, lo que ocurrió fue lo siguiente: `DeleteImagePost` se manejó de forma sincrónica e, internamente, despachó `DeletePhotoFile` que se envió al transporte. Los dos primeros mensajes están un poco desordenados porque nuestro código de registro se ejecuta siempre después de que se ejecute el resto de la cadena, así que después de que se manejara`DeleteImagePost`. Podríamos mejorar esto moviendo la lógica de registro de`Handling Sync` por encima del código que llama al resto del middleware. Sí, este material es súper potente... pero puede ser un poco complejo de navegar. Este asunto del registro es probablemente lo más confuso que puede haber.

A continuación: el trabajador gestiona cada mensaje en el orden en que lo ha recibido. Pero... eso no es lo ideal: es mucho más importante que todos los mensajes de `AddPonkaToImage` se gestionen antes que cualquier mensaje de `DeletePhotoFile`. Hagamos eso con transportes prioritarios.
