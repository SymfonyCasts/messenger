# Manejar la sincronización de mensajes mientras se desarrolla

Me encanta la posibilidad de aplazar el trabajo para más tarde enviando mensajes a un transporte, pero hay al menos un inconveniente práctico: hace que sea un poco más difícil desarrollar y codificar tu aplicación. Además de configurar tu servidor web, tu base de datos y todo lo demás, ahora tienes que acordarte de ejecutar:

```terminal
php bin/console messenger:consume
```

De lo contrario... las cosas no funcionarán del todo. Si tienes una configuración robusta para el desarrollo local -quizás algo que utilice Docker- podrías incorporar esto a esa configuración para que se ejecute automáticamente. Salvo que... seguirías teniendo que acordarte de reiniciar el trabajador cada vez que hagas un cambio en algún código que utilice.

No es lo peor. Pero, si esto te vuelve loco, hay una solución muy buena: decirle a Messenger que maneje todos tus mensajes de forma sincrónica cuando estés en el entorno `dev`.

## Hola "sync" Transporte

Echa un vistazo a `config/packages/messenger.yaml`. Una de las partes comentadas de este archivo es una especie de transporte "sugerido" llamado `sync`. La parte realmente importante no es el nombre `sync` sino el DSN: `sync://`. Ya hemos aprendido que Messenger admite varios tipos de transporte, como Doctrine, redis y AMQP. Y la forma de elegir cuál quieres es el comienzo de la cadena de conexión, como `doctrine://`. El transporte `sync` es realmente ingenioso: en lugar de enviar realmente cada mensaje a una cola externa... simplemente los maneja inmediatamente. Se manejan de forma sincrónica.

## Hacer que los transportes se sincronicen

Podemos aprovechar esto y utilizar un truco de configuración para cambiar nuestros transportes`async` y `async_priority_high` para utilizar el transporte `sync://` sólo cuando estemos en el entorno `dev`.

Entra en el directorio `config/packages/dev`. Los archivos que se encuentran aquí sólo se cargan en el entorno `dev` y anulan todos los valores del directorio principal `config/packages`. Crea un nuevo archivo llamado `messenger.yaml`... aunque el nombre de este archivo no es importante. Dentro, pondremos la misma configuración que tenemos en nuestro archivo principal: `framework` `messenger` , `transports`. Luego anula `async` y ponle `sync://`. Haz lo mismo con `async_priority_high`: ponle `sync://`.

[[[ code('59134df695') ]]]

Eso es todo En el entorno de desarrollo, estos valores anularán los valores de `dsn` del archivo principal. Y, podemos ver esto: en una pestaña abierta del terminal, ejecuta:

```terminal
php bin/console debug:config framework messenger
```

Este comando te muestra la configuración real y definitiva en `framework` y `messenger`. Y... ¡sí! Como actualmente estamos en el entorno `dev`, ambos transportes tienen un `dsn` establecido en `sync://`.

Quiero mencionar que la opción `queue_name` es algo específico de Doctrine. El transporte `sync` no la utiliza y, por tanto, la ignora. Es posible que en una futura versión de Symfony, esto arroje un error porque estamos utilizando una opción no definida para este transporte. Si eso ocurre, sólo tendríamos que cambiar el formato YAML para establecer la clave `dsn` -como hacemos en el archivo principal`messenger.yaml` - y luego anular la clave `options` y establecerla en una matriz vacía. Lo menciono por si acaso.

Bien, ¡probemos esto! Actualiza la página para estar seguro. Ah, y antes de subir algo, vuelve al terminal donde se está ejecutando nuestro trabajador, pulsa Control+C para detenerlo y reinícialo. ¡Woh! ¡Se ha estropeado!

> No puedes recibir mensajes del transporte de sincronización.

Messenger está diciendo:

> ¡Oye! Um... el SyncTransport no es una cola real de la que puedas leer... así que
> ¡deja de intentar hacerlo!

Es cierto... y esto es exactamente lo que queríamos: queríamos poder hacer llamar a nuestros manejadores en el entorno `dev` sin tener que preocuparnos de ejecutar este comando.

Bien, ahora vamos a probarlo: sube un par de fotos y... sí... vuelve a ser súper lento. Pero Ponka se añade cuando termina. Los mensajes se están gestionando de forma sincrónica.

Para asegurarte de que esto sólo ocurre en el entorno `dev`, abre el archivo`.env` y cambia `APP_ENV` por `prod` temporalmente. Asegúrate de borrar la caché para que esto funcione:

```terminal
php bin/console cache:clear
```

Ahora, deberíamos poder ejecutar `messenger:consume` como antes:

```terminal
php bin/console messenger:consume -vv async_priority_high async
```

Y... ¡podemos! Sincroniza los mensajes en dev, async en prod.

Ahora que hemos conseguido esto, vuelve a cambiar `APP_ENV` por `dev` y, para que las cosas sean más interesantes para el tutorial, comenta la nueva configuración `sync`que acabamos de añadir: Quiero seguir utilizando nuestros transportes reales mientras codificamos. Detén y reinicia el trabajador:

[[[ code('08db7871ee') ]]]

Ahora que estamos de vuelta en el entorno `dev`, para y reinicia el trabajador:

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

A continuación: vamos a hablar de un problema similar: ¿cómo se manejan los transportes al escribir pruebas automatizadas?
