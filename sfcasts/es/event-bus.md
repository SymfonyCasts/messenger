# Eventos y bus de eventos

Messenger es un "bus de mensajes". Y resulta que un "mensaje" es un término bastante genérico en informática. De hecho, hay tres tipos de mensajes de los que oirás hablar habitualmente.

## Los mensajes: Comandos, Eventos y Consultas

El primer tipo de mensaje es un "comando". Y ese es el tipo que hemos estado creando hasta ahora: creamos clases de mensajes que suenan como un comando:`AddPonkaToImage` o `DeleteImagePost` y cuyos manejadores realizan alguna acción. Cuando creas clases de mensajes y manejadores con este aspecto, estás utilizando Messenger como un "bus de comandos". Y una de las, digamos, "reglas" de los comandos es que cada comando debe tener exactamente un manejador. Ese es el patrón de diseño "comando".

El segundo tipo de mensaje es un "evento". Si creas una clase de "evento" y la pasas a Messenger, entonces estás utilizando Messenger como un bus de "eventos". La diferencia entre el aspecto de una clase "comando" y el de una clase "evento" es sutil: se reduce a las convenciones de nomenclatura y a lo que, en última instancia, intentas conseguir. Un evento se envía después de que ocurra algo y puede tener de cero a muchos manejadores. No te preocupes, pronto veremos cómo es esto.

El tercer tipo de mensaje es una "consulta", de la que hablaremos más adelante. Por ahora, vamos a centrarnos en entender los eventos y en qué se diferencian de los comandos... porque... puede ser súper confuso. Y Messenger, al ser un "bus de mensajes" genérico, funciona perfectamente con ambos.

## Crear un segundo bus

Antes de crear nuestro primer evento, cerraré algunas cosas y luego abriré`config/packages/messenger.yaml`. Si nuestra aplicación aprovecha tanto los comandos como los eventos, está totalmente bien utilizar un solo bus para manejar todo eso. Pero, en aras de complicarnos un poco la vida y aprender más, vamos a seguir utilizando nuestro bus existente sólo como bus de comandos y a crear un nuevo bus para utilizarlo sólo con eventos.

Para ello, bajo la clave `buses:`, añade una nueva llamada, qué tal, `event.bus`. Ponla en `~` que es nula... sólo porque no tenemos ninguna otra configuración que debamos poner aquí todavía. Esto hará que se añada un nuevo servicio `MessageBus` al contenedor.

[[[ code('b1461e7a94') ]]]

Hasta ahora, siempre que hemos necesitado el bus de mensajes -como en `ImagePostController` - lo hemos autocableado utilizando la sugerencia de tipo `MessageBusInterface`. La pregunta ahora es: ¿cómo podemos acceder al nuevo servicio de bus de mensajes?

Busca tu terminal y ejecuta

```terminal
php bin/console debug:autowiring
```

... que... ¡estalla! Mi error:

> Configuración no válida para la ruta `framework.messenger`: debes especificar `default_bus`

Copiar el nombre del bus por defecto. Una vez que definas más de un bus, necesitas una clave`default_bus` establecida para tu bus "principal". Esto le dice a Symfony qué servicio de MessageBus debe pasarnos cuando utilicemos la sugerencia de tipo `MessageBusInterface`.

[[[ code('173c8b2076') ]]]

Prueba de nuevo el comando `debug:autowiring`... y busca "mess".

```terminal-silent
php bin/console debug:autowiring
```

Ah, ¡ahora tenemos dos entradas! Esto me dice que si usamos el type-hint`MessageBusInterface`, obtendremos el servicio `messenger.bus.default`. Ignora la parte `debug.traced` - eso es sólo Symfony añadiendo algunas herramientas de depuración. Pero ahora, si usas el type-hint `MessageBusInterface` y nombras el argumento `$eventBus`, ¡te pasará el nuevo servicio de bus de eventos!

Se trata de una nueva característica de Symfony 4.2, en la que puedes autoconectar cosas mediante una combinación del tipo-hint y el nombre del argumento. Symfony tomó el nombre de nuestro bus - `event.bus` - e hizo posible utilizar el nombre del argumento `$eventBus`.

## Diferencias entre los buses

¡Genial! ¡Ya sabemos cómo obtener el bus de eventos! Pero... ¿cuál es la diferencia entre estos dos buses? ¿Se comportan de forma diferente? La respuesta es... ¡no!

Un bus no es más que un conjunto de middleware. Si tienes dos objetos de bus que tienen el mismo middleware... entonces... ¡esos buses de mensajes son efectivamente idénticos! Así que, aparte del hecho de que, hasta ahora, sólo hemos añadido nuestro `AuditMiddleware`al primer bus, estos buses funcionarán y actuarán de forma idéntica. Por eso, aunque haya creado un servicio para manejar comandos y otro para manejar eventos... ah... realmente podríamos enviar todos nuestros comandos y eventos a un solo servicio.

A continuación, vamos a crear un evento, a aprender qué aspecto tiene, por qué podríamos utilizarlos y en qué se diferencian de los comandos.
