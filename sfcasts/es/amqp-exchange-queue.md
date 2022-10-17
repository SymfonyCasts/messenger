# Aspectos internos de AMQP: Intercambios y colas

Acabamos de cambiar la configuración de nuestro Messenger para enviar los mensajes a una instancia de RabbitMQ basada en la nube, en lugar de enviarlos a Doctrine para que los almacene en la base de datos. Y después de hacer ese cambio... ¡todo sigue funcionando! Podemos enviar mensajes de forma normal y consumirlos con el comando `messenger:consume`. ¡Eso es genial!

Pero quiero mirar un poco más cómo funciona esto... lo que realmente está ocurriendo dentro de RabbitMQ. Detén el trabajador... y entonces vamos a borrar unas cuantas imágenes: una, dos, tres. Esto debería haber provocado el envío de tres nuevos mensajes a Rabbit.

Cuando usábamos el transporte Doctrine, podíamos consultar una tabla de la base de datos para verlos. ¿Podemos hacer algo similar con RabbitMQ? Sí... ¡podemos! RabbitMQ viene con una preciosa herramienta llamada RabbitMQ Manager. Haz clic para entrar en ella.

Ah, sí, ¡tenemos datos! Y si aprendemos lo que significan algunos de estos términos... ¡estos datos empezarán a tener sentido!

## Intercambios

El primer gran concepto de RabbitMQ es un intercambio... y, para mí, ésta fue la parte más confusa de aprender cómo funciona Rabbit. Cuando envías un mensaje a RabbitMQ, lo envías a un intercambio específico. La mayoría de estos intercambios se crearon automáticamente para nosotros... y puedes ignorarlos. ¿Pero ves ese intercambio de `messages`? Fue creado por nuestra aplicación y, ahora mismo, todos los mensajes que Messenger transporta a RabbitMQ se envían a este intercambio.

Todavía no verás el nombre de este intercambio en nuestra configuración de Messenger, pero cada transporte que utiliza AMQP tiene una opción `exchange` y por defecto es `messages`. ¿Ves esta columna "Tipo"? Nuestro intercambio es de un tipo llamado `fanout`. Haz clic en este intercambio para obtener más información... y abre los "enlaces". Este intercambio tiene una "vinculación" a una "cola" que... por casualidad... también se llama "mensajes".

## Los intercambios envían a las colas

Y aquí es donde las cosas pueden volverse un poco confusas... pero en realidad es una idea sencilla. Los dos conceptos principales en RabbitMQ son los intercambios y las colas. Estamos mucho más familiarizados con la idea de una cola. Cuando utilizábamos el tipo de transporte Doctrine, nuestra tabla de base de datos era básicamente una cola: era una gran lista de mensajes en cola... y cuando ejecutábamos el trabajador, éste leía los mensajes de esa lista.

En RabbitMQ, las colas tienen el mismo papel: las colas contienen mensajes y nosotros leemos mensajes de las colas. Entonces... ¿qué diablos hacen estas cosas de intercambio?

La diferencia clave entre el tipo de transporte Doctrine y AMQP es que con AMQP no envías un mensaje directamente a una cola. No puedes decir

> ¡Hey RabbitMQ! Me gustaría enviar este mensaje a la cola `important_stuff`.

No, en RabbitMQ, envías los mensajes a un intercambio. Entonces, ese intercambio tendrá alguna configuración que dirija ese mensaje a una cola específica... o posiblemente a varias colas. El "Bindings" representa esa configuración.

El tipo de intercambio más sencillo es este `fanout`. Dice que cada mensaje que se envíe a este intercambio debe enviarse a todas las colas que se hayan vinculado a él... que en nuestro caso es sólo una. Las reglas de "vinculación" pueden ser mucho más inteligentes, enviando diferentes mensajes a diferentes colas, pero nos ocuparemos de eso más adelante. Por el momento, toda esta configuración de lujo significa que cada mensaje acabará en última instancia en una cola llamada `messages`.

Hagamos clic en el enlace Colas de la parte superior. Sí, tenemos exactamente una cola: `messages`. Y... ¡eh! Tiene 3 mensajes "Listos" dentro de ella, ¡esperando a que los consumamos!

## auto_setup Exchange & Queues

Por cierto... ¿quién creó el intercambio `messages` y la cola `messages`? ¿Son... estándar para RabbitMQ? Rabbit viene con algunos intercambios fuera de la caja, pero éstos fueron creados por nuestra aplicación. Sí, al igual que con el tipo de transporte Doctrine, el transporte AMQP de Messenger tiene una opción `auto_setup` que por defecto es verdadera. Esto significa que detectará si el intercambio y la cola que necesita existen, y si no es así, los creará automáticamente. Sí, Messenger se encarga de crear el intercambio, de crear la cola y de unirlos con el enlace de intercambio. Tanto el nombre del intercambio como el de la cola son opciones que puedes configurar en tu transporte... y ambos tienen por defecto la palabra `messages`. Veremos esa configuración un poco más adelante.

## Enviar a un intercambio, leer de una cola

Para resumir todo esto: enviamos un mensaje a un intercambio y éste lo reenvía a una o varias colas en función de algunas reglas internas. Quien "envía" -o "produce"- el mensaje sólo dice

> Ir al intercambio llamado "mensajes"

... y en teoría... el "emisor" no sabe ni le importa en qué cola acabará ese mensaje. Una vez que el mensaje está en una cola... ¡simplemente se queda ahí... y espera!

La segunda parte de la ecuación es tu "trabajador", la cosa que consume los mensajes. El trabajador es lo contrario del emisor: no sabe nada de los intercambios. Sólo dice:

> ¡Oye! Dame el siguiente mensaje de la cola de "mensajes".

Enviamos mensajes a los intercambios, RabbitMQ los encamina a las colas, y consumimos de esas colas. El intercambio es una nueva capa extra... pero el resultado final sigue siendo bastante sencillo.

¡Ufff! Antes de intentar ejecutar nuestro trabajador, vamos a cargar 4 fotos. Entonces.... si miras la cola `messages`... y refresca.... ¡ahí está! ¡Tiene 7 mensajes!

## Consumir desde la cola

Como recordatorio, estamos enviando mensajes de `AddPonkaToImage` a `async_priority_high`y de `ImagePostDeletedEvent` a `async`. La idea es que podamos poner diferentes mensajes en diferentes colas y luego consumir mensajes en la cola `async_priority_high`antes de consumir mensajes en la cola `async`. El problema es que... ahora mismo... ¡todo acaba en la misma y única cola!

Comprueba esto: busca tu terminal y consume sólo desde el transporte `async`. Esto debería hacer que sólo se consuman los mensajes de `ImagePostDeletedEvent`:

```terminal-silent
php bin/console messenger:consume -vv async
```

Y... sí, maneja algunos objetos de `ImagePostDeletedEvent`. Pero si sigues observando... una vez que los termina, sí empieza a procesar los mensajes de `AddPonkaToImage`.

Ahora mismo tenemos una configuración AMQP tan sencilla que hemos introducido un error: nuestros dos transportes están enviando exactamente a la misma cola... lo que anula nuestra capacidad de consumirlos de forma prioritaria. Lo arreglaremos a continuación utilizando dos intercambios.

Ah, pero si vuelves al gestor de RabbitMQ, podrás ver todos los mensajes que se están consumiendo. Qué bien.
