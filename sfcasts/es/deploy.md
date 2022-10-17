# Despliegue y supervisión

Entonces... ¿cómo funciona todo esto en producción? En realidad es un problema sencillo: en producción, tenemos que asegurarnos de alguna manera de que este comando - `messenger:consume` - se ejecute siempre. Como, siempre.

Algunas plataformas de alojamiento -como SymfonyCloud- te permiten hacerlo con una sencilla configuración. Básicamente dices:

> ¡Oye, proveedor de la nube! Por favor, asegúrate de que `bin/console messenger:consume`
> esté siempre en marcha. Si se cierra por alguna razón, inicia uno nuevo.

Si no utilizas una plataforma de alojamiento así, no pasa nada, pero tendrás que hacer un poco de trabajo para obtener el mismo resultado. Y en realidad, no es sólo que necesitemos una forma de asegurarnos de que alguien inicie este comando y luego se ejecute para siempre. En realidad no queremos que el comando se ejecute para siempre. Por muy bien que escribas tu código PHP, éste no está hecho para ejecutarse eternamente: con el tiempo, su huella de memoria aumentará demasiado y el proceso morirá. Y... ¡eso es perfecto! No queremos que nuestro proceso se ejecute para siempre. No: lo que realmente queremos es que `messenger:consume` se ejecute, maneje... unos cuantos mensajes... y luego se cierre. Luego, utilizaremos una herramienta diferente para asegurarnos de que cada vez que el proceso desaparezca, se reinicie.

## Hola Supervisor

La herramienta que hace eso se llama supervisor. Después de instalarla, le das un comando que quieres que se ejecute siempre y se queda despierto toda la noche comiendo pizza constantemente y vigilando que ese comando se ejecute. En el momento en que deja de ejecutarse, por cualquier motivo, deja la pizza y reinicia el comando.

Así que vamos a ver cómo funciona el Supervisor y cómo podemos utilizarlo para asegurarnos de que nuestro trabajador está siempre en marcha. Como estoy utilizando un Mac, ya he instalado el Supervisor a través de Brew. Si usas Ubuntu, puedes instalarlo mediante apt. Por cierto, en realidad no necesitas instalar y configurar Supervisor en tu máquina local: sólo lo necesitas en producción. Lo instalamos para poder probar y asegurarnos de que todo funciona.

## Configuración del Supervisor

Para ponerlo en marcha, necesitamos un archivo de configuración del supervisor. Busca en Google "Messenger Symfony" y abre la documentación principal. En el medio... hay un punto que habla del supervisor. Copia el archivo de configuración. Podemos ponerlo en cualquier sitio: no es necesario que viva en nuestro proyecto. Pero, a mí me gusta tenerlo en mi repo para poder guardarlo en git. En... qué tal `config/`, crea un nuevo archivo llamado `messenger-worker.ini` y pega el código dentro.

[[[ code('ade99551b3') ]]]

El archivo le dice al Supervisor qué comando debe ejecutar y otra información importante como el usuario con el que debe ejecutar el proceso y el número de procesos a ejecutar. Esto creará dos procesos trabajadores. Cuantos más trabajadores se ejecuten, más mensajes se podrán gestionar a la vez. Pero también, más memoria y CPU necesitarás.

Ahora, localmente, no necesito ejecutar el supervisor... porque podemos ejecutar manualmente`messenger:consume`. Pero para asegurarnos de que todo esto funciona, vamos a fingir que mi ordenador es de producción y cambiar la ruta para que apunte a usar mi ruta local:`/Users/weaverryan/messenger`... que si vuelvo a comprobar en mi terminal... oop - me olvidé de la parte `Sites/`. Luego, aquí abajo, cambiaré el usuario para que sea `weaverryan`. De nuevo, normalmente lo establecerás con tus valores de producción.

Ah, y si te fijas bien en el comando, se está ejecutando`messenger:consume async`. Asegúrate de consumir también `async_priority_high`. El comando también tiene una opción `--time-limit=3600`. Hablaremos más de esto y de otras opciones dentro de un rato, pero esto es genial: le dice al trabajador que se ejecute durante 60 minutos y luego salga, para asegurarse de que no envejece demasiado y ocupa demasiada memoria. En cuanto salga, el Supervisor lo reiniciará.

