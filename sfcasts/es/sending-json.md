# Serializar mensajes como JSON

Una vez que empiezas a usar RabbitMQ, se hace posible un flujo de trabajo totalmente diferente... un flujo de trabajo que es especialmente común con sistemas más grandes. La idea es que el código que envía un mensaje puede no ser el mismo código que consume y maneja ese mensaje. Nuestra aplicación es responsable tanto de enviar los mensajes a RabbitMQ como, aquí en el terminal, de consumir los mensajes de la cola y manejarlos.

¿Pero qué pasa si queremos enviar uno o más mensajes a RabbitMQ con la expectativa de que algún otro sistema -quizás algún código escrito en un lenguaje diferente y desplegado en un servidor diferente- lo consuma y lo maneje? ¿Cómo podemos hacerlo?

Bueno... a alto nivel... ¡es fácil! Si quisiéramos enviar cosas a este transporte `async`... pero no pensáramos consumir esos mensajes, ¡no tendríamos que cambiar nada en nuestro código! No, simplemente... no consumiríamos mensajes de ese transporte al utilizar el comando `messenger:consume`. Podríamos seguir consumiendo mensajes de otros transportes, sólo que no leeríamos estos... porque sabemos que alguien más lo hará. ¡Ya está! ¡Victoria! ¡Café!

## ¿Cómo se formatean nuestros mensajes?

Pero... si fueras a enviar datos a otro sistema, ¿cómo formatearías normalmente esos datos? Bueno, para usar un ejemplo más familiar, cuando envías datos a una ruta de la API, normalmente los formateas como JSON... o quizás XML. Lo mismo ocurre en el mundo de las colas. Puedes enviar un mensaje a RabbitMQ en cualquier formato... siempre que quien consuma ese mensaje entienda el formato. Así que... ¿qué formato estamos utilizando ahora? ¡Vamos a averiguarlo!

Iré a la cola `messages_normal`... y para estar seguros, vaciémosla. Los mensajes enviados al transporte `async` acabarán en esta cola... y las clases `ImagePostDeleteEvent` se dirigen a ella. Bien, volvemos a nuestra aplicación, borramos una foto y luego, mirando nuestra cola, en un momento... ¡ahí está! Nuestra cola contiene el único mensaje nuevo.

Veamos cómo es exactamente este mensaje. Abajo, hay un punto para sacar un mensaje. Pero... por alguna razón... esto no me ha funcionado. Para evitarlo, abriré mis herramientas de red, haré clic en "Obtener mensaje(s)" de nuevo... y miraré la petición AJAX que acaba de hacer. Abre los datos devueltos y pasa el ratón por encima de la propiedad `payload`.

Sí, este es el aspecto de nuestro mensaje en la cola: este es el cuerpo del mensaje. ¿Qué es ese feo formato? Es un objeto PHP serializado! Cuando Messenger consume esto, sabe que debe utilizar la función `unserialize` para volver a convertirlo en un objeto... y así, ¡este formato funciona de maravilla!

Pero si esperamos que una aplicación PHP diferente consuma esto... des-serializarlo no funcionará porque estas clases probablemente no existirán. Y si el código que manejará esto está escrito en un lenguaje diferente, pfff, ni siquiera tendrán la oportunidad de leer y entender este formato específico de PHP.

La cuestión es: usar la serialización de PHP funciona muy bien cuando la aplicación que envía el mensaje también lo maneja. Pero funciona horriblemente cuando ese no es el caso. En su lugar, probablemente querrás utilizar JSON o XML.

## Utilizar el serializador de Symfony

Afortunadamente, utilizar un formato diferente es fácil. Voy a purgar ese mensaje de la cola una vez más. Muévete y abre `config/packages/messenger.yaml`. Una de las claves que puedes tener debajo de cada transporte se llama`serializer`. Ponlo en una cadena especial: `messenger.transport.symfony_serializer`.

[[[ code('627715a70b') ]]]

Cuando se envía un mensaje a un transporte -ya sea Doctrine, AMQP o cualquier otro-, éste utiliza un "serializador" para codificar ese mensaje en un formato de cadena que pueda ser enviado. Más tarde, cuando lee un mensaje de un transporte, utiliza ese mismo serializador para descodificar los datos de nuevo en el objeto mensaje.

Messenger viene con dos "serializadores" de fábrica. El primero es el serializador de PHP... que es el predeterminado. El segundo es el "Serializador Symfony", que utiliza el componente Serializador de Symfony. Ese es el servicio de serializador al que acabamos de cambiar. Si no tienes ya instalado el componente serializador, asegúrate de instalarlo con:

```terminal
composer require "serializer:^1.0"
```

El serializador de Symfony es genial porque es realmente bueno convirtiendo objetos en JSON o XML, y utiliza JSON por defecto. Así que... ¡vamos a ver qué pasa! Retrocede y elimina otra foto. De vuelta en el gestor de Rabbit, utilizaré el mismo truco que antes para ver cómo es ese mensaje.

Woh. ¡Esto es fascinante! El `payload` es ahora... súper sencillo: sólo una clave `filename`establecida en el nombre del archivo. Esta es la representación JSON de la clase de mensaje, que es `ImagePostDeletedEvent`. Abre eso:`src/Message/Event/ImagePostDeletedEvent.php`. ¡Sí! El serializador de Symfony ha convertido la única propiedad de este objeto en JSON.

No vamos a profundizar demasiado en el componente serializador de Symfony, pero si quieres saber más, profundizamos mucho más en nuestro [Tutorial de la Plataforma API](https://symfonycasts.com/screencast/api-platform).

En cualquier caso, esta sencilla estructura JSON es algo que cualquier otro sistema podría entender. Así que... ¡somos lo máximo!

Pero... sólo como reto... si intentáramos consumir este mensaje desde nuestra aplicación Symfony... ¿funcionaría? No estoy seguro. Si se consume este mensaje, ¿cómo sabría el serializador que esta simple cadena JSON debe descodificarse en un objeto `ImagePostDeletedEvent`? La respuesta... está en otro lugar del mensaje: las cabeceras. Eso a continuación.
