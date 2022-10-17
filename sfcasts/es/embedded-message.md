# ¿Despachar un mensaje dentro de un manejador?

La eliminación de una imagen se sigue haciendo de forma sincrónica. Puedes verlo: como lo he hecho extra lento para conseguir un efecto dramático, tarda un par de segundos en procesarse antes de desaparecer. Por supuesto, podríamos evitarlo haciendo que nuestro JavaScript elimine la imagen visualmente antes de que termine la llamada AJAX. Pero hacer que las cosas pesadas sean asíncronas es una buena práctica y podría permitirnos poner menos carga en el servidor web.

Veamos el estado actual de las cosas: hemos actualizado todo esto para que lo maneje nuestro bus de comandos: tenemos un comando `DeleteImagePost` y`DeleteImagePostHandler`. Pero dentro de `config/packages/messenger.yaml`, no estamos enrutando esta clase a ninguna parte, lo que significa que se está manejando inmediatamente.

Ah, y fíjate: seguimos pasando todo el objeto entidad al mensaje. En los dos últimos capítulos, hablamos de evitar esto como mejor práctica y porque puede causar cosas raras si manejas esto de forma asíncrona.

Pero... si piensas mantener `DeleteImagePost` de forma sincrónica... depende de ti: pasar el objeto entidad completo no perjudica nada. Y... realmente... ¡necesitamos que este mensaje se gestione de forma sincrónica! Necesitamos que el `ImagePost` se borre de la base de datos inmediatamente para que, si el usuario actualiza, la imagen desaparezca.

Pero, fíjate bien: el borrado implica dos pasos: eliminar una fila en la base de datos y eliminar el archivo de imagen subyacente. Y... sólo es necesario que se produzca el primer paso en este momento. Si eliminamos el archivo en el sistema de archivos más tarde... ¡no pasa nada!

## Dividir en un nuevo Comando+Handler

Para hacer parte del trabajo de forma sincronizada y la otra parte de forma asincrónica, mi enfoque preferido es dividir esto en dos comandos.

Crea una nueva clase de comando llamada `DeletePhotoFile`. Dentro, añade un constructor para que podamos pasar la información que necesitemos. Esta clase de comando se utilizará para eliminar físicamente el archivo del sistema de archivos. Y si te fijas en el manejador, para hacerlo, sólo necesitamos el servicio `PhotoFileManager` y la cadena nombre de archivo.

Así que esta vez, la menor cantidad de información que podemos poner en la clase de comando es`string $filename`

[[[ code('9651ebf913') ]]]

Pulsaré Alt + intro e iré a "Inicializar campos" para crear esa propiedad y establecerla 

[[[ code('5b6f629b2c') ]]]

Ahora iré a Código -> Generar -o Cmd+N en un Mac- para generar el getter.

[[[ code('0356749485') ]]]

¡Genial! Paso 2: añade el manejador `DeletePhotoFileHandler`. Haz que éste siga las dos reglas de los manejadores: implementa `MessageHandlerInterface` y crea un método `__invoke()`con un argumento que sea de tipo con la clase de mensaje:`DeletePhotoFile $deletePhotoFile`.

[[[ code('cf2005303f') ]]]

Perfecto Lo único que tenemos que hacer aquí es... esta línea:`$this->photoManager->deleteImage()`. Cópiala y pégala en nuestro manejador. Para el argumento, podemos utilizar nuestra clase de mensaje: `$deletePhotoFile->getFilename()`.

[[[ code('f91a797e9b') ]]]

Y por último, necesitamos el servicio `PhotoFileManager`: añade un constructor con un argumento: `PhotoFileManager $photoManager`. Utilizaré mi truco de Alt+Enter -> Inicializar campos para crear esa propiedad como siempre.

[[[ code('745ec700b2') ]]]

Ya está Ahora tenemos una clase de comando funcional que requiere la cadena nombre de archivo, y un manejador que lee ese nombre de archivo y... ¡hace el trabajo!

## Despachar el incrustado

Todo lo que tenemos que hacer ahora es despachar el nuevo comando. Y... técnicamente podríamos hacerlo en dos lugares diferentes. En primer lugar, podrías pensar que, en`ImagePostController`, podríamos enviar dos comandos diferentes aquí mismo.

Pero... Eso no me gusta. El controlador ya está diciendo `DeleteImagePost`. No debería necesitar emitir ningún otro comando. Si decidimos dividir esa lógica en trozos más pequeños, eso depende del controlador. En otras palabras, vamos a despachar este nuevo comando desde el manejador de comandos. ¡Inicio!

En lugar de llamar directamente a `$this->photoManager->deleteImage()`, cambia la sugerencia de tipo de ese argumento a autowire `MessageBusInterface $messageBus`. Actualiza el código en el constructor... y el nombre de la propiedad.

[[[ code('288755011c') ]]]

Ahora, fácil: elimina el código antiguo y empieza con:`$filename = $imagePost->getFilename()`. Luego, eliminémoslo de la base de datos y, al final, `$this->messageBus->dispatch(new DeletePhotoFile($filename))`.

[[[ code('fb8f83c8a0') ]]]

Y... esto debería... funcionar: todo se sigue gestionando de forma sincrónica.

Probemos a continuación, pensemos un poco en lo que ocurre si falla parte de un manejador, y hagamos que la mitad del proceso de borrado sea asíncrono.
