# Retraso en AMQP: intercambio de letra muerta

Cuando empezamos a trabajar con AMQP, te dije que entraras en `ImagePostController` y quitaras el sello `DelayStamp`. Este sello es una forma de decirle al sistema de transporte que espere al menos 500 milisegundos antes de permitir que un trabajador reciba el mensaje. Vamos a cambiarlo a 10 segundos, es decir, `10000` milisegundos.

[[[ code('0eff211920') ]]]

Ahora, dirígete a tu terminal y asegúrate de que tu trabajador no se está ejecutando.

Bien, ¡vamos a ver qué pasa! Ahora mismo ambas colas están vacías. Voy a subir 3 fotos... entonces... ¡rápido, rápido, rápido! Ve a mirar las colas. De repente, ¡puf! ha aparecido una nueva cola... con un nombre extraño: `delay_messages_high_priority__10000`. Y tiene - ¡dun, dun, dun! - tres mensajes en ella.

Vamos a mirar dentro. Es interesante, los mensajes se entregaron aquí, en lugar de la cola normal. Pero luego... ¿desaparecieron? El gráfico muestra cómo los mensajes en esta cola pasaron de 3 a 0. Pero... ¿cómo? ¡Nuestro trabajador ni siquiera está en marcha!

¡Woh! ¡La página acaba de salir en 404! ¡La cola ha desaparecido! ¡Algo está atacando nuestras colas!

Vuelve a la lista de colas. Sí, esa extraña cola de "retraso" ha desaparecido... oh, pero ahora los tres mensajes están de alguna manera en `messages_high`. ¿Qué demonios ha pasado?

Bueno, en primer lugar, para demostrar que todo el sistema sigue funcionando... independientemente de la locura que acaba de ocurrir... ejecutemos nuestro trabajador y consumamos desde los transportes`async_priority_high` y `async`:

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

Los consume y... cuando nos desplazamos, vamos a la página de inicio y refrescamos, ¡sí! se ha añadido Ponka a esas imágenes.

## El intercambio de retrasos

Vale, vamos a ver cómo ha funcionado esto. Por un lado, no es importante: si hubiéramos estado ejecutando nuestro trabajador todo el tiempo, habrías visto que esos mensajes se retrasaron, de hecho, 10 segundos. Cómo se retrasan los mensajes en RabbitMQ es una locura... pero si no te importan los detalles, Messenger se encarga de ello por ti.

Pero quiero ver cómo funciona esto... en parte porque será una gran oportunidad para ver cómo funcionan algunas de las características más avanzadas de AMQP.

Haz clic en "Intercambios". ¡Sorpresa! Hay un nuevo intercambio llamado `delays`. Y en lugar de ser del tipo `fanout` como nuestros otros dos intercambios, se trata de un intercambio `direct`. Pronto hablaremos de lo que eso significa.

Pero lo primero que hay que saber es que cuando Messenger ve que un mensaje debe retrasarse, lo envía a este intercambio en lugar de enviarlo al intercambio normal, el "correcto". En este momento, el intercambio `delays` no tiene enlaces... pero eso cambiará cuando enviemos un mensaje retrasado.

Para poder ver realmente lo que ocurre, aumentemos el retraso a 60 segundos.

[[[ code('465e79bf29') ]]]

Bien, sube 3 fotos más: ahora sabemos que se acaban de enviar al intercambio `delays`. Y... si actualizas ese intercambio... ¡tiene un nuevo enlace! Esto dice:

> Si un mensaje enviado aquí tiene una "clave de enrutamiento" establecida en
> `delay_messages_high_priority__60000`, entonces enviaré ese mensaje a una
> cola llamada delay_messages_high_priority__60000

Una "clave de enrutamiento" es una propiedad extra que puedes establecer en un mensaje que se envía a AMQP. Normalmente, Messenger no establece ninguna clave de enrutamiento, pero cuando un mensaje tiene un retraso, sí lo hace. Y gracias a esta vinculación, esos tres mensajes se envían a la cola `delay_messages_high_priority__60000`. Así es como funciona un intercambio `direct`: en lugar de enviar cada mensaje a todas las colas vinculadas, utiliza las reglas de la "clave de vinculación" para averiguar a qué cola -o colas- debe ir un mensaje.

## Colas de retraso: x-message-ttl y x-deal-letter-exchange

Haz clic en la cola porque es súper interesante. Tiene unas cuantas propiedades importantes. La primera es un `x-message-ttl` fijado en 60 segundos. ¿Qué significa esto? Cuando estableces esto en una cola, significa que, después de que un mensaje haya estado en esta cola durante 60 segundos, RabbitMQ debería eliminarlo... lo que parece una locura, ¿verdad? ¿Por qué querríamos que los mensajes sólo vivieran durante 60 segundos... y luego fueran eliminados? Bueno... es por diseño... y funciona junto con esta segunda propiedad importante:`x-dead-letter-exchange`.

Si una cola tiene esta propiedad, le dice a Rabbit que cuando un mensaje alcanza su TTL de 60 segundos y necesita ser eliminado, no debe ser borrado. En su lugar, debe ser enviado al intercambio `messages_high_priority`.

Así, Messenger entrega los mensajes al intercambio `delays` con una clave de enrutamiento que hace que se envíen aquí. Después, tras permanecer 60 segundos, el mensaje se elimina de esta cola y se envía a la central `messages_high_priority`. ¡Sí, se entrega en el lugar correcto después de 60 segundos!

Y entonces... ¡404! Incluso la propia cola está marcada como "temporal": una vez que no le quedan mensajes, se borra.

Cuando vuelves a ver las colas, los mensajes fueron entregados a la cola `messages_high`... pero ésta ya está vacía porque nuestro trabajador los consumió.

Así que... sí... ¡vaya! Cada vez que publicamos un mensaje con retardo, Messenger configura todo esto: crea la cola de retardo temporal con la configuración del TTL y del intercambio de letras muertas, añade un enlace al intercambio `delays` para enrutar a esta cola, y añade la clave de enrutamiento correcta al mensaje para asegurarse de que acaba en esa cola.

Realmente puedes empezar a ver lo ricas que son las funciones de AMQP... aunque no las necesites. La característica más importante que acabamos de ver es el tipo de intercambio `direct`: un intercambio que se basa en las claves de enrutamiento para averiguar a dónde debe ir cada mensaje.

A continuación, ¿podríamos utilizar intercambios directos para nuestros mensajes no retardados? En lugar de dos intercambios que se "abanican" cada uno a una cola distinta, ¿podríamos crear un solo intercambio que, mediante el uso de claves de enrutamiento, entregue los mensajes correctos a las colas correctas? Totalmente.
