# Mensaje, Manejador y el Bus

Messenger es lo que se conoce como "Bus de Mensajes"... que es una especie de herramienta genérica que puede utilizarse para realizar un par de patrones de diseño diferentes, pero similares. Por ejemplo... Messenger puede utilizarse como un "Bus de comandos", un "Bus de consultas", un "Bus de eventos" o... un "Bus escolar". Oh... espera... este último nunca se ha implementado... vale, puede utilizarse para los tres primeros. De todos modos, si estos términos no significan absolutamente nada para ti... ¡genial! Hablaremos de lo que significa todo esto a lo largo del camino.

## Patrón del bus de comandos

La mayoría de la gente utiliza Messenger como un "bus de comandos"... que es una especie de patrón de diseño. La idea es la siguiente. Ahora mismo, hacemos todo el trabajo en el controlador. Bueno, vale, hemos organizado las cosas en servicios, pero nuestro controlador llama a esos métodos directamente. Está bien organizado, pero sigue siendo básicamente procedimental: puedes leer el código de arriba a abajo.

Con un bus de comandos, separas lo que quieres que ocurra -llamado "comando"- del código que hace ese trabajo. Imagina que trabajas como camarero o camarera en un restaurante y alguien quiere una pizza margarita... ¡con albahaca fresca extra! Mmm. ¿Vuelves corriendo a la cocina y la preparas tú mismo? Probablemente no... En su lugar, anotas el pedido. Pero... digamos que, en lugar de eso, escribes una "orden": cocina una pizza al estilo margarita con extra de albahaca fresca. A continuación, "envías" esa orden a la cocina. Y finalmente, un chef hace toda la magia para que la pizza esté lista. Mientras tanto, puedes recibir más pedidos y enviar más "órdenes" a la cocina.

Esto es un bus de comandos: creas una orden simple e informativa "cocinar una pizza", se la das a algún "sistema" central... que recibe esa elegante palabra "bus", y se asegura de que algo vea esa orden y la "maneje"... en este caso, un "chef" cocina la pizza. Y ese "bus" central es probablemente lo suficientemente inteligente como para que diferentes personas "manejen" diferentes órdenes: el chef cocina la pizza, pero el bar tender prepara los pedidos de bebidas.

## Creación de la clase de comandos

Vamos a recrear esa misma idea... ¡en código! La "orden" que queremos emitir es: añadir Ponka a esta imagen. En Messenger, cada comando es una simple clase PHP. En el directorio`src/`, crea un nuevo directorio `Message/`. Podemos poner nuestras clases de comando, o de "mensaje", en cualquier lugar... pero esta es una buena forma de organizar las cosas. Crea una nueva clase PHP llamada `AddPonkaToImage`... porque eso describe la intención de lo que queremos que ocurra: queremos que alguien añada ponka a la imagen. Dentro... por ahora... no hagas nada.

[[[ code('2679ecb5e7') ]]]

Una clase de mensaje es tu código: puede tener el aspecto que quieras. Más adelante hablaremos de ello.

## Creación de la clase manejadora

Comando, ¡hecho! El paso 2 es crear la clase "manejadora": el código que realmente añadirá Ponka a una imagen. Una vez más, esta clase puede vivir en cualquier lugar, pero vamos a crear un nuevo directorio `MessageHandler/` para mantener las cosas organizadas. La clase manejadora también puede llamarse de cualquier manera... pero a menos que te guste confundirte... llámala `AddPonkaToImageHandler`.

[[[ code('57f1429b7c') ]]]

A diferencia del mensaje, la clase manejadora tiene algunas reglas. En primer lugar, una clase manejadora debe implementar `MessageHandlerInterface`... que en realidad está vacía. Es una interfaz "marcadora". Hablaremos de por qué se necesita esto dentro de un rato. Y, en segundo lugar, la clase debe tener una función pública llamada `__invoke()` con un único argumento que sea de tipo indicativo con la clase de mensaje. Así, `AddPonkaToImage`, y luego cualquier nombre de argumento: `$addPonkaToImage`. Dentro, hmm, para ver cómo funciona todo esto, vamos a `dump($addPonkaToImage)`.

[[[ code('880b6eada5') ]]]

## Conectando el mensaje y el manejador

