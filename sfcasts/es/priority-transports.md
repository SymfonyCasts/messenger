# Transportes de alta prioridad

Los dos mensajes que enviamos al transporte `async` son`AddPonkaToImage` y `DeletePhotoFile`, que se encargan de eliminar el archivo físico del sistema de archivos. Y... el segundo no es algo que el usuario note o le importe realmente, es sólo una tarea de mantenimiento. Si ocurriera dentro de 5 minutos o dentro de 10 días, al usuario no le importaría.

Esto crea una situación interesante. Nuestro trabajador maneja las cosas según el principio de "primero en entrar, primero en salir": si enviamos 5 mensajes al transporte, el trabajador los manejará en el orden en que los haya recibido. Esto significa que si se borran un montón de imágenes y luego alguien sube una nueva foto... el trabajador procesará todos esos mensajes de borrado antes de añadir finalmente Ponka a la foto. Y eso... no es lo ideal.

La verdad es que los mensajes de `AddPonkaToImage` deberían tener una prioridad más alta en nuestro sistema que los de `DeletePhotoFile`: siempre queremos que `AddPonkaToImage` se gestione antes que cualquier mensaje de `DeletePhotoFile`... aunque se hayan añadido primero.

## Crear el transporte de "alta" prioridad

Entonces... ¿podemos establecer una prioridad en los mensajes? No exactamente. Resulta que en el mundo de las colas, esto se resuelve creando varias colas y dando a cada una de ellas una prioridad. En Symfony Messenger, eso se traduce en múltiples transportes.

Debajo del transporte `async`, crea un nuevo transporte llamado, qué tal,`async_priority_high`. Utilicemos el mismo DSN que antes, que en nuestro caso es `doctrine`. Debajo, añade `options`, y luego `queue_name` ajustado a `high`. El nombre `high` no es importante - podríamos usar cualquier cosa. La opción `queue_name` es específica del transporte Doctrine y, en última instancia, controla el valor de una columna de la tabla, que funciona como una categoría y nos permite tener varias "colas" de mensajes dentro de la misma tabla. Y además, para cualquier transporte, puedes configurar estas opciones como parámetros de consulta en el DSN o bajo esta clave `options`.

[[[ code('71a4ec092d') ]]]

En este momento tenemos tres colas, todas ellas almacenadas en la misma tabla de la base de datos, pero con diferentes valores de `queue_name`. Y ahora que tenemos este nuevo transporte, podemos dirigir `AddPonkaToImage` a `async_priority_high`.

[[[ code('926687b4d2') ]]]

## Consumir transportes prioritarios

Si nos detenemos ahora... lo único que hemos hecho realmente es posibilitar el envío de estas dos clases de mensajes diferentes a dos colas distintas. Pero no hay nada especial en `async_priority_high`. Claro, he puesto la palabra "alto" en su nombre, pero no es diferente de `async`.

La verdadera magia viene del trabajador. Busca tu terminal donde se esté ejecutando el trabajador y pulsa Control+C para detenerlo. Si sólo ejecutas `messenger:consume` sin ningún argumento y tienes más de un transporte, te pregunta qué transporte quieres consumir:

```terminal
php bin/console messenger:consume
```

Es decir, de qué transporte quieres recibir mensajes. Pero en realidad, puedes leer mensajes de varios transportes a la vez y decirle al trabajador cuál debe leer primero. Fíjate en esto: Yo digo `async_priority_high, async`.

Esto le dice al trabajador: primero pregunta a `async_priority_high` si tiene algún mensaje. Si no lo tiene, entonces ve a comprobar el transporte `async`.

Deberíamos ver esto en acción. Actualizaré la página, borraré un montón de imágenes aquí tan rápido como pueda y luego subiré un par de fotos. Comprueba la salida del terminal:

Se maneja `DeletePhotoFile` y luego... `AddPonkaToImage`, otro `AddPonkaToImage`, otro `AddPonkaToImage` y... ¡sí! Vuelve a gestionar el `DeletePhotoFile` de menor prioridad.

Así que, al principio -antes de la carga- sí que consumía unos cuantos mensajes de `DeletePhotoFile`. Pero en cuanto vio un mensaje en ese transporte `async_priority_high`, los consumió todos hasta que estuvo vacío. Cuando lo estaba, volvía a consumir mensajes de `async`.

Básicamente, cada vez que el trabajador busca el siguiente mensaje, comprueba primero el transporte de mayor prioridad y sólo pregunta al siguiente transporte -o transportes- si está vacío.

Y... ¡ya está! Crea un nuevo transporte para el número de "niveles" de prioridad que necesites, y luego dile al comando del trabajador en qué orden debe procesarlos. Ah, y en lugar de utilizar esta forma interactiva de hacer las cosas, puedes ejecutar:

```terminal
php bin/console messenger:consume async_priority_high async
```

Perfecto. A continuación, vamos a hablar de una opción que podemos utilizar para facilitar el desarrollo mientras usamos colas... porque tener que recordar siempre que hay que ejecutar el comando trabajador mientras se codifica puede ser un dolor.
