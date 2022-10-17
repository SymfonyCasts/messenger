# El transporte de fallos

Ahora sabemos que cada mensaje se reintentará 3 veces -lo cual es configurable- y luego, si el manejo sigue fallando, será "rechazado"... lo cual es una palabra de "cola" para: se eliminará del transporte y se perderá para siempre.

Eso es... ¡un fastidio! Nuestro último reintento se produjo 14 segundos después del primero... pero si el gestor falla porque un servidor de terceros está temporalmente fuera de servicio... entonces si ese servidor está fuera de servicio aunque sólo sea durante 30 segundos... ¡el mensaje se perderá para siempre! ¡Sería mejor si pudiéramos reintentarlo una vez que el servidor volviera a funcionar!

La respuesta a esto es... ¡el transporte de fallos!

## Hola Transporte de Fallas

En primer lugar, voy a descomentar un segundo transporte. En general, puedes tener tantos transportes como quieras. Éste comienza con `doctrine://default`. Si miras nuestro archivo `.env`... ¡eh! ¡Eso es exactamente lo que nuestra variable de entorno`MESSENGER_TRANSPORT_DSN` está configurada! Sí, tanto nuestro transporte `async`como el nuevo `failed` están utilizando el transporte `doctrine` y la conexión de doctrina `default`. Pero el segundo también tiene esta pequeña opción `?queue_name=failed`. Oooooooooooo.

[[[ code('81c981f68d') ]]]

Vuelve a lo que estés usando para inspeccionar la base de datos y comprueba la tabla de colas:

```terminal
DESCRIBE messenger_messages;
```

Ah. Una de las columnas de esta tabla se llama `queue_name`. Esta columna nos permite crear varios transportes que almacenan todos los mensajes en la misma tabla. Messenger sabe qué mensajes pertenecen a cada transporte gracias a este valor. Todos los mensajes enviados al transporte `failed` tendrán un valor `failed`... que puede ser cualquier cosa - y los mensajes enviados al transporte `async` utilizarán el valor por defecto... que es `default`.

## Configurar los transportes

Por cierto, cada transporte tiene una serie de opciones de conexión diferentes y hay dos formas de pasarlas: o bien como parámetros de consulta como éste, o bien mediante un formato expandido en el que pones el `dsn` en su propia línea y luego añades una clave `options` con lo que necesites debajo.

¿Qué opciones puedes poner aquí? Cada tipo de transporte -como `doctrine` o `amqp` - tiene su propio conjunto de opciones. Ahora mismo, no están bien documentadas, pero son fáciles de encontrar... si sabes dónde buscar. Por convención, cada tipo de transporte tiene una clase llamada `Connection`. Pulsaré Shift+Shift en PhpStorm, buscaré `Connection.php`... y buscaré los archivos. ¡Ahí están! Una clase `Connection`para Amqp, Doctrine y Redis.

Abre la de Doctrine. Todas estas clases tienen documentación cerca de la parte superior que describe sus opciones, en este caso: `queue_name`, `table_name` y algunas otras, como `auto_setup`. Antes hemos visto que Doctrine creará la tabla`messenger_messages` automáticamente si no existe. Si no quieres que eso ocurra, debes poner `auto_setup` en `false`.

El transporte con más opciones se puede ver en la clase Conexión Amqp. Hablaremos de Amqp más adelante en el tutorial.

## El transporte_fracaso

De todos modos, ¡volvemos a ello! Ahora tenemos un nuevo transporte llamado `failed`... que, a pesar de su nombre, es igual que cualquier otro transporte. Si quisiéramos, podríamos encaminar allí las clases de mensajes y consumirlas, tal y como estamos haciendo con `async`.

Pero... el objetivo de este transporte es diferente. Cerca de la parte superior, hay otra clave llamada `failure_transport`. Descomenta eso y observa que apunta a nuestro nuevo transporte `failed`.

[[[ code('5bc9ca3d83') ]]]

¿Qué hace? ¡Veámoslo en acción! Primero, ve a reiniciar nuestro trabajador:

```terminal-silent
php bin/console messenger:consume -vv
```

¡Woh! Esta vez, nos pregunta qué "receptor" -que básicamente significa qué "transporte"- queremos consumir. Un trabajador puede leer de uno o varios transportes, algo de lo que hablaremos más adelante con los transportes "priorizados". Vamos a consumir sólo el transporte `async` - manejaremos los mensajes del transporte `failed` de otra manera. Y en realidad, para facilitarnos la vida, podemos pasar `async` como argumento para que no nos pregunte qué transporte utilizar cada vez:

```terminal-silent
php bin/console messenger:consume -vv async
```

Ahora... ¡vamos a subir algunas imágenes! Entonces... por aquí... rápidamente, las 4 agotan sus reintentos y acaban siendo rechazadas por el transporte. Hasta ahora, eso significaba que habían desaparecido para siempre. Pero esta vez... eso no ocurrió. Antes de eliminar el mensaje de la cola, dice

> El mensaje rechazado `AddPonkaToImage` se enviará al transporte de fallo "fallido"

Y luego... "Enviando mensaje". Por tanto, se ha eliminado del transporte `async`, pero sigue existiendo porque se ha enviado al transporte "fallido".

¿Cómo podemos ver qué mensajes han fallado y volver a intentarlo si pensamos que esos fallos eran temporales? Con un par de brillantes y nuevos comandos de consola. Hablemos de ellos a continuación.
