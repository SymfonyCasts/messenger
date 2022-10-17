# Prueba funcional del punto final de carga

¿Cómo podemos escribir pruebas automatizadas para todo esto? Bueno... Tengo muchas respuestas para eso. En primer lugar, podrías hacer pruebas unitarias de tus clases de mensajes. Normalmente no lo hago... porque esas clases suelen ser muy sencillas... pero si tu clase es un poco más compleja o quieres ir a lo seguro, puedes hacer pruebas unitarias totalmente.

Más importantes son los manejadores de mensajes: definitivamente es una buena idea probarlos. Podrías escribir pruebas unitarias y simular las dependencias o escribir una prueba de integración... dependiendo de lo que sea más útil para lo que hace cada manejador.

La cuestión es: para las clases de mensajes y manejadores de mensajes... probarlas no tiene absolutamente nada que ver con Messenger o transportes o async o workers: son simplemente clases PHP bien escritas que podemos probar como cualquier otra cosa. Esa es realmente una de las cosas bonitas de Messenger: por encima de todo, sólo estás escribiendo código bonito.

Pero las pruebas funcionales son más interesantes. Por ejemplo, abre`src/Controller/ImagePostController.php`. El método `create()` es la ruta de subida y hace un par de cosas: como guardar el `ImagePost` en la base de datos y, lo más importante para nosotros, enviar el objeto `AddPonkaToImage`.

Escribir una prueba funcional para este punto final es, en realidad, bastante sencillo, pero ¿qué pasaría si quisiéramos poder probar no sólo que este punto final "parece" haber funcionado, sino también que el objeto `AddPonkaToImage` fue, de hecho, enviado al transporte? Al fin y al cabo, no podemos probar que Ponka se ha añadido realmente a la imagen porque, cuando se devuelve la respuesta, ¡todavía no ha ocurrido!

## Configuración de la prueba

Primero vamos a poner en marcha la prueba funcional, antes de ponernos elegantes. Empieza por encontrar un terminal abierto y ejecutar:

```terminal
composer require phpunit --dev
```

Eso instala el `test-pack` de Symfony, que incluye el puente de PHPUnit - una especie de "envoltura" alrededor de PHPUnit que nos facilita la vida. Cuando termina, nos dice que escribamos nuestras pruebas dentro del directorio `tests/` -una idea brillante- y que las ejecutemos ejecutando `php bin/phpunit`. Ese pequeño archivo acaba de ser añadido por la receta y se encarga de todos los detalles de la ejecución de PHPUnit.

Bien, primer paso: crear la clase de prueba. Dentro de `tests`, crea un nuevo directorio `Controller/`y luego una nueva clase PHP: `ImagePostControllerTest`. En lugar de hacer que ésta extienda la normal `TestCase` de PHPUnit, extiende `WebTestCase`, lo que nos dará los superpoderes de prueba funcional que merecemos... y necesitamos. La clase vive en FrameworkBundle pero... ¡ten cuidado porque hay (gasp) dos clases con este nombre! La que quieres vive en el espacio de nombres `Test`. La que no quieres vive en el espacio de nombres `Tests`... así que es súper confuso. Debería ser así. Si eliges la equivocada, borra la declaración `use` e inténtalo de nuevo.

[[[ code('e4e1fc7dd3') ]]]