Bien, retrocedamos. A grandes rasgos, así es como va a funcionar esto. En nuestro código, crearemos un objeto `AddPonkaToImage` y le diremos a Messenger -el bus de mensajes- que lo "maneje". Messenger verá nuestro objeto `AddPonkaToImage`, irá a buscar el servicio `AddPonkaToImageHandler`, llamará a su método `__invoke()` y le pasará el objeto`AddPonkaToImage`. Eso es... ¡todo lo que hay que hacer!

Pero espera... ¿cómo sabe Messenger que el objeto `AddPonkaToImage` debe ser "manejado" por `AddPonkaToImageHandler`? Por ejemplo, si tuviéramos varias clases de comandos y manejadores, ¿cómo sabría qué manejador maneja cada mensaje?

Busca tu terminal y ejecuta:

```terminal
php bin/console debug:messenger
```

Este es un comando impresionante: nos muestra un mapa de qué manejador será llamado para cada mensaje. Ahora mismo sólo tenemos 1, pero... sí, de alguna manera ya sabe que `AddPonkaToImage` debe ser manejado por `AddPonkaToImageHandler`. ¿Cómo?

Lo sabe gracias a dos cosas. En primer lugar, ese `MessageHandlerInterface` vacío es una "bandera" que indica a Symfony que se trata de un "manejador" de Messenger. Y en segundo lugar, Messenger busca un método llamado `__invoke()` y lee el tipo-indicación de su argumento para saber qué clase de mensaje debe manejar. Así que, `AddPonkaToImage`.

Y sí, puedes configurar todo esto de otra manera, e incluso omitir la adición de la interfaz utilizando una etiqueta. Hablaremos de esto más adelante... pero normalmente no es algo de lo que debas preocuparte.

Ah, y si no estás familiarizado con el método `__invoke()`, ignorando a Messenger por un minuto, es un método mágico que puedes poner en cualquier clase de PHP para hacerla "ejecutable": puedes tomar un objeto y llamarlo como una función... si tiene este método:

```php
$handler = new AddPonkaToImageHandler();
$handler($addPonkaToImage);
```

Este detalle no es en absoluto importante para entender Messenger, pero explica por qué se eligió este, por lo demás, "extraño" nombre de método.

## Enviar el mensaje

¡Ufff! Comprobación de estado: tenemos una clase de mensaje, tenemos una clase de manejador, y gracias a alguna astucia de Symfony, Messenger sabe que están vinculados entre sí. Lo último que tenemos que hacer es... ¡enviar realmente el comando, o "mensaje", al bus!

Dirígete a `ImagePostController`. Esta es la ruta que sube nuestra imagen y añade Ponka a ella. Busca el bus de mensajes añadiendo un nuevo argumento con el tipo`MessageBusInterface`.

[[[ code('a9cdac2c6b') ]]]

Entonces... justo encima de todo el código de la imagen Ponka -dejaremos todo eso ahí por el momento- di `$message = new AddPonkaToImage()`. Y luego`$messageBus->dispatch($message)`.

[[[ code('cac1436bb2') ]]]

Eso es todo! `dispatch()` es el único método de ese objeto... no hay nada más complicado que esto.

Así que... ¡probemos! Si todo funciona, este objeto `AddPonkaToImage` debería pasarse a `__invoke()` y luego lo volcaremos. Como todo esto ocurrirá en una petición AJAX, utilizaremos un truco del perfilador para ver si ha funcionado.

Vuelve a actualizar la página... para estar seguro. Sube una nueva foto y... cuando termine, abajo en la barra de herramientas de depuración de la web, pasa el ratón sobre el icono de la flecha para encontrar... ¡bien! Aquí está esa petición AJAX. Mantendré pulsada la tecla Comando y haré clic en el enlace para abrirlo en una nueva pestaña. Este es el perfilador de esa petición AJAX. Haz clic en el enlace "Depurar" de la izquierda.

¡Ja! ¡Ahí está! ¡Esto nos muestra que nuestro código `dump()` se ejecutó durante la petición AJAX! ¡Ha funcionado! Pasamos el mensaje al bus de mensajes y éste llama al manejador.

Por supuesto... nuestro manejador no hace nada todavía. A continuación, vamos a trasladar toda la lógica de Ponkaficación de nuestro controlador al manejador.