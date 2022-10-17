# Transporte: Haz el trabajo después (asíncrono)

Hasta ahora, hemos separado las instrucciones de lo que queremos hacer -queremos añadir Ponka a este `ImagePost` - de la lógica que realmente hace ese trabajo. Y... es un buen patrón de codificación: es fácil de probar y si necesitamos añadir Ponka a una imagen desde cualquier otra parte de nuestro sistema, será súper agradable.

Pero este patrón desbloquea algunas posibilidades importantes. Piénsalo: ahora que hemos aislado las instrucciones sobre lo que queremos hacer, en lugar de manejar el objeto comando inmediatamente, ¿no podríamos, en teoría, "guardar" ese objeto en algún sitio... y leerlo y procesarlo después? Así es... básicamente como funciona un sistema de colas. La ventaja es que, dependiendo de tu configuración, podrías poner menos carga en tu servidor web y dar a los usuarios una experiencia más rápida. Por ejemplo, ahora mismo, cuando un usuario hace clic para subir un archivo, tarda unos segundos antes de que finalmente aparezca aquí. No es el mayor problema, pero no es lo ideal. Si podemos arreglarlo fácilmente, ¿por qué no?

## Hola transportes

En Messenger, la clave para "guardar el trabajo para más tarde" es un sistema llamado transportes. Abre `config/packages/messenger.yaml`. ¿Ves esa tecla `transports`? En realidad, los detalles se configuran en `.env`.

La idea es la siguiente: vamos a decirle a Messenger:

> ¡Oye! Cuando cree un objeto `AddPonkaToImage`, en lugar de manejarlo inmediatamente,
> quiero que lo envíes a otro lugar.

Ese "otro lugar" es un transporte. Y un transporte suele ser una "cola". Si eres nuevo en esto de las colas, la idea es refrescantemente sencilla. Una cola es un sistema externo que "retiene" información en una gran lista. En nuestro caso, retendrá los objetos de mensaje serializados. Cuando le enviamos otro mensaje, lo añade a la lista. Más tarde, puede leer esos mensajes de la cola uno a uno, manejarlos y, cuando haya terminado, la cola lo eliminará de la lista.

Claro... los sistemas de cola robustos tienen un montón de otras campanas y silbatos... pero ése es realmente el concepto principal.

## Tipos de transporte

Hay un montón de sistemas de colas disponibles, como RabbitMQ, Amazon SQS, Kafka y colas en el supermercado. Fuera de la caja, Messenger soporta tres: `amqp` -que básicamente significa RabbitMQ, pero técnicamente significa cualquier sistema que implemente la especificación "AMQP"- `doctrine` y `redis`. AMQP es el más potente... pero a menos que ya seas un profesional de las colas y quieras hacer alguna locura, todos ellos funcionan exactamente igual.

Ah, y si necesitas hablar con algún transporte no soportado, Messenger se integra con otra biblioteca llamada Enqueue, que soporta un montón más.

## Activar el transporte de la doctrina

Como ya estoy utilizando Doctrine en este proyecto, vamos a utilizar el transporte `doctrine`. Descomenta la variable de entorno para ello 

[[[ code('a13aa11e17') ]]]

¿Ves esta parte de `://default`? Eso le dice al transporte Doctrine que queremos utilizar la conexión `default` Doctrine. Sí, reutilizará la conexión que ya has configurado en tu aplicación para almacenar el mensaje dentro de una nueva tabla. 
Pronto hablaremos de ello.

***TIP
A partir de symfony 5.1, el código del transporte Doctrine se trasladó a su propio paquete. La única diferencia es que ahora también debes ejecutar este comando: 
`composer require symfony/doctrine-messenger`
***

Ahora, de vuelta en `messenger.yaml`, descomenta este transporte `async`, que utiliza la variable de entorno`MESSENGER_TRANSPORT_DSN` que acabamos de crear. El nombre - `async` - no es importante - podría ser cualquier cosa. Pero, en un segundo, empezaremos a hacer referencia a ese nombre.

[[[ code('d9a42e723e') ]]]

## Enrutamiento a los transportes

Llegados a este punto... ¡vaya! Le hemos dicho a Messenger que tenemos un transporte `async`. Y si quisiéramos volver y subir un archivo ahora, no habría... ninguna diferencia: se seguiría procesando inmediatamente. ¿Por qué?

Porque tenemos que decirle a Messenger que este mensaje debe ser enviado a ese transporte, en lugar de ser tratado ahora mismo.

Volviendo a `messenger.yaml`, ¿ves esta clave `routing`? Cuando enviamos un mensaje, Messenger mira todas las clases de esta lista... que ahora mismo es cero si no cuentas el comentario... y busca nuestra clase - `AddPonkaToImage`. Si no encuentra la clase, maneja el mensaje inmediatamente.

Digámosle a Messenger que, en su lugar, lo envíe al transporte `async`. Establece`App\Message\AddPonkaToImage` en `async`.

[[[ code('5393ecbf11') ]]]

En cuanto lo hagamos, habrá una gran diferencia. Observa lo rápido que se carga la imagen a la derecha después de cargarla. ¡Bum! Ha sido más rápido que antes y... ¡Ponka no está ahí! ¡Jadea!

En realidad, vamos a probar una más - esa primera imagen era un poco lenta porque Symfony estaba reconstruyendo su caché. Esta debería ser casi instantánea. ¡Lo es! En lugar de llamar a nuestro manejador inmediatamente, Messenger está enviando nuestro mensaje al transporte Doctrine.

## Viendo el mensaje en cola

Y... um... ¿qué significa eso en realidad? Busca tu terminal... o cualquier herramienta que te guste utilizar para jugar con las bases de datos. Yo utilizaré el cliente `mysql` para conectarme a la base de datos `messenger_tutorial`. Dentro, vamos:

```terminal
SHOW TABLES;
```

¡Woh! Esperábamos `migration_versions` y `image_post`... pero de repente tenemos una tercera tabla llamada `messenger_messages`. Veamos qué hay ahí:

```terminal
SELECT * FROM messenger_messages;
```

¡Bien! ¡Tiene dos filas para nuestros dos mensajes! Utilicemos la magia `\G` para darle un formato más bonito:

```terminal-silent
SELECT * FROM messenger_messages \G
```

¡Genial! El `body` contiene nuestro objeto: ha sido serializado utilizando la función`serialize()` de PHP... aunque eso puede configurarse. El objeto está envuelto dentro de algo llamado `Envelope`... pero dentro... podemos ver nuestro objeto`AddPonkaToImage` y el `ImagePost` dentro de éste... completo con el nombre del archivo, la fecha `createdAt`, etc.

Espera... ¿pero de dónde viene esta tabla? Por defecto, si no está ahí, Messenger la crea por ti. Si no quieres eso, hay una opción de configuración llamada`auto_setup` para desactivar esto - más adelante te mostraré cómo. Si desactivaste la configuración automática, podrías utilizar el práctico comando `setup-transports` en el despliegue para crear esa tabla por ti.

```terminal-silent
php bin/console messenger:setup-transports
```

Esto no hace nada ahora... porque la tabla ya está ahí.

¡Este fue un gran paso! Cada vez que subimos imágenes... no se gestionan inmediatamente: cuando subimos dos más... se envían a Doctrine y éste hace un seguimiento de ellas. ¡Gracias Doctrine!

A continuación, es el momento de leer esos mensajes uno a uno y empezar a manejarlos. Lo hacemos con un comando de consola llamado "trabajador".
