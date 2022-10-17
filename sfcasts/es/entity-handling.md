# Pasar Ids de entidad dentro de los mensajes

Supón que necesitas que tu amigo venga a cuidar a tu perra durante el fin de semana, llamémosla Molly. Así que le escribes un mensaje en el que le explicas todos los detalles que necesita saber: con qué frecuencia debe alimentar a Molly, cuándo debe pasearla, dónde le gusta exactamente que le rasquen detrás de las orejas, tu película de superhéroes favorita y el nombre de tu mejor amigo de la infancia. Espera... esas dos últimas cosas... aunque fascinantes... ¡no tienen nada que ver con la vigilancia de tu perro Molly!

Y esto toca una práctica recomendada para diseñar tus clases de mensajes: haz que contengan todos los detalles que el adiestrador necesita... y nada extra. Esto no es una regla absoluta... sólo hace que sean más delgadas, más pequeñas y más dirigidas.

## Pasar el Id. de la entidad

Si piensas en nuestro mensaje, en realidad no necesitamos todo el objeto `ImagePost`. Lo más pequeño que podríamos pasar es en realidad el id... que luego podríamos utilizar para consultar el objeto `ImagePost` y obtener el nombre del archivo.

Cambia el argumento del constructor por `int $imagePostId`. Lo cambiaré a continuación y ve a Código -> Refactorizar para cambiar el nombre de la propiedad. Ah, y ¡brillante! También cambió el nombre de mi getter a `getImagePostId()`. Actualiza el tipo de retorno para que sea un `int`. Podemos eliminar la antigua declaración `use` como crédito extra.

[[[ code('a60b6a58e9') ]]]

A continuación, en `ImagePostController`, busca `AddPonkaToImage` y... cámbialo por `$imagePost->getId()`.

[[[ code('558e33c398') ]]]

Nuestra clase de mensaje es ahora lo más pequeña posible. Por supuesto, esto significa que tenemos que hacer un poco de trabajo extra en nuestro manejador. En primer lugar, la variable `$imagePost`ya no es... bueno... ¡un `ImagePost`! Cámbiale el nombre a `$imagePostId`.

[[[ code('f45b58107a') ]]]

Para consultar el objeto real, añade un nuevo argumento del constructor:`ImagePostRepository $imagePostRepository`. Pulsaré Alt + Enter -> Inicializar campos para crear esa propiedad y establecerla.

[[[ code('b8876ce6b8') ]]]

De vuelta al método, podemos decir`$imagePost = $this->imagePostRepository->find($imagePostId)`.

[[[ code('c404db1a35') ]]]

Ya está ¡Y esto soluciona nuestro problema con Doctrine! Ahora que estamos consultando la entidad, cuando llamemos a `flush()`, la guardará correctamente con un `UPDATE`. Podemos eliminar la llamada a `persist()` porque no es necesaria para las actualizaciones.

¡Vamos a probarlo! Como acabamos de cambiar el código en nuestro manejador, pulsa Ctrl+C para detener nuestro trabajador y luego reinícialo:

```terminal-silent
php bin/console messenger:consume -vv
```

¡Ya está! Sube un nuevo archivo... comprueba el trabajador -sí, se ha procesado bien- y... ¡actualiza! ¡Sí! ¡No hay duplicación, Ponka está visitando mi taller y la fecha está fijada!

## Fallando con gracia

Pero... siento dar malas noticias... ¿qué pasa si no se puede encontrar el `ImagePost` para este `$imagePostId`? Eso no debería ocurrir... pero dependiendo de tu aplicación, ¡podría ser posible! Para nosotros... ¡lo es! Si un usuario sube una foto y luego la borra antes de que el trabajador pueda gestionarla, ¡el `ImagePost` desaparecerá!

¿Es realmente un problema? Si el `ImagePost` ya se ha borrado, ¿nos importa que este manipulador explote? Probablemente no... siempre que hayas pensado en cómo va a explotar y sea intencionado.