Pero .... mientras escribía este tutorial y me enfadaba por esta parte confusa, creé una incidencia en el repositorio de Symfony. Y estoy encantado de que cuando grabé el audio, ¡la otra clase ya había sido renombrada! Gracias a [janvt](https://github.com/janvt) que se ha lanzado a ello. ¡Adelante con el código abierto!

De todos modos, como vamos a probar la ruta `create()`, añade`public function testCreate()`. Dentro, para asegurarme de que las cosas funcionan, voy a probar mi favorito `$this->assertEquals(42, 42)`.

[[[ code('a29494d17b') ]]]

## Ejecutando la prueba

Fíjate en que no he obtenido ningún autocompletado en esto. Eso es porque el propio PHPUnit no se ha descargado todavía. Compruébalo: busca tu terminal y ejecuta las pruebas con:

```terminal
php bin/phpunit
```

Este pequeño script utiliza Composer para descargar PHPUnit en un directorio separado en segundo plano, lo que es bueno porque significa que puedes obtener cualquier versión de PHPUnit, incluso si algunas de sus dependencias chocan con las de tu proyecto.

Una vez hecho esto... ¡ding! Nuestra única prueba está en verde. Y la próxima vez que ejecutemos

```terminal
php bin/phpunit
```

salta directamente a las pruebas. Y ahora que PHPUnit está descargado, una vez que PhpStorm construya su caché, ese fondo amarillo en `assertEquals()` desaparecerá.

## Probando el punto final de carga

Para probar la ruta en sí, primero necesitamos una imagen que podamos subir. Dentro del directorio `tests/`, vamos a crear un directorio `fixtures/` para contener esa imagen. Ahora copiaré una de las imágenes que he estado subiendo a este directorio y la llamaré `ryan-fabien.jpg`.

Ahí lo tienes. La prueba en sí es bastante sencilla: crear un cliente con`$client = static::createClient()` y un objeto `UploadedFile` que representará el archivo que se está subiendo: `$uploadedFile = new UploadedFile()` pasando la ruta del archivo como primer argumento - `__DIR__.'/../fixtures/ryan-fabien.jpg` - y el nombre del archivo como segundo - `ryan-fabien.jpg`.

[[[ code('5c7266be16') ]]]

¿Por qué el segundo argumento, un poco "redundante"? Cuando subes un archivo en un navegador, éste envía dos informaciones: el contenido físico del archivo y el nombre del archivo en tu sistema de archivos.

Finalmente, podemos hacer la petición: `$client->request()`. El primer argumento es el método... que es `POST`, luego la URL - `/api/images` - no necesitamos ningún parámetro GET o POST, pero sí necesitamos pasar un array de archivos.

[[[ code('51b446e8e8') ]]]

Si te fijas en `ImagePostController`, esperamos que el nombre del archivo subido -que normalmente es el atributo `name` del campo `<input` - sea literalmente `file`. No es el nombre más creativo... pero es sensato. Utiliza esa clave en nuestra prueba y ponla en el objeto `$uploadedFile`.

[[[ code('a3f5d9abdd') ]]]

Y... ¡ya está! Para ver si ha funcionado, vamos a`dd($client->getResponse()->getContent())`.

[[[ code('a3f5d9abdd') ]]]

¡Hora de probar! Busca tu terminal, limpia la pantalla, respira profundamente y...

```terminal
php bin/phpunit
```

¡Ya está! Y obtenemos un nuevo identificador cada vez que lo ejecutamos. Los registros de `ImagePost` se guardan en nuestra base de datos normal porque no me he tomado la molestia de crear una base de datos distinta para mi entorno `test`. Eso es algo que normalmente me gusta hacer.

## Afirmar el éxito

Elimina el `dd()`: vamos a utilizar una aserción real: `$this->assertResponseIsSuccessful()`.

[[[ code('276fcce839') ]]]

Este bonito método se añadió en Symfony 4.3... y no es el único: ¡este nuevo`WebTestAssertionsTrait` tiene un montón de nuevos y bonitos métodos para probar un montón de cosas!

Si nos detenemos ahora... esta es una bonita prueba y podrías estar perfectamente satisfecho con ella. Pero... hay una parte que no es ideal. Ahora mismo, cuando ejecutamos nuestra prueba, el mensaje `AddPonkaToImage` se envía realmente a nuestro transporte... o al menos creemos que lo hace... no estamos verificando realmente que esto haya ocurrido... aunque podemos comprobarlo manualmente ahora mismo.

Para que esta prueba sea más útil, podemos hacer una de estas dos cosas. En primer lugar, podríamos anular los transportes para que sean síncronos en el entorno de prueba, como hicimos con `dev`. Entonces, si el manejo del mensaje fallara, nuestra prueba fallaría.

O, en segundo lugar, podríamos al menos escribir algo de código aquí que demuestre que el mensaje se envió al menos al transporte. Ahora mismo, es posible que la ruta devuelva 200... pero algún error en nuestro código hizo que el mensaje nunca se enviara.

Añadamos esa comprobación a continuación, aprovechando un transporte especial "en memoria".
