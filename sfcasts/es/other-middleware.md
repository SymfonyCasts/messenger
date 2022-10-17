# Middleware de transacción y validación de Doctrine

Ahora estamos utilizando tanto un patrón de bus de comandos, en el que creamos comandos y manejadores de comandos, como el patrón de bus de eventos: tenemos nuestro primer evento y manejador de eventos. La diferencia entre un comando y un evento... es un poco sutil. Cada comando debe tener exactamente un manejador: estamos ordenando que algo realice una acción concreta: `AddPonkaToImage`. Pero un evento es algo que suele despacharse después de que se realice esa acción, y el propósito es permitir que cualquier otra persona realice alguna acción secundaria: reaccionar a la acción.

## Dos autobuses... ¿Por qué?

Obviamente, el propio Messenger es una herramienta lo suficientemente genérica como para poder utilizarla en estos dos casos de uso. Abre `config/packages/messenger.yaml`. Hemos decidido registrar un servicio de bus que estamos utilizando como nuestro bus de comandos y otro servicio de bus que estamos utilizando como nuestro bus de eventos. Pero... ¡en realidad no hay casi ninguna diferencia entre estos dos buses! Un bus no es más que una colección de middleware... así que las únicas diferencias son que el primero tiene`AuditMiddleware`... que también podríamos añadir al segundo... y que le dijimos al `HandleMessageMiddleware` del bus de eventos que permitiera "sin manejadores" para un mensaje: si un evento tiene cero manejadores, no lanzará una excepción.

Pero realmente... esto es tan poco importante que si quisieras utilizar un solo bus para todo, funcionaría muy bien.

## Middleware de Validación, Transacción Doctrine, etc

Sin embargo, hay algunas personas que hacen sus buses de comandos y eventos un poco más diferentes. Busca en Google "Symfony Messenger multiple buses" para encontrar un artículo que habla de cómo gestionar varios buses. En este ejemplo, los documentos muestran tres buses diferentes: el bus de comandos, un bus de consultas -del que hablaremos en un minuto- y un bus de eventos. Pero cada bus tiene un middleware ligeramente diferente.

Estos dos middleware - `validation` y `doctrine_transaction` - vienen automáticamente con Symfony pero no están activados por defecto. Si añades el middleware `validation`, cuando envíes un mensaje, ese middleware validará el propio objeto mensaje a través del validador de Symfony. Si la validación falla, lanzará un`ValidationFailedException` que puedes atrapar en tu código para leer los errores.

Esto es genial... pero no lo vamos a utilizar porque prefiero validar mis datos antes de enviarlos al bus. Simplemente tiene más sentido para mí y parece un poco más sencillo que una capa, en cierto modo, "invisible" que haga la validación por nosotros. Pero, es algo totalmente válido para usar.

El middleware `doctrine_transaction` es similar. Si activas este middleware, envolverá tu manejador dentro de una transacción Doctrine. Si el manejador lanza una excepción, revertirá la transacción. Y si no se lanza ninguna excepción, la confirmará. Esto significa que tu gestor no tendrá que llamar a `flush()` en el EntityManager: el middleware lo hace por ti.

Esto es genial... pero me parece bien crear y gestionar yo mismo las transacciones de Doctrine si las necesito. Así que éste es otro bonito middleware que me gusta, pero que no utilizo.

De todos modos, si utilizas más middleware del que estamos utilizando, entonces tus diferentes buses podrían empezar a ser... realmente más diferentes... y utilizar múltiples servicios de bus tendría más sentido. Como con todo, si el enfoque más sencillo -usar un solo bus para todo- te funciona, ¡genial! Hazlo. Si necesitas flexibilidad para tener diferentes middleware en diferentes buses, genial. Configura varios buses.

Dado que los buses múltiples son el caso de uso más complejo... y que estamos profundizando en Messenger, mantengamos nuestra configuración de buses múltiples y organicemos mejor nuestro código en torno a este concepto.

## Mensajes enviados al bus equivocado

Busca tu terminal y ejecuta:

```terminal
php bin/console debug:messenger
```

Ah... Ahora que tenemos varios autobuses, desglosa la información autobús por autobús. Dice que los siguientes mensajes pueden ser enviados a nuestro bus de comandos y... eh... estos mismos mensajes pueden ser enviados al bus de eventos.

Eso está... bien... pero no es lo que realmente queremos. Sabemos que ciertos mensajes son órdenes y se enviarán al bus de órdenes y otros son eventos. Pero cuando configuramos nuestros manejadores, nunca le dijimos a Messenger que este manejador sólo debe ser utilizado por este bus. Así, Messenger se asegura de que todos los buses conozcan todos los manejadores. Esto no es un gran problema, pero significa que si accidentalmente tomáramos este comando y lo enviáramos al bus de eventos, ¡funcionaría! Y si tomáramos este evento y lo enviáramos al bus de comandos, funcionaría. Si confiamos en que cada bus tenga un middleware bastante diferente, probablemente no querremos cometer ese error.

Así que... vamos a hacer algo totalmente opcional... pero agradable, cuando se utilizan eventos y comandos. Mira dentro de los directorios `Message` y `MessageHandler`: tenemos una mezcla de eventos y comandos. Claro, he puesto el evento en un subdirectorio `Event/`, pero no hemos hecho lo mismo con los comandos.

Hagamos eso a continuación: organicemos mejor nuestros mensajes y manejadores de mensajes. Una vez hecho esto, podemos utilizar un truco de configuración de servicios para asegurarnos de que el bus de comandos sólo conoce los manejadores de comandos y el bus de eventos sólo conoce los manejadores de eventos.
