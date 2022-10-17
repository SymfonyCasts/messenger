# Mensaje, manejador y depuración:messenger

Nuestra aplicación tiene otro pequeño superpoder. Si por alguna razón no estás contento con tu imagen Ponka... Puedes eliminarla. Cuando haces clic en ese botón, se envía una petición AJAX que llega a esta acción`delete()`.

Y... eso hace realmente dos cosas. En primer lugar, `$photoManager->deleteImage()` se encarga de eliminar físicamente la imagen del sistema de archivos. He añadido un `sleep()`para conseguir un efecto dramático, pero borrar algo del sistema de archivos podría ser un poco pesado si los archivos estuvieran almacenados en la nube, como en S3.

Y en segundo lugar, el controlador borra el `ImagePost` de la base de datos. Pero... pensando en estos dos pasos... lo único que tenemos que hacer inmediatamente es borrar la imagen de la base de datos. Si sólo hiciéramos eso y el usuario refrescara la página, ya no estaría. Y luego... si elimináramos el archivo real unos segundos... o minutos o incluso días después... ¡estaría totalmente bien! Pero... más sobre cómo hacer cosas asíncronas en unos minutos.

## Creación de DeleteImagePost

Ahora vamos a refactorizar toda esta lógica de borrado en el patrón del bus de comandos que acabamos de aprender. En primer lugar, necesitamos la clase de mensaje o "comando". Copiemos`AddPonkaToImage`, peguémosla y llamémosla `DeleteImagePost.php`. Actualiza el nombre de la clase y luego... ¡no hagas nada! Casualmente, esta clase de mensaje será exactamente igual: el manipulador necesitará saber qué `ImagePost` debe eliminar.

[[[ code('36487e6995') ]]]

## Creación de DeleteImagePostHandler

Es hora del paso 2: ¡el manejador! Crea una nueva clase PHP y llámala`DeleteImagePostHandler`. Al igual que antes, dale un `public function __invoke()`con un tipo-indicación `DeleteImagePost` como único argumento.

[[[ code('6e39ab5f03') ]]]

Ahora, es el mismo proceso que antes: copia las tres primeras líneas del controlador, bórralas y pégalas en el manejador. Esta vez, necesitamos dos servicios 

[[[ code('b794098fc1') ]]]

Añade `public function __construct()` con `PhotoFileManager $photoManager` y`EntityManagerInterface $entityManager`. Pulsaré Alt + Enter y haré clic en inicializar campos para crear ambas propiedades y establecerlas.

[[[ code('f045313514') ]]]

Aquí abajo, utiliza `$this->photoManager`, `$this->entityManager` y uno más`$this->entityManager`. Y, como antes, necesitamos saber qué `ImagePost` vamos a borrar. Prepáralo con `$imagePost = $deleteImagePost->getImagePost()`.

[[[ code('94349efc49') ]]]

## Enviando el mensaje

¡Ding! Ese es mi... ¡sonido de que está hecho! Porque, tenemos un mensaje, un manejador y Symfony debe saber que están unidos. El último paso es enviar el mensaje. En el controlador... ya no necesitamos estos dos últimos argumentos... sólo necesitamos `MessageBusInterface $messageBus`. Y entonces, esto es maravilloso, todo nuestro controlador es: `$messageBus->dispatch(new DeleteImagePost($imagePost))`.

[[[ code('07378e449c') ]]]

Muy bonito, ¿verdad? Vamos a ver si todo funciona. Muévete, haz clic en la "x" y... hmm... no ha desaparecido. Y... ¡parece que era un error 500! Gracias al poder del perfilador, podemos hacer clic en el pequeño enlace para saltar directamente a una versión grande, bonita y en HTML de esa excepción. Interesante:

## Bus de comandos: Cada mensaje debe tener un manejador

> No hay manejador para el mensaje `App\Message\DeleteImagePost`

Esto es interesante. Antes de averiguar qué ha fallado, quiero mencionar una cosa: en un bus de comandos, cada mensaje suele tener exactamente un manejador: no dos ni cero. Y por eso Messenger nos da un útil error si no encuentra ese manejador. Hablaremos más de esto más adelante y flexibilizaremos estas reglas cuando hablemos de los buses de eventos.

## Depurar el manejador que falta

De todos modos... ¿por qué Messenger cree que `DeleteImagePost` no tiene un manejador? ¿No puede ver la clase `DeleteImagePostHandler`? Busca tu terminal y ejecuta:

```terminal
php bin/console debug:messenger
```

¡Woh! ¡Sólo ve nuestra única clase manejadora! Lo que realmente hace este comando es lo siguiente: encuentra todas las clases "manejadoras" del sistema, y luego imprime el "mensaje" que maneja junto a él. Así que... ¡esto confirma que, por alguna razón, Messenger no ve nuestro manejador!

Y... ¡puede que hayas detectado mi error! Para encontrar todos los manejadores, Symfony busca en el directorio `src/` las clases que implementan `MessageHandlerInterface`. Y... ¡me olvidé de esa parte! Añade `implements MessageHandlerInterface`.

[[[ code('b783fdcfeb') ]]]

Ejecuta de nuevo `debug:messenger`:

```terminal-silent
php bin/console debug:messenger
```

¡Ahora lo ve! Probemos de nuevo: cierra el perfilador, prueba a darle a la "x" y... ¡esta vez funciona!

Informe de estado: tenemos dos mensajes y cada uno tiene un manejador que potencialmente está haciendo un trabajo bastante pesado, como la manipulación de imágenes o hablar a través de una red si los archivos se almacenan en la nube. Es hora de hablar de los transportes: el concepto clave para tomar este trabajo y hacerlo de forma asíncrona, de modo que nuestros usuarios no tengan que esperar a que todo ese trabajo pesado termine antes de obtener una respuesta.
