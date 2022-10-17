# Matar a los trabajadores antes de tiempo y en el despliegue

Ejecuta:

```terminal
php bin/console messenger:consume --help
```

Ya hemos visto que tiene una opción llamada `--time-limit`, que puedes utilizar para decirle al comando que se ejecute durante 60 minutos y luego salga. El comando también tiene otras dos opciones - `--memory-limit` - para decirle al comando que salga una vez que su uso de memoria esté por encima de un determinado nivel - o `--limit` - para decirle que ejecute un número específico de mensajes y luego salga. Todas estas opciones son estupendas porque realmente no queremos que nuestro comando `messenger:consume` se ejecute demasiado tiempo: en realidad sólo queremos que gestione unos pocos mensajes y luego salga. El reinicio del trabajador es gestionado por el Supervisor y no requiere una gran cantidad de recursos. Todas estas opciones hacen que el trabajador salga con elegancia, es decir, que sólo salga después de que se haya gestionado completamente un mensaje, nunca en medio de él. Pero, si dejas que tu trabajador se ejecute demasiado tiempo y se queda sin memoria... eso haría que saliera en medio de la gestión de un mensaje y... bueno... eso no es bueno. Utiliza estas opciones. Incluso puedes utilizarlas todas a la vez.

## Reiniciar los trabajadores al desplegar

También hay una situación completamente diferente en la que quieres que todos tus trabajadores se reinicien: siempre que hagas un despliegue. Ya hemos visto muchas veces por qué: cada vez que hacemos un cambio en nuestro código, reiniciamos manualmente el comando `messenger:consume`para que el trabajador vea el nuevo código. Lo mismo ocurrirá en producción: cuando despliegues, tus trabajadores no verán el nuevo código hasta que salgan y se reinicien. En este momento, eso puede tardar hasta seis minutos en ocurrir, lo que no está bien. No, en el momento en que desplegamos, necesitamos que todos o procesos worker salgan, y necesitamos que eso ocurra con gracia.

Afortunadamente, Symfony nos cubre la espalda. Una vez más, ejecuta `ps -A` para ver los procesos trabajadores.

```terminal-silent
ps -A | grep messenger:consume
```

Ahora, imagina que acabamos de desplegar. Para detener todos los trabajadores, ejecuta

```terminal
php bin/console messenger:stop-workers
```

Vuelve a comprobar los procesos:

```terminal-silent
ps -A | grep messenger:consume
```

¡Ja! ¡Perfecto! Los dos nuevos identificadores de proceso demuestran que los trabajadores se han reiniciado! ¿Cómo funciona esto? ¡Por arte de magia! Es decir, el almacenamiento en caché. En serio.

Entre bastidores, este comando envía una señal a cada trabajador para que salga. Pero los trabajadores son inteligentes: no salen inmediatamente, sino que terminan el mensaje que están manejando y luego salen: una salida elegante. Para enviar esta señal, Symfony establece una bandera en el sistema de caché, y cada trabajador comprueba esta bandera. Si tienes una configuración multiservidor, tendrás que asegurarte de que la "caché de la aplicación" de Symfony se almacena en algo como Redis o Memcache en lugar de en el sistema de archivos, para que todos puedan leer esas claves.

## Qué ocurre cuando despliegas los cambios de la clase de mensajes

Hay un detalle más en el que debes pensar y se debe a la naturaleza asíncrona del manejo de los mensajes. Abre `AddPonkaToImage`. Imagina que nuestro sitio está actualmente desplegado y la clase `AddPonkaToImage` tiene este aspecto. Cuando alguien sube una imagen, serializamos esta clase y la enviamos al transporte.

Imagina ahora que tenemos un montón de estos mensajes en la cola en el momento en que desplegamos una nueva versión de nuestro sitio. En esta nueva versión, hemos refactorizado la clase `AddPonkaToImage`: hemos cambiado el nombre de `$imagePostId` por el de `$imagePost`. ¿Qué ocurrirá cuando se carguen esas antiguas versiones de `AddPonkaToImage` desde la cola?

La respuesta... la nueva propiedad `$imagePost` será nula... y en su lugar se establecería una propiedad`$imagePostId` inexistente. Y eso probablemente causaría a tu manejador algún problema serio. Así que, si necesitas modificar algunas propiedades de una clase de mensaje existente, tienes dos opciones. Primero, no lo hagas: crea una nueva clase de mensaje. Luego, después de desplegarla, elimina la antigua clase de mensaje. O, en segundo lugar, actualizar la clase de mensaje pero, temporalmente, mantener tanto las propiedades antiguas como las nuevas y hacer que tu manejador sea lo suficientemente inteligente como para buscar ambas. De nuevo, después de un despliegue, o realmente, una vez que estés seguro de que todos los mensajes antiguos han sido procesados, puedes eliminar lo antiguo.

Y... ¡eso es todo! Utiliza el Supervisor para mantener tus procesos en marcha y el comando`messenger:stop-workers` para reiniciar el despliegue. Ya estás listo para poner esto en producción.

Antes de continuar, voy a buscar mi terminal y ejecutar:

```terminal
supervisorctl -c /usr/local/etc/supervisord.ini stop messenger-consume:*
```

Eso detiene los dos procesos. Ahora ejecutaré mi trabajador manualmente:

```terminal-silent
php bin/console messenger:consume -vv async_priority_high async
```

Esto facilita la vida y la hace más evidente a nivel local: Puedo ver la salida de mi trabajador.

Siguiente: hemos hablado de los comandos y de los manejadores de comandos. Ahora es el momento de hablar de los eventos y los manejadores de eventos, de cómo podemos utilizar Messenger como un bus de eventos y... de qué diablos significa eso.
