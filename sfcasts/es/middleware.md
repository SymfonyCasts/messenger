# Middleware

Internamente, cuando envías un mensaje al bus... ¿qué ocurre? ¿Qué aspecto tiene el código dentro del bus? La respuesta es... ¡básicamente no hay código dentro del bus! Todo se hace a través del middleware.

## Conceptos básicos del middleware

El bus no es más que una colección de "middleware". Y cada middleware es sólo una función que recibe el mensaje y puede hacer algo con él.

El proceso es así. Pasamos un mensaje al método `dispatch()`, luego el bus lo pasa al primer middleware. El middleware ejecuta entonces algún código y finalmente llama al segundo middleware. Ejecuta algo de código y acaba llamando al tercer middleware... hasta que finalmente el último middleware -digamos que es el cuarto middleware- no tiene a quién llamar. En ese momento, la función del cuarto middleware termina, luego la del tercero, luego la del segundo y luego la del primero. Gracias a este diseño, cada middleware puede ejecutar código antes de llamar al siguiente middleware o después.

Este concepto de "middleware" no es exclusivo de Messenger, ni siquiera de PHP: es un patrón. Puede ser a la vez superútil... y un poco confuso... ya que es un gran círculo. La cuestión es la siguiente: con Messenger, si quieres engancharte al proceso de envío -como registrar lo que está ocurriendo- lo harás con un middleware. Incluso la funcionalidad principal de Messenger -ejecutar manejadores y enviar mensajes a los transportes- se hace con un middleware Estos se llaman `HandleMessageMiddleware` y`SendMessageMiddleware`, por si quieres ponerte friki y ver cómo funcionan.

Así que éste es nuestro objetivo: cada vez que enviemos un mensaje... desde cualquier lugar, quiero adjuntar un identificador único a ese mensaje y luego utilizarlo para registrar lo que ocurre a lo largo del tiempo con el mensaje: cuándo se envía inicialmente, cuándo se envía al transporte y cuándo se recibe del transporte y se maneja. Incluso podrías utilizarlo para registrar el tiempo que tarda un mensaje individual en ser procesado o cuántas veces se reintenta.

## Crear un middleware

Crear un middleware es en realidad bastante sencillo. Crea un nuevo directorio dentro de `src/` llamado `Messenger/`... aunque... como con casi todo en Symfony, este directorio podría llamarse como sea. Dentro, añade una clase llamada, qué tal, `AuditMiddleware`.

[[[ code('9d6147439d') ]]]

La única regla para los middleware es que deben implementar -¡sorpresa! -
`MiddlewareInterface`. Iré a "Código -> Generar" -o Comando+N en un Mac- y seleccionaré "Implementar métodos". Esta interfaz sólo requiere uno: `handle()`. Hablaremos de lo de la "pila" en un segundo... pero sobre todo... la firma de este método tiene sentido: recibimos el `Envelope` y devolvemos un `Envelope`.

[[[ code('a19817ab3f') ]]]

La única línea que tu middleware necesitará casi seguro es ésta:`return $stack->next()->handle($envelope, $stack)`.

[[[ code('1cdc3fbcef') ]]]

Esta es la línea que básicamente dice

> Quiero ejecutar el siguiente middleware y luego devolver su valor.

Sin esta línea, cualquier middleware posterior a nosotros nunca sería llamado... que no suele ser lo que quieres.

## Registrar el middleware

Y... para empezar... es suficiente: ¡esta clase ya es un middleware funcional! Pero, a diferencia de muchas cosas en Symfony, Messenger no encontrará y empezará a usar este middleware automáticamente. Busca tu terminal abierto y, una vez más, ejecuta:

```terminal
php bin/console debug:config framework messenger
```

Veamos... en algún lugar de aquí hay una clave llamada `buses`. En ella se definen todos los servicios del bus de mensajes que tienes en tu sistema. Ahora mismo, tenemos uno: el bus por defecto llamado `messenger.bus.default`. Ese nombre puede ser cualquier cosa y se convierte en el identificador del servicio. Debajo de esto, podemos utilizar la clave `middleware` para definir cualquier nuevo middleware que queramos añadir, además de los básicos que se añaden por defecto.

Vamos a copiar esa configuración. Luego, abre `config/packages/messenger.yaml` y, debajo de`framework:`, `messenger:`, pega esto justo encima... y asegúrate de que está sangrado correctamente. Debajo, añade a `middleware:` una nueva línea, y luego nuestro nuevo servicio de middleware:`App\Messenger\AuditMiddleware`.

[[[ code('4fe287ea10') ]]]

## Orden del middleware

Y así, nuestro middleware debería ser llamado... junto con todo el middleware principal. ¿Qué... son los middleware del núcleo? ¿Y en qué orden se llama todo? Bueno, todavía no hay una buena forma de verlo, pero puedes encontrar esta información ejecutando

```terminal
php bin/console debug:container --show-arguments messenger.bus.default.inner
```

... que es una forma de muy bajo nivel de obtener información sobre el bus de mensajes. De todos modos, hay unos cuantos middleware centrales al principio que configuran algunas cosas básicas, luego nuestro middleware y, finalmente, se llama a `SendMessageMiddleware` y`HandleMessageMiddleware` al final. Saber el orden exacto de estas cosas no es tan importante, pero espero que ayude a desmitificar las cosas a medida que avancemos.

A continuación, vamos a ponernos a trabajar utilizando nuestro middleware para adjuntar un identificador único a cada mensaje. ¿Cómo? A través de nuestro propio sello
