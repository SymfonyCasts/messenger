# Haciendo el trabajo en el controlador

Dentro de nuestro controlador, después de guardar el nuevo archivo en el sistema de archivos, estamos creando un nuevo objeto `AddPonkaToImage` y enviándolo al bus de mensajes... o técnicamente al bus de "comandos"... porque actualmente lo estamos utilizando como bus de comandos. El resultado final es que el bus llama al método `__invoke()` de nuestro manejador y le pasa ese objeto. Messenger entiende la conexión entre el objeto mensaje y el manipulador gracias a la sugerencia de tipo de argumento y a esta interfaz.

## Bus de comandos: bellamente decepcionante

Por cierto, puede que estés pensando

> Espera... ¿el objetivo de un bus de "comandos" es... simplemente "llamar" a este
> `__invoke()` método por mí? ¿No podría... ya sabes... llamarlo yo mismo y
> saltarme una capa?

Y... ¡sí! Es así de sencillo Al principio, debe parecerte totalmente decepcionante

Pero tener esa "capa", el "bus", en medio nos da dos cosas buenas. En primer lugar, nuestro código está más desacoplado: el código que crea el "comando" -nuestro controlador en este caso- no conoce ni se preocupa por nuestro controlador. Envía el mensaje y sigue adelante. Y en segundo lugar, este sencillo cambio nos va a permitir ejecutar los manejadores de forma asíncrona. Pronto hablaremos de ello.

## Mover el código al Manejador

Volvamos al trabajo: todo el código para añadir Ponka a la imagen se sigue haciendo dentro de nuestro controlador: éste obtiene una versión actualizada de la imagen con Ponka dentro, otro servicio guarda realmente la nueva imagen en el sistema de archivos, y esta última parte -`$imagePost->markAsPonkaAdded()` - actualiza un campo de fecha en la entidad. Son sólo unas pocas líneas de código... ¡pero es mucho trabajo!

Copia todo esto, elimínalo y quita también mis comentarios. Pega todo eso en el manejador. Vale, no es una sorpresa, tenemos algunas variables no definidas.`$ponkaficator`, `$photoManager` y `$entityManager` son todos servicios.

[[[ code('d82713772d') ]]]

En el controlador... en la parte superior, estábamos autocableando esos servicios en el método del controlador. Ya no necesitamos `$ponkaficator`.

[[[ code('8f2be01f38') ]]]

De todos modos, ¿cómo podemos obtener esos servicios en nuestro controlador? Esto es lo más interesante: la clase "mensaje" - `AddPonkaToImage` es una clase simple, "modelo". Es una especie de entidad: no vive en el contenedor y no la autoconducimos a nuestras clases. Si necesitamos un objeto `AddPonkaToImage`, decimos: `new AddPonkaToImage()`. Si decidimos dar a esa clase algún argumento de constructor -más sobre eso en breve- se lo pasamos aquí.

Pero las clases manejadoras son servicios. Y eso significa que podemos utilizar, a la vieja usanza, la inyección de dependencias para obtener cualquier servicio que necesitemos.

Añade `public function __construct()` con, veamos aquí,`PhotoPonkaficator $ponkaficator`, `PhotoFileManager $photoManager` y... necesitamos el gestor de entidades: `EntityManagerInterface $entityManager`

[[[ code('da447338b5') ]]]

Pulsaré `Alt + Enter` y seleccionaré Inicializar campos para crear esas propiedades y establecerlas.

[[[ code('ca216a6549') ]]]

Ahora... vamos a utilizarlas: `$this->ponkaficator`, `$this->photoManager`,`$this->photoManager` de nuevo... y `$this->entityManager`.

[[[ code('0cc8d75375') ]]]

## Datos de la clase de mensajes

¡Qué bien! Esto nos deja con una sola variable indefinida: el propio `$imagePost`al que tenemos que añadir Ponka. Veamos... en el controlador, creamos este objeto de entidad`ImagePost`... que es bastante sencillo: contiene el nombre del archivo en el sistema de archivos... y algunos otros datos menores. Esto es lo que almacenamos en la base de datos.

Volviendo a `AddPonkaToImageHandler`, a alto nivel, esta clase necesita saber en qué`ImagePost` se supone que está trabajando. ¿Cómo podemos pasar esa información del controlador al manejador? ¡Poniéndola en la clase mensaje! Recuerda que ésta es nuestra clase, y puede contener los datos que queramos.

Así que ahora que hemos descubierto que nuestro manejador necesita el objeto `ImagePost`, añade un `public function __construct()` con un argumento: `ImagePost $imagePost`. Haré mi habitual Alt+Enter y seleccionaré "Inicializar campos" para crear y establecer esa propiedad.

[[[ code('0cd52ae71b') ]]]

Más adelante, necesitaremos una forma de leer esa propiedad. Añade un getter:`public function getImagePost()` con un tipo de retorno `ImagePost`. Dentro,`return $this->imagePost`.

[[[ code('9f4f0e6e00') ]]]

Y realmente... puedes hacer que esta clase tenga el aspecto que quieras: podríamos haber hecho que fuera una propiedad `public` sin necesidad de un constructor o un getter. O podrías sustituir el constructor por un `setImagePost()`. Esta es la forma en que me gusta hacerlo... pero no importa: mientras contenga los datos que quieres pasar al manejador... ¡estás bien!

De todos modos, ¡ahora somos peligrosos! De vuelta a `ImagePostController`, aquí abajo,`AddPonkaToImage` necesita ahora un argumento. Pásale `$imagePost`.

[[[ code('e456246d3e') ]]]

Luego, en el manejador, termina esto con`$imagePost = $addPonkaToImage->getImagePost()`.

[[[ code('638edb1f3f') ]]]

¡Me encanta! Así que ése es el poder de la clase mensaje: realmente es como si escribieras un mensaje a alguien que dijera

> Quiero que hagas una tarea y aquí tienes toda la información que necesitas saber para
> realizar esa tarea.

Luego, se lo pasas al bus de mensajes, éste llama al manejador, y el manejador tiene toda la información que necesita para hacer esa tarea. Es una idea sencilla... pero muy bonita.

Asegurémonos de que todo funciona: muévete y actualiza para estar seguro. Sube una nueva imagen y... ¡sigue funcionando!

A continuación: ya hay otra tarea que podemos trasladar a un sistema de manejo de comandos: borrar una imagen.
