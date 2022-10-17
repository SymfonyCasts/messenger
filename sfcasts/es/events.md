# Creación y manejo de eventos

Entonces... ¿qué diablos es un evento? Deja que te ponga un ejemplo. Supón que un usuario se registra en tu sitio web. Cuando eso ocurre, haces tres cosas: guardar al usuario en la base de datos, enviarle un correo electrónico y añadirlo a un sistema de CRM. El código para hacer todo esto podría vivir en un controlador, un servicio o un `SaveRegisteredUserHandler`si tuvieras un comando `SaveRegisteredUser`.

Esto significa que tu servicio -o quizás tu controlador de comandos- está haciendo tres cosas distintas. Eso... no es un gran problema. Pero si de repente necesitas hacer una cuarta cosa, tendrás que añadir aún más código. Tu servicio -o manejador- viola el principio de responsabilidad única que dice que cada función sólo debe realizar una única tarea.

Esto no es el fin del mundo: a menudo escribo código así... y no suele molestarme. Pero este problema de organización del código es exactamente la razón por la que existen los eventos.

La idea es la siguiente: si tienes un manejador de comandos como `SaveRegisteredUser`, se supone que sólo debe realizar su tarea principal: debe guardar el usuario registrado en la base de datos. Si sigues esta práctica, no debería realizar tareas "secundarias", como enviar un correo electrónico al usuario o configurarlo en un sistema CRM. En su lugar, debe realizar la tarea principal y luego enviar un evento, como `UserWasRegistered`. Entonces, tendríamos dos manejadores para ese evento: uno que envía el correo electrónico y otro que configura el usuario en el CRM. El manejador de comandos realiza la "acción" principal y el evento ayuda a otras partes del sistema a "reaccionar" a esa acción.

En lo que respecta a Messenger, los comandos y los eventos son idénticos. La diferencia se reduce a que cada uno soporta un patrón de diseño diferente.

## La tarea secundaria de DeleteImagePostHandler

Y... ¡ya tenemos una situación así! Mira `DeleteImagePost` y luego`DeleteImagePostHandler`. La tarea "principal" de este manejador es eliminar este`ImagePost` de la base de datos. Pero también tiene una segunda tarea: eliminar el archivo subyacente del sistema de archivos.

Para ello, enviamos un segundo comando - `DeletePhotoFile` - y su manejador elimina el archivo. Adivina qué... ¡este es el patrón de eventos! Bueno, es casi el patrón de eventos. La única diferencia es la denominación: `DeletePhotoFile`
suena como un "comando". En lugar de "ordenar" al sistema que haga algo, un evento es más bien un "anuncio" de que algo ha ocurrido.

Para entenderlo bien, retrocedamos y volvamos a implementar todo esto de nuevo. Comenta la llamada a `$messageBus->dispatch()` y luego elimina la declaración de uso de `DeletePhotoFile`en la parte superior 

[[[ code('a6a76aae83') ]]]

A continuación, para tener un comienzo limpio: elimina la propia clase de comando `DeletePhotoFile` y `DeletePhotoFileHandler`. Por último, en `config/packages/messenger.yaml`, dirigimos el comando que acabamos de eliminar. Comenta eso.

[[[ code('dfe1ab5a10') ]]]

Veamos esto con ojos nuevos. Hemos conseguido que `DeleteImagePostHandler`realice únicamente su tarea principal: borrar el `ImagePost`. Y ahora nos preguntamos: ¿dónde debo poner el código para realizar la tarea secundaria de borrar el archivo físico? Podríamos poner esa lógica aquí mismo, o aprovechar un evento.

## Crear el evento

Los comandos, los eventos y sus manejadores son idénticos. En el directorio `src/Message`, para empezar a organizar las cosas un poco mejor, vamos a crear un subdirectorio `Event/`. Dentro, añade una nueva clase: `ImagePostDeletedEvent`.

[[[ code('86fdc32311') ]]]

Fíjate en el nombre de esta clase: es fundamental. Hasta ahora todo ha sonado como una orden: estamos recorriendo nuestra base de código gritando: ¡ `AddPonkaToImage`! y ¡ `DeleteImagePost`! Parecemos mandones.

Pero con los eventos, no estás utilizando un comando estricto, sino que estás notificando al sistema algo que acaba de ocurrir: vamos a eliminar completamente el puesto de la imagen y luego diremos:

> ¡Oye! ¡Acabo de borrar un post de imagen! Si te interesa... eh... ahora es tu oportunidad
> de... eh... ¡hacer algo! Pero no me importa si lo haces o no.

El evento en sí podría ser manejado por... nadie... o podría tener múltiples manejadores. Dentro de la clase, almacenaremos los datos que creamos que pueden ser útiles. Añade un constructor con un `string $filename` - saber el nombre de archivo del`ImagePost` eliminado podría ser útil. Pulsaré Alt + Enter e iré a "Inicializar campos" para crear esa propiedad y establecerla. Luego, en la parte inferior, iré a "Código -> Generar" -o Comando + N en un Mac- y seleccionaré "Obtenedores" para generar este único getter.

[[[ code('07ef42914f') ]]]

Te habrás dado cuenta de que, aparte de su nombre, esta clase "evento" es exactamente igual que el comando que acabamos de eliminar

## Crear el manejador de eventos

La creación de un "manejador" de eventos también es idéntica a la de los manejadores de comandos. En el directorio `MessageHandler`, vamos a crear otro subdirectorio llamado`Event/` para organizarnos. A continuación, añade una nueva clase PHP. Llamémosla`RemoveFileWhenImagePostDeleted`. Pero asegúrate de que lo escribes correctamente.

[[[ code('3d01d91156') ]]]

Esto también sigue una convención de nomenclatura diferente. En el caso de los comandos, si un comando se llamaba `AddPonkaToImage`, llamábamos al manejador `AddPonkaToImageHandler`. La gran diferencia entre los comandos y los eventos es que, mientras que cada comando tiene exactamente un manejador -por lo que usar la convención "nombre del comando Manejador" tiene sentido-, cada evento puede tener varios manejadores.

Pero el interior de un manejador es el mismo: implementa `MessageHandlerInterface`y luego crea nuestro querido `public function __invoke()` con el tipo-indicación de la clase de evento: `ImagePostDeletedEvent $event`.

[[[ code('1383b84b6b') ]]]

Ahora... haremos el trabajo... y esto será idéntico al manejador que acabamos de eliminar. Añade un constructor con el único servicio que necesitamos para eliminar archivos:`PhotoFileManager`. Inicializaré los campos para crear esa propiedad y luego, más abajo, terminaré las cosas con `$this->photoFileManager->deleteImage()` pasando ese`$event->getFilename()`.

[[[ code('6a2cf60847') ]]]

Espero que esto te haya resultado deliciosamente aburrido. Hemos eliminado un comando y un controlador de comandos... y los hemos sustituido por un evento y un controlador de eventos que son... aparte del nombre... ¡idénticos!

A continuación, vamos a enviar este nuevo evento... pero a nuestro bus de eventos. Luego, ajustaremos un poco ese bus para asegurarnos de que funciona perfectamente.
