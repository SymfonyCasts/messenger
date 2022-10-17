# Envío de manejadores a diferentes transportes: from_transport

La última opción que quiero mencionar es interesante... pero también puede resultar confusa. Se llama `from_transport`.

Si te fijas en `messenger.yaml`, este `DeleteImagePost` no se enruta a ninguna parte, lo que significa que se maneja de forma sincrónica. Imaginemos que queremos manejarlo de forma asíncrona... y que lo dirigimos al transporte `async`. Establece `from_transport` en `async`.. 

[[[ code('a5ba58b0a5') ]]]

y luego dirige temporalmente esta clase a ese transporte en `messenger.yaml`.

Ahora, imagina que el mensaje `DeleteImagePost` tiene realmente dos manejadores... algo que es muy posible para los eventos. Suponiendo que aún no hayamos añadido esta configuración de `from_transport`, si enviaste `DeleteImagePost` al transporte`async`, entonces cuando ese mensaje sea leído desde ese transporte por un trabajador, ambos manejadores se ejecutarán uno tras otro.

Pero, ¿qué pasaría si quisieras, más o menos, enviar un manejador de ese mensaje a un transporte, quizá `async_priority_high`, y otro manejador a otro transporte? Pues bien, en Messenger no se envían "manejadores"... se envían mensajes... y cuando Messenger consume un mensaje, llama a todos los manejadores de ese mensaje. ¿Significa eso que es imposible hacer que un manejador de un mensaje tenga una prioridad "alta" y otro baja? No Este flujo de trabajo es posible.

## Enrutar a dos transportes

En primer lugar, encamina `DeleteImagePost` a los dos transportes `async` y `async_priority_high`

[[[ code('3fb5eeef3c') ]]]

Si sólo hiciéramos esto, el mensaje se enviaría a ambos transportes, se consumiría dos veces, y cada manejador sería llamado dos veces... lo cual no es en absoluto lo que queremos... a menos que cada manejador esté horneando galletas... o algo así.

Pero cuando añadimos esta opción `from_transport` configurada en `async`, significa que este manejador sólo debe ser llamado cuando se consuma un objeto `DeleteImagePost` desde el transporte`async`. Si configuramos un segundo manejador con `from_transport`establecido en `async_priority_high`, ese manejador sólo sería llamado cuando el mensaje se consuma desde ese transporte.

En otras palabras, estás enviando el mensaje a dos transportes, pero cada uno de ellos sabe que sólo debe ejecutar un manejador. Esto permite que tus dos manejadores se pongan en cola y sean ejecutados por los trabajadores de forma independiente el uno del otro. Es una función realmente poderosa... pero como Messenger se centra en el envío de mensajes a los transportes, su uso excesivo puede resultar confuso.

Vamos a comentar esto y a eliminar la configuración de enrutamiento.

[[[ code('194442abf3') ]]]

Eso es básicamente todo en cuanto a las opciones que puedes pasar aquí... aunque siempre puedes consultar `MessageSubscriberInterface`: habla de lo que está disponible.

A continuación, vamos a mejorar nuestro juego de colas cambiando el transporte Doctrine por RabbitMQ, también conocido como AMQP. ¡Es muy divertido!
