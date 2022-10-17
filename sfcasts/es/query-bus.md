# Autobús turístico

El último tipo de autobús del que oirás hablar es... ¡el autobús turístico de dos pisos! Es decir... ¡el autobús de la consulta! Para que lo sepas... aunque soy un fanático de saludar como un idiota en el nivel superior de un autobús turístico, no soy un gran fanático de los autobuses de consulta: creo que hacen que tu código sea un poco más complejo... para no obtener mucho beneficio. Dicho esto, quiero que al menos entiendas qué es y cómo encaja en la metodología del bus de mensajes.

## Creación del bus de consultas

En `config/packages/messenger.yaml` tenemos `command.bus` y `event.bus`. Añadamos `query.bus`. Mantendré las cosas sencillas y sólo pondré esto en `~` para obtener la configuración por defecto.

[[[ code('602f5d0add') ]]]

## ¿Qué es una consulta?

Vale: ¿para qué sirve un "bus de consulta"? Entendemos el propósito de los comandos: enviamos mensajes que suenan como comandos: `AddPonkaToImage` o`DeleteImagePost`. Cada comando tiene entonces exactamente un manejador que realiza ese trabajo... pero no devuelve nada. En realidad, aún no lo he mencionado: los comandos sólo realizan un trabajo, pero no comunican nada de vuelta. Por ello, no hay problema en procesar los comandos de forma sincrónica o asincrónica: nuestro código no espera recibir información de vuelta del manejador.

Un bus de consulta es lo contrario. En lugar de ordenar al bus que haga su trabajo, el objetivo de una consulta es obtener información del manipulador. Por ejemplo, supongamos que, en nuestra página web, queremos imprimir el número de fotos que se han subido. Esta es una pregunta o consulta que queremos hacer a nuestro sistema

> ¿Cuántas fotos hay en la base de datos?

Si utilizas el patrón del bus de consulta, en lugar de obtener esa información directamente, enviarás una consulta.

## Crear la consulta y el manejador

Dentro del directorio `Message/`, crea un nuevo subdirectorio `Query/`. Y dentro de él, crea una nueva clase PHP llamada `GetTotalImageCount`.

Incluso ese nombre parece una consulta en lugar de un comando: Quiero obtener el número total de imágenes. Y... en este caso, podemos dejar la clase de consulta en blanco: no necesitaremos pasar ningún dato extra al manejador.

[[[ code('e1efceb84f') ]]]

A continuación, dentro de `MessageHandler/`, haz lo mismo: añade un subdirectorio `Query/` y luego una nueva clase llamada `GetTotalImageCountHandler`. Y, como con todo lo demás, haz que ésta implemente `MessageHandlerInterface` y crea`public function __invoke()` con un argumento de tipo-indicado con la clase de mensaje:`GetTotalImageCount $getTotalImageCount`.

[[[ code('a8fd7e470f') ]]]

¿Qué hacemos aquí dentro? ¡Encontrar el recuento de imágenes! Probablemente inyectando el`ImagePostRepository`, ejecutando una consulta y devolviendo ese valor. Dejaré la parte de la consulta para ti y sólo `return 50`.

[[[ code('05c61ac5b9') ]]]

Pero espera un segundo... ¡porque acabamos de hacer algo totalmente nuevo! ¡Estamos devolviendo un valor de nuestro manejador! Esto no es algo que hayamos hecho en ningún otro sitio. Los comandos funcionan pero no devuelven ningún valor. Una consulta no hace realmente ningún trabajo, su único objetivo es devolver un valor.

