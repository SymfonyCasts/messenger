# Intercambio de Claves de Enrutamiento y Vinculación

Vamos a cambiar este retraso a un segundo... para no esperar todo el día a que se procesen nuestras fotos.

[[[ code('cd12692aea') ]]]

## Configuración sencilla: 1 Intercambio Fanout por cola

En `messenger.yaml`, los mensajes enviados a cada transporte - `async` y`async_priority_high` - deben entregarse finalmente en dos colas diferentes para que podamos consumirlos de forma independiente. Y... ¡lo hemos conseguido!

Pero hay dos formas diferentes en las que podríamos haber hecho esto. En primer lugar, recuerda que en AMQP los mensajes se envían a un intercambio, no a una cola. Ahora mismo, cuando un mensaje se dirige al transporte `async`, Messenger lo envía a un intercambio llamado `messages`. No ves esa configuración aquí sólo porque `messages` es el nombre de intercambio por defecto en Messenger.

Cuando un mensaje se dirige al transporte `async_priority_high`, Messenger lo envía a un intercambio llamado `messages_high_priority`. Cada transporte siempre envía a exactamente un intercambio.

Entonces, cada intercambio enruta cada mensaje a una sola cola, como el intercambio `messages`envía a una cola `messages`... y `messages_high_priority` envía a una cola `messages_high`. No hay una clave de enrutamiento en la vinculación: Messenger vincula cada intercambio a una cola... pero sin clave de enrutamiento. Así es como funciona un intercambio "fanout": no le importan las claves de enrutamiento... simplemente envía cada mensaje a cada cola vinculada a él.

## 1 Intercambio directo a 2 colas

Ésa es una forma de resolver el problema. La otra forma consiste en tener un único intercambio... pero haciéndolo lo suficientemente inteligente como para enviar algunos mensajes a la cola `messages` y otros mensajes a `messages_high`. Lo hacemos con claves de enlace y enrutamiento más inteligentes... que ya vimos con el intercambio `delays`.

## Configurar un intercambio directo

Vamos a refactorizar nuestros transportes para utilizar este sistema "más inteligente". En el transporte `async`, añade `options`, luego `exchange`, y establece `name` en `messages`. Si nos detuviéramos aquí, esto no cambiaría nada: este es el nombre de intercambio por defecto en Messenger.

[[[ code('ac1475fe07') ]]]

Pero ahora, añade una clave `type` configurada como `direct`. Esto sí cambia las cosas: el valor por defecto es `fanout`. Añade una clave más debajo de esta `default_publish_routing_key`
configurada en `normal`.

[[[ code('343ffab36d') ]]]

Hablaré de ello en un segundo. A continuación, añade una sección `queues`. Vamos a "vincular" este intercambio a una cola llamada `messages_normal`. Pero no nos detendremos ahí Debajo de esto, añade `binding_keys` ajustado a `[normal]`.

[[[ code('5eb3bee106') ]]]

Esa palabra `normal` podría ser cualquier cadena. Pero no es casualidad que coincida con lo que hemos establecido para `default_publish_routing_key`.

## Borrar todos los intercambios y colas

En lugar de hablar mucho sobre lo que hará esto... ¡vamos a verlo en acción! Haz clic para eliminar una foto: eso debería enviar un mensaje al transporte `async`. ¡Pero la llamada AJAX explota! Abre el perfilador para ver el error. Ah:

> Error del canal del servidor: 406, mensaje: PRECONDITION_FAILED - inequivalent arg
> 'tipo' para el intercambio 'mensajes': recibido 'directo' pero el actual es 'fanout'

El problema es que ya tenemos un intercambio llamado `messages `, que es de tipo `fanout`... pero ahora estamos intentando utilizarlo como un intercambio `direct`. ¡AMQP nos avisa de que estamos intentando hacer una locura!

Así que vamos a empezar de nuevo. Ahora que estamos haciendo las cosas de una manera nueva, vamos a pulsar el botón de reinicio y permitir que Messenger cree todo lo nuevo.

Busca tu terminal - yo cerraré la sesión de MySQL - y detén tu trabajador... de lo contrario seguirá intentando crear tus intercambios y colas con la antigua configuración.

Luego vuelve al administrador de RabbitMQ, borra el intercambio `messages`... y luego el intercambio `messages_high_priority`. Y aunque las colas no se verán diferentes, para estar más seguros, borremos también las dos.

Así volvemos a no tener colas y a tener sólo los intercambios originales que creó AMQP -que de todas formas no estamos utilizando- y el intercambio `delays`. ¡Estamos empezando de cero!

Volvamos a nuestro sitio, borremos la segunda imagen y... ¡parece que ha funcionado! Genial! ¡Veamos qué ha pasado dentro de RabbitMQ! ¡Sí! Tenemos un nuevo intercambio llamado`messages` y es de tipo directo. Dentro, tiene un único enlace que dice

> Cuando se envía un mensaje a este intercambio con una clave de enrutamiento llamada `normal`,
> se entregará a la cola llamada `messages_normal`.

Todo esto se ha establecido gracias a la configuración de `queues` y `binding_keys`. Esto le dice a Messenger:

> Quiero que cree una cola llamada `messages_normal`. Además, asegúrate de que
> que hay un enlace en el intercambio que dirigirá cualquier mensaje con una
> clave de enrutamiento establecida en `normal` a esta cola.

Pero... ¿envió Messenger el mensaje con esa clave de enrutamiento? Hasta ahora, aparte de las cosas de retraso, Messenger ha estado entregando nuestros mensajes a AMQP sin clave de enrutamiento. La configuración de `default_publish_routing_key` cambia eso. Dice:

> Cada vez que un mensaje se enruta al transporte `async`, quiero
> que lo envíe al intercambio `messages` con una clave de enrutamiento establecida en `normal`.

Todo esto significa que si miramos las colas... ¡sí! ¡Tenemos una cola `message_normal`con un mensaje esperando dentro! ¡Lo hemos conseguido!

A continuación, repitamos esto para el otro transporte. Entonces, aprenderemos cómo esto nos da la flexibilidad de controlar dinámicamente dónde se entregará un mensaje en el momento en que lo enviemos.
