# Despacho del evento y sin manejadores

De vuelta a `DeleteImagePostHandler`, tenemos que despachar nuestro nuevo mensaje`ImagePostDeletedEvent`. Anteriormente, creamos un segundo servicio de bus de mensajes. Ahora tenemos un bus que utilizamos como bus de comandos llamado `messenger.bus.default`y otro llamado `event.bus`. Gracias a esto, cuando ejecutamos

```terminal
php bin/console debug:autowiring mess
```

ahora podemos autoconectar cualquiera de estos servicios. Si sólo utilizamos la sugerencia de tipo `MessageBusInterface`, obtendremos el bus de comandos principal. Pero si utilizamos ese type-hint y nombramos el argumento `$eventBus` nos dará el otro.

Dentro de `DeleteImagePostHandler`, cambia el argumento a `$eventBus`. No es necesario, pero también voy a cambiar el nombre de la propiedad a `$eventBus` para mayor claridad. Ah, y las variables necesitan un `$` en PHP. Perfecto

[[[ code('303f4b730d') ]]]

Dentro de `__invoke()`, es realmente lo mismo que antes: `$this->eventBus->dispatch()`
con `new ImagePostDeletedEvent()` pasando que `$filename`.

[[[ code('910a58c952') ]]]

¡Y ya está! El resultado final de todo este trabajo... fue hacer lo mismo que antes, pero con algún cambio de nombre para que coincida con el patrón del "bus de eventos". El manejador realiza su tarea principal -eliminar el registro de la base de datos- y luego envía un evento que dice

> ¡Se acaba de borrar un registro de imagen! Si a alguien le interesa... ¡haz algo!

## Enrutamiento de eventos

De hecho, a diferencia de lo que ocurre con los comandos, cuando enviamos un evento... en realidad no nos importa si hay algún controlador para él. Podría haber cero, 5, 10... ¡no nos importa! No vamos a utilizar ningún valor de retorno de los manejadores y, a diferencia de los comandos, no vamos a esperar que ocurra nada específico. Simplemente gritarás al espacio:

> ¡Oye! ¡Se ha eliminado un ImagePost!

De todos modos, la última pieza que tenemos que arreglar para que esto sea realmente idéntico a lo de antes es, en `config/packages/messenger.yaml`, debajo de `routing`, dirigir`App\Message\Event\ImagePostDeletedEvent` al transporte `async`.

[[[ code('dc0a84cae3') ]]]

¡Vamos a probarlo! Busca tu trabajador y reinícialo. Toda esta refactorización giraba en torno al borrado de imágenes, así que... borremos un par de cosas, volvamos a pasar y... ¡sí! ¡Funciona de maravilla! `ImagePostDeletedEvent` se está despachando y manejando.

## ¿Manejar algunos manejadores de forma asíncrona?

Ah, y una nota al margen sobre el enrutamiento. Cuando enrutas una clase de comando, sabes exactamente qué manejador tiene. Y así, es súper fácil pensar en lo que hace ese manejador y determinar si puede o no manejarse de forma asíncrona.

Con los eventos, es un poco más complicado: esta clase de evento podría tener múltiples manejadores. Y, en teoría, puedes querer que algunos se gestionen inmediatamente y otros más tarde. Como Messenger está construido en torno al enrutamiento de los mensajes a los transportes -no a los manejadores-, hacer que algunos manejadores estén sincronizados y otros asincrónicos no es natural. Sin embargo, si necesitas hacerlo, es posible: puedes encaminar un mensaje a varios transportes, y luego configurar Messenger para que sólo llame a un manejador cuando se reciba del transporte A y sólo al otro manejador cuando se reciba del transporte B. Es un poco más complejo, así que no recomiendo hacerlo a menos que lo necesites. No hablaremos de cómo en este tutorial, pero está en los documentos.

## Los eventos pueden no tener manejadores

De todos modos, ya he mencionado que, para los eventos, es legal a nivel filosófico no tener manejadores... aunque probablemente no lo harás en tu aplicación porque... ¿qué sentido tiene enviar un evento sin manejadores? Pero... por probar, abre `RemoveFileWhenImagePostDeleted` y quita la parte de`implements MessageHandleInterface`.

[[[ code('298d329ef1') ]]]

Lo hago temporalmente para ver qué pasa si Symfony ve cero manejadores para un evento. Vamos a... ¡descubrirlo! De nuevo en el navegador, intenta eliminar una imagen. ¡Funciona! Espera... oh, me olvidé de detener el trabajador... hagámoslo... y vuelve a intentarlo. Esta vez... funciona... pero en el registro del trabajador... ¡Error crítico!

> Se ha producido una excepción al manejar `ImagePostDeletedEvent`: no hay manejador para el mensaje.

Por defecto, Messenger exige que cada mensaje tenga al menos un manejador. Eso es para ayudarnos a evitar errores tontos. Pero... para un bus de eventos... sí queremos permitir cero manejadores. De nuevo... esto es más un problema filosófico que real: es poco probable que decidas enviar eventos que no tienen manejadores. Pero, ¡vamos a ver cómo solucionarlo!

En `messenger.yaml`, quita el `~` de `event.bus` y añade una nueva opción debajo:`default_middleware: allow_no_handlers`. La opción `default_middleware` está por defecto en `true` y su propósito principal es permitirte establecerla en `false` si, por alguna razón, quisieras eliminar por completo el middleware por defecto, el que maneja y envía los mensajes, entre otras cosas. Pero también puedes establecerlo en`allow_no_handlers` si quieres mantener el middleware normal, pero indicando a `HandleMessageMiddleware` que no debe entrar en pánico si hay cero manejadores.

[[[ code('96db3c2c63') ]]]

Vuelve y reinicia el trabajador. Luego, borra otra imagen... vuelve aquí y... ¡guay! Dice "No hay manejador para el mensaje" pero no se asusta y provoca un fallo.

Así que ahora nuestro bus de comandos y nuestro bus de eventos tienen una pequeña diferencia... aunque siguen siendo casi idénticos... y realmente podríamos seguir enviando tanto comandos como eventos a través del mismo bus. Vuelve a poner el `MessageHandlerInterface` en la clase... y reinicia nuestro trabajador una vez más.

[[[ code('f5c0278b7b') ]]]

Ahora que nos sentimos bien con los eventos... Tengo una pregunta: ¿cuál es la diferencia entre enviar un evento a Messenger y enviar un evento al EventDispatcher de Symfony?

Vamos a hablar de eso a continuación.
