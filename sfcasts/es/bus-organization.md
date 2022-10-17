# Organización del bus de eventos y comandos

Ya hemos organizado nuestra nueva clase de eventos en un subdirectorio `Event`. ¡Genial! Hagamos lo mismo con nuestros comandos. Crea un nuevo subdirectorio `Command/`, mueve las dos clases de comandos dentro... y añade `\Command` al final del espacio de nombres de ambas clases.

Veamos... ahora que hemos cambiado esos espacios de nombres... tenemos que actualizar algunas cosas. Empieza en `messenger.yaml`: estamos haciendo referencia a `AddPonkaToImage`. Añade`Command` a ese nombre de clase. A continuación, en `ImagePostController`, arriba del todo, estamos haciendo referencia a ambos comandos. Actualiza el espacio de nombres en cada uno de ellos.

Y por último, en los manejadores, tenemos lo mismo: cada manejador tiene una declaración `use`para la clase de comando que maneja. Añade el espacio de nombres `Command\` en ambos.

¡Genial! Hagamos lo mismo con los manejadores: crea un nuevo subdirectorio llamado`Command/`, muévelos dentro... y añade el espacio de nombres `\Command` a cada uno. Eso es... todo lo que tenemos que cambiar.

¡Me gusta! Este cambio no tiene nada de técnico... sólo es una buena forma de organizar las cosas si piensas utilizar algo más que comandos, es decir, eventos o mensajes de consulta. Y todo funcionará exactamente igual que antes. Para comprobarlo, en tu terminal, ejecuta `debug:messenger`:

```terminal-silent
php bin/console debug:messenger
```

¡Sí! Vemos la misma información que antes.

## Vinculación de los manejadores a un bus

Pero... ahora que hemos separado nuestros manejadores de eventos de nuestros manejadores de comandos... podemos hacer algo especial: podemos vincular cada manejador al bus específico al que está destinado. De nuevo, no es superimportante hacer esto, pero hará que las cosas estén más claras.

Te lo mostraré: abre `config/services.yaml`. Esta línea `App\` se encarga de registrar automáticamente cada clase del directorio `src/` como un servicio en el contenedor.

La línea siguiente repite eso para las clases del directorio `Controller/`. ¿Por qué? Esto anulará los servicios del controlador registrados anteriormente y añadirá una etiqueta especial que los controladores necesitan para funcionar.

Podemos utilizar un truco similar con Messenger. Digamos `App\MessageHandler\Command\`, y luego utilizar la tecla `resource` para volver a registrar todas las clases del directorio`../src/MessageHandler/Command`. Uy, me he equivocado con el nombre del directorio... Veré un gran error en unos minutos... y lo arreglaré.

[[[ code('ca10d47952') ]]]

Si sólo hiciéramos esto... no cambiaría absolutamente nada. Esto registraría todo lo que hay en este directorio como un servicio... pero eso ya lo hace la primera entrada de `App\` de todos modos.

Pero ahora podemos añadir una etiqueta a esto con `name: messenger.message_handler` y`bus:` configurada con... el nombre de mi bus de `messenger.yaml`. Copia`messenger.bus.default` y di `bus: messenger.bus.default`.

[[[ code('d9a5ecd55d') ]]]

Aquí ocurren varias cosas. Primero, cuando Symfony ve una clase en nuestro código que implementa `MessageHandlerInterface`, añade automáticamente esta etiqueta`messenger.message_handler`. Así es como Messenger sabe qué clases son manejadoras de mensajes.

Ahora estamos añadiendo esa etiqueta manualmente para poder decir también exactamente en qué bus se debe utilizar este manejador. Sin la opción `bus`, se añade a todos los buses.

También tenemos que añadir una clave más: `autoconfigure: false`.

[[[ code('dc4fd0597b') ]]]

Gracias a la sección `_defaults` de la parte superior, todos los servicios de nuestro directorio `src/` tendrán, por defecto, activada la opción `autoconfigure`... que es la responsable de añadir automáticamente la etiqueta `messenger.message_handler` a todos los servicios que implementen `MessageHandlerInterface`. La desactivamos para los servicios de este directorio para que la etiqueta no se añada dos veces.

¡Ufff! Puedes ver el resultado final ejecutando de nuevo `debug:messenger`.

```terminal-silent
php bin/console debug:messenger
```

¡Oh, el resultado final es un gran error gracias a mi errata! Asegúrate de que estás haciendo referencia al directorio `MessageHandler`. Prueba de nuevo con `debug:messenger`:

```terminal-silent
php bin/console debug:messenger
```

¡Bien! El bus de eventos ya no dice que podamos enviar los dos comandos. Lo que realmente significa es que los manejadores de comandos se añadieron al bus de comandos, pero no al bus de eventos.

Repitamos esto para los eventos: copia esta sección, pégala, cambia el espacio de nombres a `Event\`, el directorio a `Event` y actualiza la opción `bus` a`event.bus` -el nombre de nuestro otro bus dentro de `messenger.yaml`.

[[[ code('8805e98abe') ]]]

¡Genial! Prueba de nuevo con `debug:messenger`:

```terminal-silent
php bin/console debug:messenger
```

¡Perfecto! Nuestros dos manejadores de comandos están vinculados al bus de comandos y nuestro único manejador de eventos está vinculado al bus de eventos.

Una vez más, hacer este último paso no era tan importante... pero me gustan mucho estos subdirectorios... y ajustar las cosas es agradable.

## Cambiar el nombre del bus de comandos

Ah, pero mientras limpiamos las cosas, de vuelta en `config/packages/messenger.yaml`, nuestro bus principal se llama `messenger.bus.default`, que se convierte en el id de servicio del bus en el contenedor. Usamos este nombre... sólo porque ese es el valor por defecto que usa Symfony cuando sólo tienes un bus. Pero como este es un bus de comandos, ¡llamémoslo así! Cámbiale el nombre a `command.bus`. Y arriba, utiliza eso como nuestro `default_bus`.

[[[ code('f4f8f8d005') ]]]

¿Dónde estaba la antigua clave referenciada en nuestro código? Gracias a que autoconducimos ese servicio a través de su tipo-indicación... casi en ningún sitio: sólo en `services.yaml`. Cambia también la opción del bus a `command.bus`.

[[[ code('a43eab21de') ]]]

Comprueba todo ejecutando `debug:messenger` una vez más:

```terminal-silent
php bin/console debug:messenger
```

Qué bien: dos buses, cada uno con un gran nombre y que sólo conocen los manejadores correctos.

Ah, y este `AuditMiddleware` es algo que realmente deberíamos utilizar también en`event.bus`: registra el recorrido de los mensajes... lo que es igualmente válido aquí.

[[[ code('74800172ea') ]]]

Si te gusta esta organización, ¡genial! Si te parece demasiado, no te compliques: Messenger está aquí para hacer lo que tú quieras. A continuación, vamos a hablar del último tipo de bus de mensajes: el bus de consulta.
