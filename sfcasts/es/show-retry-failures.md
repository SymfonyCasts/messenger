# Investigar y reintentar mensajes fallidos

Aparentemente, ahora que hemos configurado un `failure_transport`, si el tratamiento de un mensaje sigue sin funcionar después de 3 reintentos, en lugar de enviarse a `/dev/null`, se envían a otro transporte, en nuestro caso llamado "fallido". Ese transporte es... realmente... el mismo que cualquier otro transporte... y podríamos utilizar el comando `messenger:consume` para intentar procesar de nuevo esos mensajes.

Pero, hay una forma mejor. Ejecuta:

```terminal
php bin/console messenger
```

## Ver los mensajes en la cola fallida

Oye, aquí se esconden nuevos y brillantes comandos Tres en `messenger:failed`. Prueba el de `messenger:failed:show`:

```terminal-silent
php bin/console messenger:failed:show
```

¡Bien! Ahí están nuestros 4 mensajes fallidos... esperando a que los miremos. Imaginemos que no estamos seguros de qué ha fallado en estos mensajes y queremos comprobarlo. Empieza pasando el identificador 115:

```terminal-silent
php bin/console messenger:failed:show 115
```

Me encanta esto: ¡nos muestra el mensaje de error, la clase de error y un historial de las desventuras del mensaje a través de nuestro sistema! Ha fallado, ha sido redistribuido al transporte asíncrono en 05, en 06 y luego en 07, finalmente ha fallado y ha sido redistribuido al transporte `failed`.

Si añadimos un `-vv` en el comando...

```terminal-silent
php bin/console messenger:failed:show 115 -vv
```

Ahora podemos ver un seguimiento completo de la pila de lo que ocurrió en esa excepción.

Esta es una forma realmente poderosa de averiguar qué ha fallado y qué hacer a continuación: ¿tenemos un error en nuestra aplicación que debemos solucionar antes de volver a intentar esto? ¿O tal vez fue un fallo temporal y podemos volver a intentarlo ahora? O tal vez, por alguna razón, queramos eliminar este mensaje por completo.

Si quieres eliminarlo sin reintentarlo, ese es el comando`messenger:failed:remove`.

## Reintentar mensajes fallidos

Pero... ¡vamos a reintentar esto! De nuevo en el manejador, cambia esto para que falle aleatoriamente.

[[[ code('b1bf4ef9ef') ]]]

Hay dos formas de trabajar con el comando reintentar: puedes reintentar un id específico como el que ves aquí o puedes reintentar los mensajes uno a uno. Vamos a hacerlo. Ejecuta:

```terminal
php bin/console messenger:failed:retry
```

Esto es algo parecido a cómo funciona `messenger:consume`, excepto que te pregunta antes de intentar cada mensaje y, en lugar de ejecutar este comando todo el tiempo en producción, lo ejecutarás manualmente cada vez que tengas algunos mensajes fallidos que necesites procesar.

¡Genial! Vemos los detalles y nos pregunta si queremos volver a intentarlo. Al igual que con show, puedes pasar `-vv` para ver los detalles completos del mensaje. Di "sí". Se procesa... y continúa con el siguiente. De hecho, déjame intentarlo de nuevo con `-vv` para que podamos ver lo que ocurre:

```terminal-silent
php bin/console messenger:failed:retry -vv
```

## Cuando los mensajes fallidos... Vuelven a fallar

Esta vez vemos todos los detalles. Vuelve a decir "sí" y... bien: "Mensaje recibido", "Mensaje gestionado" y al siguiente mensaje. ¡Estamos en racha! Fíjate en que el identificador de este mensaje es el 117 - eso será importante en un segundo. Pulsa "Sí" para reintentar este mensaje también.

¡Vaya! ¡Esta vez ha vuelto a fallar! ¿Qué significa esto? Bueno, recuerda que el transporte de fallos es en realidad un transporte normal que utilizamos de forma especial. Y así, cuando un mensaje falla aquí, Messenger... ¡lo reintenta! Sí, ¡se envió de nuevo al transporte de fallos!

Pulsaré Control+C y volveré a ejecutar el comando show:

```terminal-silent
php bin/console messenger:failed:show
```

Ese id 119 no estaba allí cuando empezamos. No, cuando se procesó el mensaje 117, falló, se volvió a enviar al transporte de fallos como id 119, y luego se eliminó. Y así, a menos que cambies la configuración, los mensajes se reintentarán 3 veces en el transporte de fallos antes de ser finalmente descartados por completo.

Pero si miras el mensaje reintentado más de cerca

```terminal-silent
php bin/console messenger:failed:show 119 -vv
```

Hay un pequeño error: faltan el error y la clase de error. Los datos siguen en la base de datos... sólo que no se muestran correctamente aquí. Pero puedes ver el historial del mensaje: incluso que fue enviado al transporte `failed` y luego enviado de nuevo al transporte `failed`.

Por cierto, puedes pasar una opción `--force` al comando `retry` si quieres que reintente los mensajes uno a uno sin preguntarte cada vez si debe hacerlo o no. Además, no todos los tipos de transporte -como AMQP o Redis- soportan todas las características que acabamos de ver si lo usas como transporte de fallos. Eso puede cambiar en el futuro, pero en este momento -Doctrine es el transporte más robusto para usar en caso de fallos.

De todos modos, por muy chulo que sea fallar, volvamos atrás y eliminemos el código que está rompiendo nuestro manejador. Porque... es hora de dar un paso más en el funcionamiento de Messenger: es hora de hablar del middleware.
