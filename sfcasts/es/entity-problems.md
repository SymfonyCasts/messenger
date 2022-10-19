# Problemas con las entidades en los mensajes

Tenemos un extraño problema: sabemos que `AddPonkaToImageHandler` está siendo llamado con éxito por el proceso trabajador...., ¡porque realmente está añadiendo Ponka a las imágenes! Pero, por alguna razón... aunque llamemos a`$imagePost->markAsPonkaAdded()`... que establece la propiedad `$ponkaAddedAt`... y luego a `$this->entityManager->flush()`... ¡no parece que se esté guardando!

## ¿Quizá nos falte persist()?

Así que... podrías preguntarte:

> ¿Tengo que llamar a persist() en `$imagePost`?

Vamos a probarlo: `$this->entityManager->persist($imagePost)`. En teoría, no deberíamos necesitarlo: sólo hay que llamar a `persist()` en los objetos nuevos que quieras guardar. No es necesario... y normalmente no hace nada... cuando lo llamas en un objeto que se va a actualizar.

Pero... qué demonios... veamos qué ocurre.

## Reiniciar el Trabajador

Pero antes de intentar esto... ¡tenemos que hacer algo muy importante! Busca tu terminal, pulsa Ctrl+C para detener el trabajador y luego reinícialo:

```terminal
php bin/console messenger:consume
```

¿Por qué? Como sabes, los trabajadores se quedan ahí y se ejecutan... para siempre. El problema es que, si actualizas algo de tu código, ¡el trabajador no lo verá! Hasta que lo reinicies, ¡sigue teniendo el código antiguo almacenado en la memoria! Así que cada vez que hagas un cambio en el código que utiliza un trabajador, asegúrate de reiniciarlo. Más adelante, hablaremos de cómo hacer esto de forma segura al desplegar.

## La rareza de las entidades serializadas

Veamos qué ocurre ahora que hemos añadido esa nueva llamada a `persist()`. Sube un nuevo archivo, encuentra tu trabajador y... ¡sí! Se ha gestionado con éxito. ¿Se ha solucionado el problema de guardar la entidad? Actualiza la página.

¡Vaya! ¡Qué acaba de pasar! la imagen aparece dos veces Una con la fecha puesta... y otra sin ella. ¡En la base de datos!

```terminal
SELECT * FROM image_post \G
```

Sí... esta única imagen está en dos filas: Lo sé porque apuntan exactamente al mismo archivo en el sistema de archivos. El trabajador... de alguna manera... duplicó esa fila en la base de datos.

## Mapa de identidad de Doctrine

Este... es un error confuso... pero tiene una fácil solución. Primero, veamos las cosas desde la perspectiva de Doctrine. Internamente, Doctrine mantiene una lista de todos los objetos de entidad con los que está tratando actualmente. Cuando consultas una entidad, la añades a esta lista. Cuando llames a `persist()`, si no está ya en la lista, la añades. Luego, cuando llamamos a `flush()`, Doctrine recorre todos estos objetos, busca los que han cambiado y crea las consultas UPDATE o INSERT adecuadas. Sabe si un objeto debe insertarse o actualizarse porque sabe si fue responsable de la consulta de ese objeto. Por cierto, si quieres empollar más este tema, esta "lista" se llama mapa de identidades... y no es más que una gran matriz que empieza vacía al principio de cada petición y se va agrandando a medida que se hacen consultas o se persiguen cosas.

Así que ahora pensamos en lo que ocurre en nuestro trabajador. Cuando se deserializa el objeto `AddPonkaToImage`, también se deserializa el objeto `ImagePost` que vive dentro. En ese momento, el mapa de identidad de Doctrine no contiene este objeto... porque no lo ha consultado dentro de este proceso PHP - desde dentro del trabajador. Por eso, originalmente, antes de añadir `persist()`, cuando llamábamos a`flush()`, Doctrine miraba la lista de objetos de su mapa de identidad -que estaba vacía- y... no hacía absolutamente nada: ¡no sabe que debe guardar el `ImagePost`!

Cuando añadimos `persist()`, creamos un problema diferente. Ahora Doctrine es consciente de que debe guardarlo... pero como no lo ha consultado originalmente, piensa erróneamente que debe insertarlo en la base de datos como una nueva fila, en lugar de actualizarlo.

¡Uf! Quería que vieras esto porque... es un poco difícil de depurar. Afortunadamente, la solución es fácil. Y toca una importante práctica recomendada para tus mensajes: incluye sólo la información que necesitas. Eso a continuación.