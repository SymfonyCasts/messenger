# Probando con el transporte "en memoria

Hace unos minutos, sólo en el entorno `dev`, hemos anulado todos nuestros transportes para que todos los mensajes se manejen de forma sincrónica. Por ahora lo hemos comentado, pero esto es algo que también podrías hacer en tu entorno `test`, para que cuando ejecutes las pruebas, los mensajes se manejen dentro de la prueba.

Esto puede ser o no lo que quieres. Por un lado, significa que tu prueba funcional está probando más. Por otro lado, una prueba funcional probablemente debería probar que la ruta funciona y que el mensaje se envía al transporte, pero la prueba del propio manejador debería hacerse en una prueba específica para esa clase.

Eso es lo que vamos a hacer ahora: encontrar una forma de no ejecutar los manejadores de forma sincrónica, pero probar que el mensaje se ha enviado al transporte. Por supuesto, si matamos al trabajador, podemos consultar la tabla `messenger_messages`, pero eso es un poco complicado y sólo funciona si utilizas el transporte Doctrine. Afortunadamente, hay una opción más interesante.

Empieza copiando `config/packages/dev/messenger.yaml` y pegándolo en`config/packages/test/`. Esto nos da una configuración de Messenger que sólo se utilizará en el entorno `test`. Descomenta el código y sustituye `sync` por`in-memory`. Hazlo para los dos transportes.

[[[ code('83a3e94953') ]]]

El transporte `in-memory` es realmente genial. De hecho, ¡vamos a verlo! Voy a pulsar`Shift+Shift` en PhpStorm y buscaré `InMemoryTransport` para encontrarlo.

Esto... es básicamente un transporte falso. Cuando se le envía un mensaje, no lo maneja ni lo envía a ningún sitio, lo almacena en una propiedad. Si utilizaras esto en un proyecto real, los mensajes desaparecerían al final de la petición.

Pero, esto es súper útil para hacer pruebas. Vamos a probarlo. Hace un segundo, cada vez que ejecutamos nuestra prueba, nuestro trabajador empezó a procesar esos mensajes... lo cual tiene sentido: realmente los estábamos entregando al transporte. Ahora, borraré la pantalla y luego ejecutaré:

```terminal
php bin/phpunit
```

Sigue funcionando... pero ahora el trabajador no hace nada: el mensaje ya no se envía realmente al transporte y se pierde al final de nuestras pruebas. Pero, desde la prueba, ahora podemos recuperar ese transporte y preguntarle cuántos mensajes se le han enviado

## Obtener el servicio de transporte

Entre bastidores, cada transporte es en realidad un servicio del contenedor. Busca tu terminal abierto y ejecuta:

```terminal
php bin/console debug:container async
```

Ahí están: `messenger.transport.async` y`messenger.transport.async_priority_high`. Copia el segundo id de servicio.

Queremos verificar que el mensaje `AddPonkaToImage` se envía al transporte, y sabemos que se dirige a `async_priority_high`.

De vuelta a la prueba, esto es superguay: podemos obtener el objeto de transporte exacto que se acaba de utilizar desde dentro de la prueba diciendo:`$transport = self::$container->get()` y pegando luego el id de servicio`messenger.transport.async_priority_high`

[[[ code('b00f9a8299') ]]]

Esta propiedad `self::$container` contiene el contenedor que se utilizó realmente durante la petición de la prueba y está diseñada para que podamos obtener lo que queramos de él.

Veamos qué aspecto tiene esto: `dd($transport)`.

[[[ code('a304563f40') ]]]

Ahora vuelve a tu terminal y ejecuta:

```terminal
php bin/phpunit
```

¡Bien! Esto vuelca el objeto `InMemoryTransport` y... ¡la propiedad `sent` contiene efectivamente nuestro objeto de mensaje! Todo lo que tenemos que hacer ahora es añadir una aserción para esto.

De vuelta a la prueba, voy a ayudar a mi editor añadiendo algunos documentos en línea para anunciar que esto es un `InMemoryTransport`. A continuación, añade `$this->assertCount()` para afirmar que esperamos que se devuelva un mensaje cuando digamos `$transport->`... veamos... el método al que puedes llamar en un transporte para obtener los mensajes enviados, o "en cola", es `get()`.

[[[ code('b5621e1b35') ]]]

¡Vamos a probarlo! Ejecuta:

```terminal
php bin/phpunit
```

¡Lo tengo! Ahora estamos garantizando que el mensaje se ha enviado, pero hemos mantenido nuestras pruebas más rápidas y dirigidas al no intentar manejarlas de forma sincrónica. Si utilizáramos algo como RabbitMQ, tampoco necesitaríamos tenerlo en marcha cada vez que ejecutamos nuestras pruebas.

A continuación, ¡hablemos del despliegue! ¿Cómo ejecutamos nuestros trabajadores en producción... y nos aseguramos de que siguen funcionando?
