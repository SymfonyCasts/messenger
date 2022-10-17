# AMQP con RabbitMQ

Abre tu archivo `.env` y comprueba la configuración `MESSENGER_TRANSPORT_DSN`. Hemos estado utilizando el tipo de transporte Doctrine. La cadena `doctrine://default` dice que los mensajes deben almacenarse utilizando la conexión `default` de Doctrine. En`config/packages/messenger.yaml`, estamos haciendo referencia a esta variable de entorno para los transportes `async` y `async_priority_high`.

Así que... ¡sí! Hemos estado almacenando los mensajes en una tabla de la base de datos. Ha sido rápido de configurar, fácil de usar -porque ya entendemos de bases de datos- y suficientemente robusto para la mayoría de los casos de uso.

## Hola AMQP... RabbitMQ

Pero el "sistema de colas" o "corredor de mensajes" estándar de la industria no es una tabla de base de datos, es algo llamado AMQP, o "Protocolo Avanzado de Colas de Mensajes". AMQP no es en sí mismo una tecnología... es un "estándar" de cómo debe funcionar un, así llamado, "sistema de corredor de mensajes". Luego, diferentes sistemas de colas pueden "implementar" este estándar. Sinceramente, normalmente cuando alguien habla de AMQP, se refiere a una herramienta concreta: RabbitMQ.

La idea es la siguiente: de la misma manera que lanzas un "servidor de base de datos" y le haces consultas, puedes lanzar una "instancia de Rabbit MQ" y enviar mensajes a ella y recibir mensajes de ella. A alto nivel... no funciona de forma muy diferente a nuestra simple tabla de base de datos: introduces mensajes... y los solicitas después.

Entonces... ¿cuáles son las ventajas de utilizar RabbitMQ en lugar de Doctrine? Quizás... ¡nada! Lo que quiero decir es que, si sólo utilizas las funciones estándar de Messenger y nunca profundizas en ellas, ambas funcionarán bien. Pero si tienes un sistema muy escalado o quieres utilizar algunas funciones avanzadas y específicas de RabbitMQ, bueno... entonces... ¡RabbitMQ es la respuesta!

¿Cuáles son esas características más avanzadas? Bueno, quédate conmigo en los próximos capítulos y empezarás a descubrirlas.

## Lanzar una instancia a través de CloudAMQP.com

La forma más sencilla de poner en marcha una instancia de RabbitMQ es a través de `cloudamqp.com`: un servicio impresionante para RabbitMQ basado en la nube... ¡con una capa gratuita para que podamos jugar! Después de iniciar sesión, crea una nueva instancia, dale un nombre, selecciona cualquier región... sí, queremos el nivel gratuito y... "Crear instancia".

## Configuración del transporte AMQP

¡Genial! Haz clic en la nueva instancia para encontrar... ¡una hermosa cadena de conexión AMQP! Cópiala, ve a buscar nuestro archivo `.env`... y pégala sobre `doctrine://default`. También puedes poner esto en un archivo `.env.local`... que es lo que yo haría normalmente para evitar comprometer estas credenciales.

***TIP
La URL que has copiado empezará ahora por `amqps://` (¡con una "s"!). Eso es AMQP "seguro". Cámbialo a `amqp://` para que las cosas funcionen. La compatibilidad con SSL se introdujo en Symfony 5.2, pero requiere una configuración adicional.
***

En cualquier caso, la parte `amqp://` activa el transporte AMQP en Symfony... y el resto contiene un nombre de usuario, una contraseña y otros detalles de la conexión. En cuanto hagamos este cambio, nuestros dos transportes `async` y `async_priority_high`... ¡ahora utilizan RabbitMQ! ¡Ha sido fácil!

Ah, pero fíjate en que sigo utilizando `doctrine` para mi transporte de fallos... y voy a mantenerlo. El transporte de fallos es un tipo especial de transporte... y resulta que el tipo de transporte `doctrine` es el que más funciones tiene para revisar los mensajes fallidos. Puedes utilizar AMQP para esto, pero yo recomiendo Doctrine.

Antes de probar esto, quiero hacer otro cambio. Abre`src/Controller/ImagePostController.php` y busca el método `create()`. Este es el controlador que se ejecuta cada vez que subimos una foto... y es el responsable de enviar el comando `AddPonkaToImage`. También añade un retraso de 500 milisegundos a través de este sello. Comenta esto por ahora... Te mostraré por qué lo hacemos un poco más tarde.

[[[ code('c67362577c') ]]]

## La extensión AMQP de PHP

¡Muy bien! Aparte de eliminar ese retraso, todo lo que hemos hecho es cambiar nuestra configuración de transporte de Doctrine a AMQP. Veamos... ¡si las cosas siguen funcionando! En primer lugar, asegúrate de que tu trabajador no se está ejecutando... para empezar. Luego, busca tu navegador, selecciona una foto y... ¡funciona! Bueno, espera... porque puede que te haya salido un gran error AJAX. Si es así, abre el perfilador de esa petición. Estoy bastante seguro de saber qué error verás

> Se ha intentado cargar la clase "AMQPConnection" desde el espacio de nombres global.
> ¿Olvidaste una declaración "use"?

Pues... ¡no! Bajo el capó, el tipo de transporte AMQP de Symfony utiliza una extensión de PHP llamada... bueno... ¡amqp! Es un complemento de PHP -como xdebug o pdo_mysql- que probablemente tendrás que instalar.

Lo malo de las extensiones de PHP es que su instalación puede variar en función de tu sistema. En el caso de Ubuntu, puedes ejecutar

```terminal
sudo apt-get install php-amqp
```

O puedes usar pecl, como hice yo con mi instalación Homebrew para Mac:

```terminal
pecl install amqp
```

Una vez que consigas instalarla, asegúrate de reiniciar el servidor web Symfony para que vea el cambio. Si tienes problemas para configurarlo, háznoslo saber en los comentarios y haremos lo posible por ayudarte

Cuando esté todo configurado, deberías poder subir una foto sin errores. Y... como esto no tenía errores... probablemente... ¿se envió a RabbitMQ? Cuando actualizo, dice "Ponka is napping"... porque nada ha consumido nuestro mensaje todavía. Bueno, vamos a ver qué pasa. Busca tu terminal y consume los mensajes de nuestros dos transportes:

```terminal
php bin/console messenger:consume -vv async_priority_high async
```

Y... ¡ahí está! Ha recibido el mensaje, lo ha gestionado... ¡y ya está! Cuando actualizamos la página... ¡ahí está Ponka! ¡Ha funcionado! Pasar de Doctrine a RabbitMQ fue tan sencillo como cambiar nuestra cadena de conexión.

A continuación, vamos a profundizar en lo que acaba de ocurrir entre bastidores: ¿qué significa "enviar" un mensaje a RabbitMQ o "obtener" un mensaje de él? Ah, y te van a encantar las herramientas de depuración de RabbitMQ.