Antes de enviar la consulta, abre `config/services.yaml` para que podamos hacer nuestro mismo truco de vincular cada manejador al bus correcto. Copia la sección `Event\`, pégala, cambia `Event` por `Query` en ambos sitios... y luego establece el bus a `query.bus`.

[[[ code('b31927d4b4') ]]]

¡Me encanta! Comprobemos nuestro trabajo ejecutando:

```terminal
php bin/console debug:messenger
```

¡Sí! `query.bus` tiene un manejador, `event.bus` tiene un manejador y `command.bus` tiene dos.

## Despachar el mensaje

¡Hagamos esto! Abre `src/Controller/MainController.php`. Para obtener el bus de consulta, necesitamos saber qué combinación de tipo-indicación y nombre de argumento debemos utilizar. Obtenemos esa información ejecutando:

```terminal
php bin/console debug:autowiring mess
```

Podemos obtener el `command.bus` principal utilizando la sugerencia de tipo `MessageBusInterface` con cualquier nombre de argumento. Para obtener el bus de consulta, tenemos que utilizar esa sugerencia de tipo y nombrar el argumento: `$queryBus`.

Hazlo: `MessageBusInterface $queryBus`. Dentro de la función, di`$envelope = $queryBus->dispatch(new GetTotalImageCount())`.

[[[ code('6dc6de5e59') ]]]

## Obtención del valor devuelto

No lo hemos utilizado demasiado, pero el método `dispatch()` devuelve el objeto final Envelope, que tendrá una serie de sellos diferentes. Una de las propiedades de un bus de consultas es que cada consulta se gestionará siempre de forma sincrónica, ¿por qué? Sencillo: necesitamos la respuesta a nuestra consulta... ¡ahora mismo! Y, por tanto, nuestro manejador debe ejecutarse inmediatamente. En Messenger, no hay nada que imponga esto en un bus de consultas... es que nunca dirigiremos nuestras consultas a un transporte, por lo que siempre se manejarán ahora mismo.

De todos modos, una vez que se maneja un mensaje, Messenger añade automáticamente un sello llamado`HandledStamp`. Vamos a conseguirlo: `$handled = $envelope->last()` con`HandledStamp::class`. Añadiré algo de documentación inline encima de eso para decirle a mi editor que esto será una instancia de `HandledStamp`.

[[[ code('dcca333caf') ]]]

Entonces... ¿por qué conseguimos este sello? Bueno, necesitamos saber cuál era el valor de retorno de nuestro manejador. Y, convenientemente, Messenger lo almacena en este sello Consíguelo con `$imageCount = $handled->getResult()`.

[[[ code('e73f919b50') ]]]

Pasémoslo a la plantilla como una variable `imageCount`... 

[[[ code('f7358992be') ]]]

y luego en la plantilla - `templates/main/homepage.html.twig` - ya que todo nuestro frontend está construido en Vue.js, anulemos el bloque `title` en la página y usémoslo allí: `Ponka'd {{ imageCount }} Photos`.

[[[ code('ab2c848ae8') ]]]

¡Vamos a comprobarlo! Muévete, actualiza y... ¡funciona! Tenemos las 50 fotos de Ponka... al menos según nuestra lógica codificada.

Así que... ¡es un bus de consulta! No es mi favorito porque no se nos garantiza qué tipo devuelve: el `imageCount` podría ser realmente una cadena... o un objeto de cualquier clase. Como no estamos llamando a un método directo, los datos que obtenemos de vuelta parecen un poco confusos. Además, como las consultas tienen que gestionarse de forma sincrónica, no estás ahorrando ningún rendimiento al aprovechar un bus de consultas: es puramente un patrón de programación.

Pero mi opinión es totalmente subjetiva, y a mucha gente le encantan los buses de consulta. De hecho, hemos hablado sobre todo de las herramientas en sí: buses de comandos, eventos y consultas. Pero hay algunos patrones más profundos, como CQRS o event sourcing, que estas herramientas pueden desbloquear. Esto no es algo que utilicemos actualmente aquí en SymfonyCasts... pero si te interesa, puedes leer más sobre este tema - [el blog de Matthias Noback](https://matthiasnoback.nl/) es mi fuente favorita.

Ah, y antes de que se me olvide, si miras atrás en los documentos de Symfony... en la página principal de Messenger... hasta el final... hay un punto aquí sobre cómo obtener resultados de tu manejador. Muestra algunos atajos que puedes utilizar para obtener más fácilmente el valor del bus.

A continuación, vamos a hablar de los suscriptores de los manejadores de mensajes: una forma alternativa de configurar un manejador de mensajes que tiene algunas opciones adicionales.
