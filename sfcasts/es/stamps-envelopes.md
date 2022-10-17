# Sobres y sellos

Acabamos de recibir una petición de la propia Ponka... y cuando se trata de este sitio, Ponka es la jefa. Ella cree que, cuando un usuario sube una foto, su imagen se añade demasiado rápido. Quiere que tarde más tiempo: quiere que parezca que está haciendo un trabajo realmente épico entre bastidores para entrar en tu foto.

Lo sé, es un ejemplo un poco tonto: Ponka es muy rara cuando hablas con ella antes de su desayuno de gambas y su siesta matutina. Pero... es un reto interesante: ¿podríamos de alguna manera no sólo decir "manipula esto más tarde"... sino también "espera al menos 5 segundos antes de manipularlo".

## El sobre: Un gran lugar para poner un mensaje

¡Sí! Y toca algunas partes súper chulas del sistema llamadas sellos y sobres. Primero, abre `ImagePostController` y sube hasta donde creamos el objeto`AddPonkaToImage`. `AddPonkaToImage` se llama "mensaje", eso lo sabemos. Lo que no sabemos es que, cuando pasas tu mensaje al bus, internamente, se envuelve dentro de algo llamado `Envelope`.

Ahora bien, esto no es un detalle especialmente importante, salvo que, si tienes un `Envelope`, puedes adjuntarle una configuración adicional mediante sellos. Así que sí, literalmente metes un mensaje en un sobre y luego le adjuntas sellos. ¿Es este tu componente favorito o qué?

De todos modos, esos sellos pueden llevar todo tipo de información. Por ejemplo, si usas RabbitMQ, puedes configurar algunas cosas sobre cómo se entrega el mensaje, como algo llamado "clave de enrutamiento". O puedes configurar un retraso.

## Pon el mensaje en el sobre, y luego añade sellos

Comprueba esto: di `$envelope = new Envelope()` y pásale nuestro `$message`. Luego, pásale un segundo argumento opcional: una matriz de sellos 

[[[ code('379ae29dbb') ]]]

Incluye sólo uno: `new DelayStamp(5000)`. Esto indica al transporte... que es algo así como el cartero... que quieres que este mensaje se retrase 5 segundos antes de ser entregado. Por último, pasa el `$envelope` -no el mensaje- a `$messageBus->dispatch()`.

[[[ code('dc09d06bda') ]]]

Sí, el método `dispatch()` acepta objetos de mensaje sin procesar u objetos `Envelope`. Si pasas un mensaje sin procesar, lo envuelve en un `Envelope`. Si pasas un`Envelope`, ¡lo utiliza! El resultado final es el mismo que antes... salvo que ahora aplicamos un `DelayStamp`.

¡Vamos a probarlo! Esta vez no necesitamos reiniciar nuestro trabajador porque no hemos cambiado ningún código que vaya a utilizar: sólo hemos cambiado el código que controla cómo se entregará el mensaje. Pero... si alguna vez no estás seguro, reinícialo.

Borraré la consola para que podamos ver lo que ocurre. Entonces... vamos a subir tres fotos y... un, dos, tres, cuatro ¡ahí está! Se retrasó 5 segundos y luego empezó a procesar cada una de ellas con normalidad. No hay un retraso de 5 segundos entre el tratamiento de cada mensaje: sólo se asegura de que cada mensaje se trate no antes de 5 segundos después de enviarlo.

***TIP
La compatibilidad con los retrasos en Redis se añadió en Symfony 4.4.
***

Nota al margen: En Symfony 4.3, el transporte Redis no admite retrasos, pero es posible que se añada en el futuro.

## ¿Qué otros sellos hay?

En cualquier caso, puede que no utilices mucho los sellos, pero los necesitarás de vez en cuando. Probablemente busques en Google "Cómo configuro los grupos de validación en Messenger" y aprendas qué sello controla esto. No te preocupes, ya hablaré de la validación más adelante, no es algo que ocurra ahora mismo.

Otra cosa interesante es que, internamente, el propio Messenger utiliza sellos para rastrear y ayudar a entregar los mensajes correctamente. Comprueba esto: envuelve `$messageBus->dispatch()`en una llamada a `dump()`.

[[[ code('4c348a2b31') ]]]

Vamos a subir una nueva imagen. A continuación, en la barra de herramientas de depuración de la web, busca la petición AJAX que acaba de terminar -será la de abajo-, haz clic para abrir su perfil y luego haz clic en "Depurar" a la izquierda. ¡Ahí lo tienes! El método `dispatch()` devuelve un `Envelope`... que contiene el mensaje, por supuesto... ¡y ahora tiene cuatro sellos! Tiene el `DelayStamp` como esperábamos, pero también un `BusNameStamp`, que registra el nombre del bus al que se envió. Esto es genial: ahora sólo tenemos un bus, pero puedes tener varios, y hablaremos de por qué podrías hacerlo más adelante. El `BusNameStamp` ayuda al comando trabajador a saber a qué bus debe enviar el `Envelope` una vez leído del transporte.

Ese `SentStamp` es básicamente un marcador que dice "este mensaje fue enviado a un transporte en lugar de ser manejado inmediatamente" y este `TransportMessageIdStamp`, contiene literalmente el id de la nueva fila en la tabla `messenger_messages`... por si es útil.

En realidad, no necesitas preocuparte por nada de esto, pero observar qué sellos se añaden a tu `Envelope` puede ayudarte a depurar un problema o a hacer cosas más avanzadas. De hecho, algunos de ellos serán útiles pronto cuando hablemos del middleware.

Por ahora, elimina el `dump()` y luego, para no volverme loco con lo lento que es esto, cambia el `DelayStamp` a 500 milisegundos. Shh, no se lo digas a Ponka. Después de este cambio... ¡sí! El mensaje se gestiona casi inmediatamente.

[[[ code('a8c5b2521d') ]]]

A continuación, hablemos de los reintentos y de lo que ocurre cuando las cosas van mal No es una broma: estas cosas son superchulas.
