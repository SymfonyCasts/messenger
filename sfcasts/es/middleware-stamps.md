# Seguimiento de los mensajes con el middleware y un sello

De alguna manera queremos adjuntar un identificador único -una cadena cualquiera- que permanezca con el mensaje para siempre: tanto si se gestiona inmediatamente, como si se envía a un transporte, o incluso se vuelve a intentar varias veces.

## Crear un sello

¿Cómo podemos adjuntar más... "cosas" adicionales a un mensaje? Dándole nuestro propio sello! En el directorio `Messenger/`, crea una nueva clase PHP llamada `UniqueIdStamp`. Los sellos también tienen una sola regla: implementan`MessengerEnvelopeMetadataAwareContainerReaderInterface`. No, estoy bromeando, ese sería un nombre tonto. Sólo tienen que implementar `StampInterface`.

[[[ code('3e253f2210') ]]]

Y... ¡eso es todo! Se trata de una interfaz vacía que sólo sirve para "marcar" objetos como sellos. Dentro... podemos hacer lo que queramos... siempre que PHP pueda serializar este mensaje... lo que básicamente significa: siempre que contenga datos simples. Añadamos una propiedad `private $uniqueId`, y luego un constructor sin argumentos. Dentro, digamos`$this->uniqueId = uniqid()`. En la parte inferior, ve a Código -> Generar -o Comando+N en un Mac- y genera el getter... que devolverá un `string`.

[[[ code('cfcc1c6ab8') ]]]

Sello, ¡hecho!

## Estampando... um... Adjuntar el Sello

A continuación, dentro de `AuditMiddleware`, antes de llamar al siguiente middleware -que llamará al resto del middleware y, en última instancia, manejará o enviará el mensaje- vamos a añadir el sello.

Pero, cuidado: tenemos que asegurarnos de que sólo adjuntamos el sello una vez. Como veremos dentro de un minuto, un mensaje puede pasar al bus -y, por tanto, al middleware- ¡muchas veces! Una vez cuando se envía inicialmente y otra cuando se recibe del transporte y se maneja. Si el manejo de ese mensaje falla y se vuelve a intentar, pasaría por el bus aún más veces.

Por tanto, empieza por comprobar si `null === $envelope->last(UniqueIdStamp::class)`, y luego`$envelope = $envelope->with(new UniqueIdStamp())`.

[[[ code('36818753c4') ]]]

## Los sobres son inmutables

Aquí hay algunas cosas interesantes. En primer lugar, cada `Envelope` es "inmutable", lo que significa que, sólo por la forma en que se escribió esa clase, no puedes cambiar ningún dato en ella. Cuando llamas a `$envelope->with()` para añadir un nuevo sello, en realidad no modifica el `Envelope`. No, internamente, hace un clon de sí mismo más el nuevo sello.

Eso... no es muy importante, salvo que tienes que acordarte de decir`$envelope = $envelope->with()` para que `$envelope` se convierta en el nuevo objeto estampado.

## Obtención de sellos

Además, en lo que respecta a los sellos, un `Envelope` podría, en teoría, contener varios sellos de la misma clase. El método `$envelope->last()` dice:

> Dame el más reciente añadido `UniqueIdStamp` o null si no hay ninguno.

## Volcar el Id. único

Gracias a nuestro trabajo, debajo de la sentencia if -independientemente de si este mensaje se acaba de enviar... o se acaba de recibir de un transporte... o se está reintentando- nuestro `Envelope` debería tener exactamente un `UniqueIdStamp`. Recógelo con`$stamp = $envelope->last(UniqueIdStamp::class)`. También voy a añadir una pequeña pista a mi editor para que sepa que esto es específicamente un `UniqueIdStamp`.

[[[ code('7de893d64e') ]]]

Para ver si esto funciona, vamos a `dump($stamp->getUniqueId())`.

[[[ code('b3069963f6') ]]]

¡Vamos a probarlo! Si hemos hecho bien nuestro trabajo, para un mensaje asíncrono, ese `dump()`se ejecutará una vez cuando se envíe el mensaje y otra vez dentro del trabajador cuando se reciba del transporte y se gestione.

Actualiza la página para estar seguro, y luego sube una imagen. Para ver si nuestro `dump()` ha sido alcanzado, utilizaré el enlace de la barra de herramientas de depuración de la web para abrir el perfilador de esa petición. Haz clic en "Depuración" a la izquierda y... ¡ahí está! ¡Nuestro identificador único! Dentro de unos minutos, nos aseguraremos de que este código también se ejecute en el trabajador.

Y como el middleware se ejecuta para cada mensaje, también deberíamos poder verlo al borrar un mensaje. Haz clic en eso, luego abre el perfilador de la petición DELETE y haz clic en "Depurar". ¡Ja! Esta vez hay dos identificadores únicos distintos porque al borrar se envían dos mensajes diferentes.

A continuación, ¡vamos a hacer algo útil con esto! Dentro de nuestro middleware, vamos a registrar todo el ciclo de vida de un solo mensaje: cuando se envía originalmente, cuando se envía a un transporte y cuando se recibe de un transporte y se gestiona. Para saber en qué parte del proceso se encuentra el mensaje, vamos a utilizar de nuevo los sellos. Pero en lugar de crear nuevos sellos, leeremos los sellos del núcleo.
