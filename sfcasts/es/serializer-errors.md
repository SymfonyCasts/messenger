# Fallo Gracioso en el Serializador de Transporte

Nuestro nuevo y brillante transporte `external_messages` lee los mensajes de esta cola`messages_from_external`, que fingimos que está siendo rellenada por una aplicación externa. Tomamos este JSON y, en`ExternalJsonMessengerSerializer`, lo descodificamos, creamos el objeto `LogEmoji`, lo ponemos en `Envelope`, incluso le añadimos un sello, y finalmente lo devolvemos, para que pueda ser enviado de nuevo a través del sistema de bus de mensajes.

## Fallo en caso de JSON inválido

¡Esto tiene muy buena pinta! Pero hay dos mejoras que quiero hacer. En primer lugar, no hemos codificado de forma muy defensiva. Por ejemplo, ¿qué pasa si, por alguna razón, el mensaje contiene JSON no válido? Comprobemos eso: si `null === $data`, entonces lanza un `new MessageDecodingFailedException('Invalid JSON')`

[[[ code('3e129a0740') ]]]

Te mostraré por qué usamos exactamente esta clase de excepción dentro de un minuto. Pero probemos esto con algún JSON no válido y... veamos qué ocurre. Ve a reiniciar el trabajador para que vea nuestro nuevo código:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Luego, en el gestor RabbitMQ, vamos a cometer un error JSON muy molesto: añadir una coma después de la última propiedad. ¡Publica ese mensaje! Bien, muévete y... ¡explosión!

> MessageDecodingFailedException: JSON inválido

Ah, y es interesante: ¡esto mató a nuestro proceso de trabajo! Sí, si se produce un error durante el proceso de descodificación, la excepción mata a tu trabajador. No es lo ideal... pero en realidad... no es un problema. En producción, ya estarás utilizando algo como el supervisor, que reiniciará el proceso cuando muera.

## Fallo por falta de un campo JSON

Añadamos código para comprobar un posible problema diferente: comprobemos si falta esta clave `emoji`: si no `isset($data['emoji'])`, esta vez lanza una excepción normal: `throw new \Exception('Missing the emoji key!')`.

[[[ code('4266ab41a8') ]]]

Bien, pasa y reinicia el trabajador:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

De nuevo en Rabbit, elimina la coma extra y cambia `emoji` por `emojis`. Publica! En el terminal... ¡genial! ¡Ha explotado! Y aparte de la clase de excepción... parece idéntico al fallo que vimos antes:

> Excepción: ¡Falta la tecla emoji!

Pero... acaba de ocurrir algo diferente. Intenta volver a ejecutar el trabajador:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

¡Woh! ¡Ha explotado! Falta la tecla emoji. Ejecútalo de nuevo:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

## La magia de la MessageDecodingFailedException

¡El mismo error! Ésta es la diferencia entre lanzar un`Exception` normal en el serializador y el especial `MessageDecodingFailedException`. Cuando lanzas un `MessageDecodingFailedException`, tu serializador está diciendo básicamente:

> ¡Oye! Algo ha ido mal... y quiero lanzar una excepción. Pero,
> creo que deberíamos descartar este mensaje de la cola: no tiene sentido
> de intentarlo una y otra vez. ¡Kthxbai!

Y eso es súper importante. Si no descartamos este mensaje, cada vez que nuestro trabajador se reinicie, fallará con ese mismo mensaje... una y otra vez... para siempre. Cualquier mensaje nuevo empezará a acumularse detrás de él en la cola.

Así que cambiemos el `Exception` por `MessageDecodingFailedException`. Pruébalo ahora:

[[[ code('f339b1ef8b') ]]]

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

Explotará la primera vez... pero el `MessageDecodingFailedException` debería haberlo eliminado de la cola. Cuando ejecutemos el trabajador ahora:

```terminal-silent
php bin/console messenger:consume -vv external_messages
```

¡Sí! El mensaje ha desaparecido y la cola está vacía.

A continuación, vamos a añadir un superpoder más a este serializador. ¿Qué pasa si ese sistema externo envía a nuestra aplicación muchos tipos diferentes de mensajes: no sólo un mensaje para registrar emojis, sino quizá también mensajes para borrar fotos o cocinar una pizza? ¿Cómo puede nuestro serializador averiguar qué mensajes son cada uno... y qué objeto de mensaje debe crear?
