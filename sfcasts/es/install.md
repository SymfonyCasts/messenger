# Instalar el Messenger

¡Hola amigos! ¡¡Es la hora de Symfony Messenger!! Entonces, ¿qué es Symfony Messenger? Es una herramienta que te permite... um... enviar mensajes... Espera... eso no tiene sentido.

## ¿Qué es Messenger?

Intentémoslo de nuevo. Messenger es una herramienta que permite un patrón de diseño realmente genial en el que escribes "mensajes" y luego otro código que hace algo cuando se envía ese mensaje. Si has oído hablar de CQRS (Command Query Responsibility Segregation), Messenger es una herramienta que permite ese patrón de diseño.

Todo eso está muy bien... y vamos a aprender mucho sobre ello. Pero es muy probable que estés viendo esto porque quieres aprender algo más que hace Messenger: ¡te permite ejecutar código de forma asíncrona con colas y trabajadores! OooooOOoo. Ésa es la verdadera gracia de Messenger.

Ah, y tengo dos argumentos de venta más. En primer lugar, Symfony 4.3 tiene un montón de nuevas características que realmente hacen brillar a Messenger. Y segundo, usar Messenger es una absoluta delicia. Así que... ¡vamos a hacerlo!

## Configuración del proyecto

Si quieres convertirte en un maestro del command-bus-queue-processing-worker-middleware-envelope... y otras palabras de moda... Messenger, calienta tu café y codifica conmigo. Descarga el código del curso desde esta página. Cuando lo descomprimas, encontrarás dentro un directorio`start/` con el mismo código que ves aquí. Abre el archivo`README.md` para obtener todos los detalles sobre cómo poner en marcha el proyecto y un poema totalmente ajeno, pero encantador, llamado "El Mensajero".

El último paso de configuración será encontrar un terminal y utilizar el binario de Symfony para iniciar un servidor web en `https://localhost:8000`:

```terminal-silent
symfony serve
```

Bien, vamos a comprobarlo en nuestro navegador. Saluda a nuestra nueva creación de SymfonyCasts: Ponka-fy Me. Por si no lo sabías, Ponka, de día, es uno de los principales desarrolladores aquí en SymfonyCasts. De noche... es la gata de Víctor. En realidad... debido a su frecuente horario de siesta... no hace nada de codificación... ahora que lo pienso.

## Ponka-fy Me

De todos modos, hemos notado un problema en el que nos vamos de vacaciones, pero Ponka no puede venir... así que cuando volvemos, ¡ninguna de nuestras fotos tiene a Ponka! Ponka-fy Me lo soluciona: seleccionamos una foto de las vacaciones... se carga... y... ¡sí! ¡Mira! ¡Ponka se unió sin problemas a nuestra foto de vacaciones!

Entre bastidores, esta aplicación utiliza un frontend Vue.js... que no es importante para lo que vamos a aprender. Lo que sí es importante saber es que esta carga a un punto final de la API que almacena la foto y luego combina dos imágenes juntas. Eso es algo bastante pesado para hacer en una petición web... y por eso, si te fijas bien, es un poco lento: terminará de subir... esperará... y, sí, luego cargará la nueva imagen de la derecha.

Veamos la ruta de la API para que te hagas una idea de cómo funciona: está en `src/Controller/ImagePostController.php`. Busca en `create()` esta es la punta de la API de carga: coge el archivo, lo valida, utiliza otro servicio para almacenar ese archivo -ese es el método `uploadImage()` -, crea una nueva entidad `ImagePost`, la guarda en la base de datos con Doctrine y luego, aquí abajo, tenemos algo de código para añadir Ponka a nuestra foto. Ese método `ponkafy()` es el que hace el trabajo realmente pesado: toma las dos imágenes, las empalma y... para hacerlo más dramático y lento a efectos de este tutorial, se toma una pausa de 2 segundos para el té.

Sobre todo... todo este código pretende ser bastante aburrido. Claro, he organizado las cosas en unos cuantos servicios... eso está bien, pero todo es muy tradicional. ¡Es un caso de prueba perfecto para Messenger!

## Instalación de Messenger

Así que... ¡vamos a instalarlo! Busca tu terminal, abre una nueva pestaña y ejecuta:

```terminal
composer require messenger
```

Cuando termine... recibimos un "mensaje"... ¡de Messenger! Bueno, de su receta. Esto es genial, pero ya hablaremos de todo esto por el camino.

Además de instalar el componente Messenger, su receta de Flex hizo dos cambios en nuestra aplicación. En primer lugar, modificó `.env`. Veamos... añadió esta configuración de "transporte". Esto se refiere a la puesta en cola de los mensajes -más adelante se hablará de ello- 

[[[ code('932e6f69b2') ]]]

También añadió un nuevo archivo `messenger.yaml`, que... si lo abres... 
es perfectamente... ¡aburrido! Tiene las claves `transports` y `routing` -de nuevo, cosas relacionadas con la cola- pero está todo vacío y no hace nada todavía.

[[[ code('8aa3fd7bc5') ]]]

Así que... ¿qué nos ha aportado la instalación del componente Messenger... aparte de algunas nuevas clases PHP dentro del directorio `vendor/`? Nos dio un nuevo servicio importante. Vuelve a tu terminal y ejecuta:

```terminal
php bin/console debug:autowiring mess
```

¡Ahí está! Tenemos un nuevo servicio que podemos utilizar con este tipo de `MessageBusInterface`. Um... ¿qué hace? No lo sé ¡Pero vamos a averiguarlo a continuación! Además de aprender sobre las clases de mensajes y los manejadores de mensajes.
