# Comando del trabajador

Aunque actualice la página, ahora que nuestros mensajes no se gestionan inmediatamente... las cuatro fotos más recientes no tienen a Ponka. ¡Eso es trágico! En su lugar, esos mensajes fueron enviados al transporte `doctrine` y están esperando pacientemente dentro de una tabla `messenger_messages`.

Entonces... ¿cómo podemos volver a leerlos y procesarlos? Necesitamos algo que pueda recuperar cada fila una a una, deserializar cada mensaje de vuelta a PHP, y luego pasarlo al bus de mensajes para que sea realmente manejado. Esa "cosa" es un comando especial de la consola. Ejecuta:

```terminal
php bin/console messenger:consume
```

No verás ninguna salida... todavía... pero, a no ser que hayamos estropeado algo, esto está haciendo exactamente lo que necesitamos: leer cada mensaje, deserializarlo y enviarlo de vuelta al bus para que lo maneje.

Así que... vamos a refrescar. ¡Woh! ¡Ha funcionado! ¡Los 4 mensajes tienen ahora Ponka! ¡Estamos salvados!

## messenger:consume -vv

Para hacer esto más interesante, como puedes ver, dice que ejecutes este comando con`-vv` si quieres ver lo que está haciendo entre bastidores. Pero... interesante, una vez que el comando ha terminado de leer y manejar los 4 mensajes... no ha abandonado: sigue ejecutándose. Y si lo reiniciamos con `-vv` al final:

```terminal-silent
php bin/console messenger:consume -vv
```

... hace lo mismo. Un comando que "maneja" mensajes de una cola se llama "trabajador". Y el trabajo de un trabajador es observar y esperar a que se añadan nuevos mensajes a la cola y manejarlos en el momento en que se añade uno. Espera y ejecuta... ¡para siempre! Bueno, eso no es del todo cierto, pero hablaremos de ello más adelante, cuando hablemos del despliegue.

Volvamos a echar un vistazo a nuestra "cola": la tabla `messenger_messages`:

```terminal-silent
SELECT * FROM messenger_messages \G
```

¡Sí! Tiene cero filas porque todos esos mensajes se han procesado y eliminado de la cola. De vuelta al navegador, subamos... qué tal... 5 fotos nuevas. Woh... ¡eso fue increíblemente rápido!

Vale, vale, ¡vuelve al terminal que está ejecutando el trabajador! ¡Podemos ver cómo hace su trabajo! Dice: "Mensaje recibido", "Mensaje gestionado por `AddPonkaToImageHandler`" y luego "`AddPonkaToImage` se ha gestionado con éxito (reconociendo)". La última parte, "reconociendo", significa que Messenger ha notificado al transporte Doctrine que el mensaje ha sido gestionado y puede ser retirado de la cola.

Luego... sigue con el siguiente mensaje... y el siguiente... y el siguiente... hasta que termina. Así que si refrescamos... ¡Ponka se ha añadido a todos ellos! Hagámoslo de nuevo: sube 5 fotos más. Y... refresquemos y veamos... ¡ahí está Ponka! Podemos ver cómo se manejan poco a poco. ¡Cuánta maravilla Ponka!

Vale, esto molaría más si nuestro JavaScript refrescara automáticamente la imagen cuando se añadiera Ponka... en lugar de tener que refrescar yo la página... pero eso es un tema totalmente diferente, y que está cubierto por el componente Mercure de Symfony.

Y... ¡eso es todo! Este comando `messenger:consume` es algo que tendrás que ejecutar en producción todo el tiempo. Por ejemplo, podrías decidir ejecutar varios procesos de trabajo. O, incluso, podrías desplegar tu aplicación en un servidor totalmente diferente -uno que no esté gestionando peticiones web- y ejecutar allí los procesos de trabajo. Así, la gestión de estos mensajes no utilizaría ningún recurso de tu servidor web. Hablaremos más sobre el despliegue más adelante.

## Problema: ¿La base de datos no se actualiza?

Porque ahora mismo... tenemos un problema... un problema un poco raro. Actualiza la página. Hmm, las fotos originales dicen algo así como

> Ponka visitó hace 13 minutos. Ponka visitó hace 11 minutos.

Pero, como hemos hecho las cosas asíncronas, todas estas dicen

> Ponka está durmiendo la siesta. Vuelve pronto.

Abre la entidad `ImagePost` y encuentra la propiedad `$ponkaAddedAt`. Se trata de un campo`datetime`, que registra cuándo se añadió Ponka a la foto. El mensaje en el front-end proviene de este valor.

En el caso de los originales... cuando todo el proceso era sincrónico, este campo se establecía con éxito. Pero ahora... parece que no es así. Comprobemos la base de datos para estar seguros. En MySQL, ejecuta:

```terminal
SELECT * FROM image_post \G
```

Al principio... se establecía `ponka_added_at`. Pero ahora están todas en `null`. Así que... nuestras imágenes se están procesando correctamente, pero, por alguna razón, este campo de la base de datos no lo está. Si miramos dentro de`AddPonkaToImageHandler`... sí... justo aquí: `$imagePost->markPonkaAsAdded()`. Eso establece la propiedad. Entonces... ¿por qué no se guarda?

Vamos a averiguar qué está pasando y a aprender un poco más sobre algunas "mejores prácticas" a la hora de construir tu clase de mensaje.
