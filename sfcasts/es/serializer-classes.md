# Asignación de mensajes a clases en un serializador de transporte

Hemos escrito nuestro serializador de transporte para que siempre espere que se ponga en la cola un solo tipo de mensaje: un mensaje que indique a nuestra app que "registre un emoji". Puede que tu aplicación sea así de sencilla, pero es más probable que este sistema "externo" envíe 5 o 10 tipos diferentes de mensajes. En ese caso, nuestro serializador tiene que detectar de qué tipo de mensaje se trata y convertirlo en el objeto mensaje correcto.

¿Cómo podemos hacerlo? ¿Cómo podemos averiguar de qué tipo de mensaje se trata? ¿Nos limitamos a mirar qué campos tiene el JSON? Podríamos... pero también podemos hacer algo más inteligente.

## Refactorización a un interruptor

Empecemos por reorganizar un poco esta clase. Selecciona el código de la parte inferior de este método -lo relacionado con el objeto `LogEmoji` - y luego ve al menú Refactorizar -> "Refactorizar esto", que es Ctrl+T en un Mac. Refactoriza este código a un método llamado `createLogEmojiEnvelope`.

***TIP
Para que los "reintentos" funcionen correctamente, se ha modificado parte del código de esta sección. Consulta los bloques de código de esta página para ver los ejemplos actualizados
***

[[[ code('6d7aa90802') ]]]

¡Genial! Eso creó una función privada aquí abajo con ese código. Añadiré una pista de tipo`array`. En `decode()`, ya estamos llamando a este método. Así que no hay grandes cambios.

[[[ code('77b1b16602') ]]]

## Utilizar cabeceras para el tipo

La pregunta clave es: si se añaden varios tipos de mensajes a la cola, ¿cómo puede el serializador determinar de qué tipo de mensaje se trata? Bueno, podríamos añadir quizás una clave `type` al propio JSON. Eso podría estar bien. Pero hay otro punto en el mensaje que puede contener datos: las cabeceras. Éstas funcionan de forma muy parecida a las cabeceras HTTP: son información "extra" que puedes almacenar sobre el mensaje. Cualquier cabecera que pongamos aquí llegará a nuestro serializador cuando se consuma.

Vale, pues añadamos una nueva cabecera llamada `type` con el nombre de `emoji`. Me lo acabo de inventar. No voy a hacer de esto un nombre de clase... porque ese sistema externo no sabrá ni le importará qué clases de PHP usamos internamente para manejar esto. Sólo dice:

> Este es un mensaje de tipo "emoji"

De vuelta a nuestro serializador, vamos a comprobar primero que esa cabecera está establecida: si no es `isset($headers['type'])`, entonces lanza un nuevo `MessageDecodingFailedException`con:

> Falta la cabecera "tipo"

[[[ code('851d7d57c9') ]]]

A continuación, aquí abajo, utilizaremos una buena y anticuada sentencia switch case en`$headers['type']`. Si éste es `emoji`, devuelve`$this->createLogEmojiEnvelope()`.

[[[ code('683de1c4b6') ]]]

Después de esto, añadirías cualquier otro "tipo" que el sistema externo publique, como `delete_photo`. En esos casos, instanciarías un objeto de mensaje diferente y lo devolverías. Y, si se pasa algún "tipo" inesperado, lanzamos un nuevo `MessageDecodingFailedException` con

> Tipo inválido "%s"

pasando `$headers['type']` como comodín.

[[[ code('e8fa843716') ]]]

***TIP
Para soportar los reintentos en caso de fallo, también tienes que volver a añadir la cabecera "tipo" dentro de `encode()`[[[ code('31a5d60cb1') ]]]
***

Un poco genial, ¿verdad? Vamos a detener nuestro trabajador y a reiniciarlo para que vea nuestro nuevo código:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

De nuevo en el gestor de Rabbit, cambiaré la clave `emojis` de nuevo a `emoji` y... ¡publicar! En el terminal... ¡qué bien! ¡Ha funcionado! Ahora cambia la cabecera `type` por algo que no soportamos, como `photo`. Publica y... ¡sí! Una excepción mató a nuestro trabajador:

> Tipo inválido "foto".

Vale amigos... sí... ¡ya está! ¡Enhorabuena por haber llegado hasta el final! ¡Espero que hayas disfrutado del viaje tanto como yo! Manejar mensajes de forma asíncrona... es algo muy divertido. Lo mejor de Messenger es que funciona estupendamente desde el principio con un único bus de mensajes y el transporte Doctrine. O puedes volverte loco: crear múltiples transportes, enviar cosas a RabbitMQ, crear intercambios personalizados con claves de enlace o utilizar tu propio serializador para... bueno... básicamente hacer lo que quieras. El poder... es... ¡intoxicante!

Así que, empieza a escribir un código de manejador loco y luego... ¡maneja ese trabajo más tarde! Y haznos saber lo que estás construyendo. Como siempre, si tienes alguna pregunta, estamos a tu disposición en los comentarios.

Muy bien amigos, ¡hasta la próxima!
