# Reintentar en caso de fallo

Cuando empiezas a manejar cosas de forma asíncrona, pensar en lo que ocurre cuando el código falla es aún más importante ¿Por qué? Bueno, cuando manejas las cosas de forma sincrónica, si algo falla, normalmente falla todo el proceso, no sólo la mitad. O, al menos, puedes hacer que todo el proceso falle si lo necesitas.

Por ejemplo: imagina que todo nuestro código sigue siendo síncrono: guardamos el `ImagePost`en la base de datos, pero luego, aquí abajo, falla la adición de Ponka a la imagen... porque está durmiendo la siesta. En este momento, eso supondría la mitad del trabajo realizado... lo que, dependiendo de lo sensible que sea tu aplicación, puede o no ser un gran problema. Si lo es, puedes resolverlo envolviendo todo esto en una transacción de base de datos.

Pensar en cómo van a fallar las cosas -y codificar a la defensiva cuando lo necesites- es simplemente una práctica de programación saludable.

## La dificultad de los fallos asíncronos

Pero todo esto cambia cuando el código es asíncrono Piénsalo: guardamos el `ImagePost`en la base de datos, se envía `AddPonkaToImage` al transporte y se devuelve la respuesta con éxito. Luego, unos segundos después, nuestro trabajador procesa ese mensaje y, debido a un problema temporal de la red, ¡el manejador lanza una excepción!

Esto no es una buena situación. El usuario piensa que todo ha ido bien porque no ha visto un error. Y ahora tenemos un `ImagePost` en la base de datos... pero Ponka nunca se añadirá a él. Ponka está furioso.

La cuestión es: cuando se envía un mensaje a un transporte, tenemos que asegurarnos de que el mensaje se procesa finalmente. Si no lo es, podría dar lugar a algunas condiciones extrañas en nuestro sistema.

## Vigilando los fallos

Así que empecemos a hacer que nuestro código falle para ver qué ocurre Dentro de`AddPonkaToImageHandler`, justo antes de que se ejecute el código real, digamos que si `rand(0, 10) < 7`, entonces lanza un `new \Exception()` con:

¡¡¡¡> He fallado aleatoriamente!!!!

[[[ code('a0b5db52f2') ]]]

¡Veamos qué ocurre! Primero, ve a reiniciar el trabajador:

```terminal-silent
php bin/console messenger:consume -vv
```

Luego despejaré la pantalla y... ¡a cargar! ¿Qué tal cinco fotos? ¡Vuelve a ver lo que pasa! ¡Whoa! Están pasando muchas cosas. Vamos a desmontar esto.

El primer mensaje se recibió y se gestionó. El segundo mensaje se recibió y también se gestionó con éxito. El tercer mensaje se recibió, pero se produjo una excepción al manejarlo: "¡Fallo aleatorio!". Luego dice: "Reintento - reintento nº 1" seguido de "Enviando mensaje". Sí, como ha fallado, Messenger lo "reintenta" automáticamente... ¡lo que significa literalmente que devuelve ese mensaje a la cola para que se procese más tarde! Uno de estos registros de "Mensaje recibido" de aquí abajo es en realidad ese mensaje que se recibe por segunda vez, gracias al reintento. Lo bueno es que... ¡al final todos los mensajes se gestionaron con éxito! Por eso los reintentos molan. Podemos ver esto cuando refrescamos: todos tienen una foto de Ponka... aunque algunos hayan fallado al principio.

## Alcanzando el máximo de 3 reintentos

Pero... vamos a intentarlo de nuevo... porque ese ejemplo no mostraba el caso más interesante. Esta vez voy a seleccionar todas las fotos... oh, pero antes, vamos a limpiar la pantalla de nuestro terminal de trabajador. Vale, sube, entonces... muévete.

Allá vamos: esta vez... gracias a la aleatoriedad, vemos muchos más fallos. Vemos que un par de mensajes fallaron y se enviaron para el reintento nº 1. Luego, algunos de esos mensajes volvieron a fallar y se enviaron para el reintento nº 2. Y... ¡sí! Volvieron a fallar y se enviaron para el reintento nº 3. Finalmente... oh sí, perfecto: después de intentarlo una vez y reintentarlo 3 veces más, uno de los mensajes siguió fallando. Esta vez, en lugar de enviarse para el reintento nº 4, dice

> Rechazando AddPonkaToImage (eliminando del transporte)

Esto es lo que ocurre: por defecto, Messenger reintenta un mensaje tres veces, y si sigue fallando, finalmente lo elimina del transporte y el mensaje se pierde definitivamente. Bueno... eso no es del todo cierto... y aquí pasa algo más de lo que parece a primera vista.

Además, si te fijas bien... estos reintentos se retrasan cada vez más. Vamos a aprender por qué y cómo tomar el control total sobre cómo se reintentan tus mensajes.
