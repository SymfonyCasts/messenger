# Fallos parciales del manejador y enrutamiento avanzado

Acabamos de dividir nuestro proceso de borrado de imágenes en trozos más pequeños creando una nueva clase de comando, un nuevo manejador y despachando ese nuevo comando desde el manejador Esto... técnicamente no es nada especial, pero es genial ver cómo puedes dividir cada tarea en tantos trozos como necesites.

Pero... asegurémonos de que esto realmente funciona. Todo debería seguir procesándose de forma sincronizada. Elimina la primera imagen y... actualiza para asegurarte. ¡Ya no está!

## Pensando en los fallos y en si los mensajes se despachan

Antes de manejar la nueva clase de comandos de forma asíncrona, tenemos que pensar en algo. Si, por alguna razón, hay un problema al eliminar este `ImagePost` de la base de datos, Doctrine lanzará una excepción aquí mismo y el archivo nunca se eliminará. Eso es perfecto: tanto la fila en la base de datos como el archivo en el sistema de archivos permanecerán.

Pero si el borrado de la fila de la base de datos se realiza con éxito... pero hay un problema al borrar el archivo del sistema de archivos -como un problema de conexión temporal al hablar con S3 si nuestro archivo estuviera almacenado allí-... ese archivo... en realidad... ¡nunca se borrará! Y... quizás no te importe. Pero si te importa, podrías envolver todo este bloque en una transacción Doctrine para asegurarte de que todo es correcto antes de eliminar finalmente la fila. Por supuesto... una vez que cambiemos este mensaje para que se gestione de forma asíncrona, la eliminación del archivo real se hará más tarde... y estaremos, más o menos, "confiando" en que se gestionará con éxito. Hablaremos de los fallos y reintentos muy pronto.

## Enrutar el mensaje de forma asíncrona

De todos modos, ahora que hemos dividido esto en dos partes, dirígete a`config/packages/messenger.yaml`. Copia la línea existente, pégala y dirige la nueva `DeletePhotoFile` a `async`.

[[[ code('06eb9c3cf8') ]]]

¡Genial! Con un poco de suerte, la fila de la base de datos se eliminará inmediatamente... y el archivo unos segundos después.

Y como acabamos de hacer un cambio en el código del manejador, vete, para nuestro trabajador y reinícialo:

```terminal-silent
php bin/console messenger:consume -vv
```

¡Tiempo de prueba! Refresca para estar seguro... y probemos a borrar. ¡Comprueba cuánto más rápido es! Si te acercas al terminal del trabajador... sí, está haciendo todo tipo de cosas buenas aquí. Ah, ¡y divertido! Se ha producido una excepción al manejar uno de los mensajes: no se ha encontrado un archivo. Creo que se debe a la fila duplicada causada por el error de Doctrine de hace unos minutos: el archivo ya había desaparecido cuando se eliminó la segunda imagen. Lo bueno es que ya está reintentando ese mensaje por si fuera un fallo temporal. Al final, se rinde y "rechaza" el mensaje.

¡Vamos a probar juntos este loco sistema! Sube un montón de fotos... y luego... ¡rápido! ¡Borra un par! Si te fijas en el trabajador... está todo muy mezclado: aquí se manejan unos cuantos objetos de `AddPonkaToImage`... y luego `DeletePhotoFile`.

## Enrutamiento con interfaces y clases base

Ah, y por cierto: si miras la sección `routing` en `messenger.yaml`, normalmente enrutarás las cosas por su nombre de clase exacto: `App\Message\AddPonkaToImage`
va a `async`. Pero también puedes enrutar mediante interfaces o clases base. Por ejemplo, si tienes un montón de clases que deben ir al transporte `async`, podrías crear tu propia interfaz -tal vez `AsyncMessageInterface` -, hacer que tus mensajes la implementen, y entonces sólo tendrás que encaminar esa interfaz a `async`aquí. Pero ten cuidado porque, si una clase coincide con varias líneas de enrutamiento, se enviará a todos esos transportes. Ah, y por último: en caso de que tengas un caso de uso, cada entrada de enrutamiento puede enviar a múltiples transportes.

A continuación: ¿recuerdas que el mensaje serializado en la base de datos estaba envuelto en algo llamado `Envelope`? Vamos a aprender qué es eso y cómo su sistema de sellos nos da algunos superpoderes geniales.
