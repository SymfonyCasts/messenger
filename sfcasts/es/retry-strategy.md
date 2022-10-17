# Retraso en el reintento y estrategia de reintento

Por defecto, un mensaje se reintentará tres veces y luego se perderá para siempre. Pues bien, en unos minutos... Te mostraré cómo puedes evitar que incluso esos mensajes se pierdan.

De todos modos... el proceso... ¡simplemente funciona! Y es aún más genial de lo que parece a primera vista. Es un poco difícil de ver -sobre todo porque hay una suspensión en nuestro manejador- pero este mensaje se envió para el reintento nº 3 en la marca de tiempo de 13 segundos y finalmente se manejó de nuevo en la marca de tiempo de 17 segundos -un retraso de 4 segundos-. Ese retraso no se debió a que nuestro trabajador estuviera ocupado hasta entonces: fue 100% intencionado.

Compruébalo: Pulsaré Ctrl+C para detener el trabajador y luego lo ejecutaré:

```terminal
php bin/console config:dump framework messenger
```

Esto debería darnos un gran árbol de configuración "de ejemplo" que puedes poner bajo la tecla `framework` `messenger` config. Me encanta este comando: es una forma estupenda de encontrar opciones que quizá no sabías que existían.

¡Genial! Fíjate bien en la clave `transports`: debajo aparece un transporte de "ejemplo" con todas las opciones de configuración posibles. Una de ellas es `retry_strategy`, donde podemos controlar el número máximo de reintentos y el retardo que debe haber entre esos reintentos.

Este número `delay` es más inteligente de lo que parece: funciona junto con el "multiplicador" para crear un retardo exponencialmente creciente. Con esta configuración, el primer reintento se retrasará un segundo, el segundo 2 segundos y el tercero 4 segundos.

Esto es importante porque, si un mensaje falla debido a algún problema temporal -como la conexión a un servidor de terceros-, es posible que no quieras volver a intentarlo inmediatamente. De hecho, puedes optar por establecer estos valores mucho más altos para que se reintente quizás 1 minuto o incluso un día después.

Probemos también un comando similar:

```terminal
php bin/console debug:config framework messenger
```

En lugar de mostrar una configuración de ejemplo, esto nos dice cuál es nuestra configuración actual, incluyendo cualquier valor por defecto: nuestro transporte `async` tiene un `retry_strategy`, que por defecto tiene 3 reintentos máximos con un retraso de 1000 milisegundos y un multiplicador de 2.

## Configurar el retardo

Hagamos esto un poco más interesante. En el manejador, hagamos que siempre falle añadiendo `|| true`.

[[[ code('5abb95e95c') ]]]

Ahora, en `messenger`, juguemos con la configuración del reintento. Espera... pero el transporte `async`está configurado como una cadena... ¿podemos incluir opciones de configuración bajo eso? No Bueno, sí, más o menos. En cuanto necesites configurar un transporte más allá de los detalles de la conexión, tendrás que colocar esta cadena en la siguiente línea y asignarla a una clave `dsn`. Ahora podemos añadir `retry_strategy`, y vamos a establecer el retraso en 2 segundos en lugar de 1.

[[[ code('4f54fafce9') ]]]

Ah, y también quiero mencionar esta tecla `service`. Si quieres controlar completamente la configuración del reintento -incluso tener una lógica de reintento diferente por mensaje- puedes crear un servicio que implemente `RetryStrategyInterface` y poner su id de servicio -normalmente su nombre de clase- aquí mismo.

En cualquier caso, veamos qué ocurre con el retraso más largo: reinicia el proceso del trabajador:

```terminal-silent
php bin/console messenger:consume -vv
```

Esta vez, sube sólo una foto para que podamos ver cómo falla una y otra vez. Y... ¡sí! Falla y envía para el reintento nº 1... luego vuelve a fallar y envía para el reintento nº 2. ¡Pero fíjate en el retraso! del 09 al 11 - 2 segundos - luego del 11 al 15 - un retraso de 4 segundos. Y... si... somos... súper... pacientes... ¡sí! El reintento nº 3 comienza 8 segundos después. Entonces es "rechazado" - eliminado de la cola - y perdido para siempre. ¡Trágico!

Los reintentos son geniales... pero no me gusta esa última parte: cuando el mensaje se pierde finalmente para siempre. Cambia el reintento a 500: así será más fácil de probar 

[[[ code('96e3408eba') ]]]

A continuación, vamos a hablar de un concepto especial llamado "transporte de fallos": 
una alternativa mejor que permitir que los mensajes fallidos simplemente... desaparezcan.