## Ejecutar el Supervisor

Ahora que tenemos nuestro archivo de configuración, tenemos que asegurarnos de que el Supervisor puede verlo. Cada instalación del Supervisor tiene un archivo de configuración principal. En un Mac donde se instala a través de Brew, ese archivo se encuentra en `/usr/local/etc/supervisord.ini`. En Ubuntu, debería estar en `/etc/supervisor/supervisord.conf`.

Entonces, en algún lugar de tu archivo de configuración, encontrarás una sección `include` con una línea`files`. Esto significa que el Supervisor está buscando en este directorio archivos de configuración -como el nuestro- que le dirán qué hacer.

Para meter nuestro archivo de configuración en ese directorio, podemos crear un enlace simbólico:`ln -s ~/Sites/messenger/config/messenger-worker.ini` y luego pegar el directorio.

```terminal-silent
ln -s ~/Sites/messenger/config/messenger-worker.ini /usr/local/etc/supervisor.d/
```

Ya está Ahora el supervisor debería poder ver nuestro archivo de configuración. Para ejecutar el supervisor, utilizaremos algo llamado `supervisorctl`. Como estoy en un Mac, también necesito pasar una opción `-c` y apuntar al archivo de configuración que acabamos de ver. Si estás en Ubuntu, no deberías necesitar hacer esto: ya sabrá dónde buscar. Luego di `reread`: eso le dice al Supervisor que vuelva a leer los archivos de configuración:

```terminal-silent
supervisorctl -c /usr/local/etc/supervisord.ini reread
```

Por cierto, es posible que tengas que ejecutar este comando con `sudo`. Si lo haces, no pasa nada: ejecutará los procesos propiamente dichos como el usuario de tu archivo de configuración.

¡Genial! Ve el nuevo grupo `messager-consume`. Ese nombre procede de la clave que hay en la parte superior de nuestro archivo. A continuación, ejecuta el comando `update`... que reiniciará los procesos con la nueva configuración... si ya estuvieran en marcha... pero los nuestros aún no lo están:

```terminal-silent
supervisorctl -c /usr/local/etc/supervisord.ini update
```

Para iniciarlos, ejecuta `start messenger-consume:*`:

```terminal-silent
supervisorctl -c /usr/local/etc/supervisord.ini start messenger-consume:*
```

El último argumento - `messenger-consume:*` no es muy obvio. Cuando creas un "programa" llamado `messenger-consume`, se crea lo que se llama un "grupo de procesos homogéneos". Como tenemos `processes=2`, este grupo ejecutará dos procesos. Al decir `messenger-consume:*` le dice al Supervisor que inicie todos los procesos dentro de ese grupo.

Cuando lo ejecutamos... no dice nada... pero... ¡nuestros comandos de trabajador deberían estar ejecutándose ahora! Vamos a detener nuestro trabajador manual para que sólo se ejecuten los del Supervisor. Ahora,

```terminal
tail -f var/log/messenger.log
```

Esto hará que sea realmente obvio si nuestros mensajes están siendo gestionados por esos trabajadores. Ahora, sube unas cuantas fotos, borra un par de elementos, muévete y... ¡sí! ¡Está funcionando! En realidad está funcionando casi el doble de rápido de lo normal porque tenemos el doble de trabajadores.

Y ahora podemos divertirnos un poco. En primer lugar, podemos ver los identificadores de proceso creados por el Supervisor ejecutando:

```terminal
ps -A | grep messenger:consume
```

***TIP
También puedes utilizar `ps aux`, que funcionará en más sistemas operativos.
***

Ahí están: 19915 y 19916. Matemos uno de ellos:

```terminal
kill 19915
```

Y ejecutémoslo de nuevo:

```terminal-silent
ps -A | grep messenger:consume
```

¡Sí! 19916 sigue ahí, pero como hemos matado al otro, el supervisor ha iniciado un nuevo proceso para él: 19995. El supervisor es genial.

A continuación, vamos a hablar más sobre las opciones que podemos utilizar para hacer salir a los trabajadores a propósito antes de que ocupen demasiada memoria. También hablaremos de cómo reiniciar los trabajadores al desplegarlos para que vean el nuevo código y un pequeño detalle sobre cómo pueden romperse las cosas si actualizas tu clase de mensajes.