Fíjate en esto: empecemos diciendo: `if (!$imagePost)` para poder hacer un manejo especial... en lugar de intentar llamar a `getFilename()` sobre null aquí abajo. Si esto ocurre, sabemos que probablemente sea sólo porque la imagen ya se ha borrado. Pero... como odio las sorpresas en producción, vamos a registrar un mensaje para que sepamos que esto ha ocurrido... por si acaso se debe a un error en nuestro código.

## Inyección de Logger con LoggerAwareInterface

A partir de Symfony 4.2, hay un pequeño atajo para conseguir el servicio principal `logger`. Primero, haz que tu servicio implemente `LoggerAwareInterface`. Luego, utiliza un rasgo llamado `LoggerAwareTrait`.

[[[ code('c74e8d7cea') ]]]

Y ya está Vamos a echar un vistazo al interior de `LoggerAwareTrait`. Muy bien. En el núcleo de Symfony, hay un poco de código que dice

> siempre que veas un servicio de usuario que implemente `LoggerAwareInterface`,
> llama automáticamente a `setLogger()` sobre él y pasa el logger.

Al combinar la interfaz con este rasgo... ¡no tenemos que hacer nada! Al instante tenemos una propiedad `$logger` que podemos utilizar.

## Cómo fallar en tu manejador

Bien, volviendo a nuestra sentencia if... ¿qué debemos hacer si no se encuentra el `ImagePost`? Tenemos dos opciones... y la elección correcta depende de la situación. En primer lugar, podríamos lanzar una excepción -cualquier excepción- y eso haría que este mensaje se reintentara. Pronto habrá más reintentos. O bien, podrías simplemente "devolver" y este mensaje "parecerá" que se ha gestionado con éxito... y se eliminará de la cola.

Volvamos: no tiene sentido reintentar este mensaje más tarde... ¡ese `ImagePost`se ha ido! 

[[[ code('0bef79d717') ]]]

Pero también registremos un mensaje: si `$this->logger`, entonces `$this->logger->alert()` con, qué tal,

> ¡Falta la imagen del puesto %d!

pasando `$imagePostId` por el comodín 

[[[ code('0d389e6cb2') ]]]

Ah, y la única razón por la que compruebo si `$this->logger` está activado es... 
básicamente... para ayudar en las pruebas unitarias. Dentro de Symfony, la propiedad `logger` siempre estará establecida. Pero a nivel orientado a objetos, no hay nada que garantice que alguien haya llamado a `setLogger()`... así que esto es un poco más responsable.

## Testigo de errores en tu manejador

¡Vamos a probar esta cosa! ¡Veamos qué ocurre si borramos un `ImagePost` antes de que se procese! Primero, muévete, detén el manipulador y reinícialo:

```terminal-silent
php bin/console messenger:consume -vv
```

Y como cada mensaje tarda unos segundos en procesarse, si subimos un montón de fotos... y las borramos súper rápido... con un poco de suerte, borraremos una antes de que se procese su mensaje.

¡Veamos si ha funcionado! Así que... algunas sí se procesaron con éxito. Pero... ¡sí! ¡Este tiene una alerta! Y gracias al "retorno" que añadimos, fue "reconocido"... lo que significa que fue eliminado de la cola.

Ah... e interesante... hay otro error que no había previsto a continuación

> Se produjo una excepción al manejar el mensaje AddPonkaToImage: Archivo no
> encontrado en la ruta...

¡Esto es increíble! Esto es lo que parece si, por cualquier motivo, se lanza una excepción en tu manejador. Al parecer, el `ImagePost` se encontró en la base de datos... pero cuando intentó leer el archivo en el sistema de archivos, ¡se había eliminado!

Lo realmente sorprendente es que Messenger vio este fallo y volvió a intentar automáticamente el mensaje una segunda... y luego una tercera vez. Hablaremos más sobre los fallos y los reintentos un poco más tarde.

Pero antes, nuestro mensaje `DeleteImagePost` se sigue gestionando de forma sincrónica. ¿Podríamos hacerlo asíncrono? Bueno... ¡no! Necesitamos que el `ImagePost` se elimine de la base de datos inmediatamente para que el usuario no lo vea si actualiza. A menos que... podamos dividir la tarea de eliminación en dos partes... ¡Vamos a intentarlo a continuación!
