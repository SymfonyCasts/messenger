# El ciclo de vida de un mensaje y sus sellos

Olvídate de los mensajes asíncronos y de los transportes externos y todas esas cosas. Abre `ImagePostController`. Como recordatorio, cuando envías un mensaje, en realidad envías un objeto `Envelope`, que es una simple "envoltura" que contiene el mensaje en sí y puede contener también algunos sellos... que añaden información extra.

Si despachas el objeto mensaje directamente, el bus de mensajes crea un`Envelope` para ti y pone tu mensaje dentro. La cuestión es que, internamente, Messenger siempre está trabajando con un `Envelope`. Y cuando llamas a `$messageBus->dispatch()`, también devuelve un `Envelope`: el `Envelope` final después de que Messenger haya hecho todo su trabajo.

Veamos qué aspecto tiene: `dump()` toda esa línea `$messageBus->dispatch()`. Ahora, muévete y sube una foto. Una vez hecho esto, busca esa petición en la barra de herramientas de depuración de la web... y abre el perfilador.

[[[ code('58d6cd358b') ]]]

## El sobre y los sellos después del envío

¡Perfecto! Puedes ver que el `Envelope` final tiene el objeto mensaje original dentro: `AddPonkaToImage`. Pero este `Envelope` tiene ahora más sellos.

¡Hora de repasar rápidamente! Cuando enviamos un mensaje al bus de mensajes, éste pasa por una colección de middleware... y cada middleware puede añadir sellos adicionales al sobre. Si amplías `stamps` en el volcado, ¡vaya! ¡Ahora hay 5 sellos! Los dos primeros - `DelayStamp` y `AmqpStamp` - no son un misterio. Los añadimos manualmente cuando enviamos el mensaje originalmente. El último - `SentStamp` - es un sello que añade el `SendMessageMiddleware`. Como hemos configurado este mensaje para que se dirija al transporte `async_priority_high`, el`SendMessageMiddleware` envía el mensaje a RabbitMQ y luego añade este `SentStamp`. Esto es una señal - para cualquiera que se preocupe - nosotros, u otro middleware - de que este mensaje fue de hecho "enviado" a un transporte. En realidad, es gracias a este sello que el siguiente middleware que se ejecuta - `HandleMessageMiddleware` - sabe que no debe manejar este mensaje en este momento. Ve que `SentStamp`, se da cuenta de que el mensaje fue enviado a un transporte y, por tanto, no hace nada. Lo manejará más tarde.

## BusNameStamp: Cómo el trabajador envía al bus correcto

¿Pero qué pasa con este `BusNameStamp`? Abramos esa clase. Huh, `BusNameStamp`contiene literalmente... el nombre del bus al que se despachó el mensaje. Si miras en `messenger.yaml`, en la parte superior, tenemos tres buses:`command.bus`, `event.bus` y `query.bus`. Vale, pero ¿qué sentido tiene`BusNameStamp`? Es decir, enviamos el mensaje a través del bus de comandos... entonces, ¿por qué es importante que el mensaje tenga un sello que diga esto?

La respuesta tiene que ver con lo que ocurre cuando un trabajador consume este mensaje. El proceso es así. En primer lugar, el comando `messenger:consume` -que es el "trabajador"- lee un mensaje de una cola. En segundo lugar, el serializador de ese transporte lo convierte en un objeto `Envelope` con un objeto de mensaje en su interior, como nuestro objeto `LogEmoji`. Por último, el trabajador envía ese sobre de vuelta al bus de mensajes. Sí, internamente, ¡algo llama `$messageBus->dispatch($envelope)`!

Espera... pero si tenemos varios buses de mensajes... ¿cómo sabe el trabajador a qué bus de mensajes debe enviar el Sobre? Pues sí Ese es el propósito de este `BusNameStamp`. Messenger añade este sello para que, cuando el trabajador reciba el mensaje, pueda utilizarlo para enviarlo al bus correcto.

Ahora mismo, en nuestro serializador, no estamos añadiendo ningún sello al `Envelope`. Como el sello no existe, el trabajador utiliza el `default_bus`, que es el `command.bus`. Así que, en este caso... ¡adivinó correctamente! Este mensaje es un comando.

## El sello UniqueIdStamp

El último sello que se añadió fue este `UniqueIdStamp`. Es algo que hemos creado... y se añade a través de un middleware personalizado: `AuditMiddleware`. Cada vez que se envía un mensaje, este middleware se asegura de que cada `Envelope`tenga exactamente un `UniqueIdStamp`. Entonces, cualquiera puede utilizar la cadena de identificación única de ese sello para seguir este mensaje exacto a lo largo de todo el proceso.

Espera... así que si esto se añade normalmente cuando enviamos originalmente un mensaje... ¿debemos añadir manualmente el sello dentro de nuestro serializador para que el `Envelope`tenga uno?

Míralo de esta manera: un mensaje normal que se envía desde nuestra aplicación ya tendría este sello en el momento en que se publica en RabbitMQ. Cuando un trabajador lo reciba, estará ahí.

Pero... en este caso, como puedes ver claramente, después de recibir el mensaje externo, no estamos añadiendo ese sello. Entonces, ¿es algo que deberíamos añadir aquí para que esto "actúe" como otros mensajes?

¡Gran pregunta! La respuesta es... ¡no! Comprueba los mensajes del registro: ya puedes ver algunos mensajes con esta cadena `5d7bc`. Ese es el identificador único. ¡Nuestro mensaje sí tiene un `UniqueIdStamp`!

¿Cómo? Recuerda que, después de que nuestro serializador devuelva el `Envelope`, el trabajador lo envía de vuelta a través del bus. Y así, nuestro `AuditMiddleware` es llamado, añade ese sello y luego registra algunos mensajes al respecto.

## Las grandes conclusiones

Para retroceder un poco, hay dos grandes puntos que quiero destacar. En primer lugar, cuando un mensaje se lee y se gestiona a través de un trabajador, se envía a través del bus de mensajes y se ejecuta todo el middleware normal. En el caso de un mensaje que se envía desde nuestra aplicación y que es manejado por ella, pasará dos veces por el middleware.

El segundo punto importante es que cuando consumes un mensaje que fue puesto allí desde un sistema externo, a ese mensaje le pueden faltar algunos sellos que tendría un mensaje normal. Y, en la mayoría de los casos, ¡eso está bien! El `DelayStamp`y el `AmqpStamp` son irrelevantes porque ambos indican al transporte cómo enviar el mensaje.

## Añadir el BusNameStamp

Pero... el `BusNameStamp` es uno de los que quizás quieras añadir. Seguro que Messenger utilizó el bus correcto en este caso por accidente, ¡pero podemos ser más explícitos!

Entra en `ExternalJsonMessengerSerializer`. Cámbialo por`$envelope = new Envelope()` y, al final, devuelve `$envelope`. Añade el sello con `$envelope = $envelope->with()` - así es como se añade un sello -`new BusNameStamp()`.

Entonces... hmm... como nuestro transporte y serializador sólo manejan este único mensaje... y como este único mensaje es un comando, querremos poner el bus de comando aquí. Copia el nombre del bus `command.bus` y pégalo. Añadiré un comentario que diga que esto es técnicamente sólo necesario si necesitas que el mensaje se envíe a través de un bus no predeterminado.

[[[ code('cf1f986c58') ]]]

A continuación, nuestro serializador es genial, pero no hemos codificado de forma muy defensiva. ¿Qué pasaría si el mensaje contuviera un JSON no válido... o le faltara el campo `emoji`? ¿Fallaría nuestra aplicación con gracia... o explotaría?
