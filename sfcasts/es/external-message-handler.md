# Configuración de los mensajes de un sistema externo

¿Qué pasaría si una cola en RabbitMQ estuviera llena de mensajes que se originan en un sistema externo... pero quisiéramos consumirlos y manejarlos desde nuestra aplicación Symfony? Por ejemplo, tal vez un usuario pueda solicitar que se elimine una foto desde un sistema totalmente diferente... y ese sistema necesita comunicarse con nuestra aplicación para que pueda realizar el borrado ¿Cómo funcionaría eso?

Cada transporte en Messenger tiene realmente dos funciones: una, enviar mensajes a un corredor de mensajes o sistema de colas y dos, recibir mensajes de ese mismo sistema y gestionarlos.

Y, como hablamos en el último vídeo, no es necesario que utilices ambas funciones de un transporte: puedes elegir enviar a un transporte, pero no leer ni consumir nunca esos mensajes... porque lo hará otro sistema. O puedes hacer lo contrario: crear un transporte al que nunca enviarás, pero que utilizarás para consumir mensajes... que probablemente fueron puestos ahí por algún sistema externo. El truco para hacer esto es crear un serializador que pueda entender el formato de esos mensajes externos.

## Crear un nuevo Mensaje y Manejador

En lugar de sobreexplicar esto, veámoslo en acción. En primer lugar, imagina que este sistema externo imaginario necesita poder decirle a nuestra aplicación que haga algo... muy... importante: registrar un Emoji. Vale, puede que no sea el tipo de mensaje más impresionante... pero los detalles de lo que este mensaje externo le dice a nuestra aplicación que haga no son importantes: podría decirnos que subamos una imagen con detalles sobre la ubicación del archivo, que eliminemos una imagen, que enviemos un correo electrónico a un usuario registrado o que registremos un emoji

Pongamos esto en marcha. Normalmente, si quisiéramos enviar un comando para registrar un emoji, empezaríamos por crear una clase de mensaje y un manejador de mensajes. En este caso... haremos exactamente lo mismo. En el directorio `Command/`, crea una nueva clase PHP llamada `LogEmoji`

[[[ code('a58117d08f') ]]]

Añade un argumento `public function __construct()`. Para indicarnos qué emoji debemos registrar, el sistema exterior nos enviará un índice entero del emoji que quieren - nuestra aplicación tendrá una lista de emojis. Así que añade un argumento `$emojiIndex` y luego pulsa Alt+Enter y selecciona "Inicializar campos" para crear esa propiedad y establecerla.

[[[ code('7f74b29e94') ]]]

Para hacer que esta propiedad sea legible por el manejador, ve al menú Código -> Generar -o Comando + N en un Mac-, selecciona getters y genera `getEmojiIndex()`.

[[[ code('018a56280b') ]]]

¡Genial! Una clase de mensaje perfectamente aburrida y normal. Segundo paso: en el directorio`MessageHandler/Command/`, crea una nueva clase `LogEmojiHandler`. Haz que implemente nuestra clase normal `MessageHandlerInterface` y añade`public function __invoke()` con el tipo de mensaje: `LogEmoji $logEmoji`.

[[[ code('b8027c7613') ]]]

Ahora... ¡nos ponemos a trabajar! Voy a pegar una lista de emojis en la parte superior: aquí están los cinco que el sistema exterior puede elegir: galleta, dinosaurio, queso, robot y, por supuesto, caca 

[[[ code('d208be46b1') ]]]

Y luego, como vamos a registrar algo, añade un método `__construct()`con la pista de tipo `LoggerInterface`. Pulsa Alt + Intro y selecciona "Inicializar campos" una vez más para crear esa propiedad y establecerla.

[[[ code('5fc4f8d7fb') ]]]

Dentro de `__invoke()`, nuestro trabajo es bastante sencillo. Para obtener el emoji, establece una variable`$index` en `$logEmoji->getEmojiIndex()`

[[[ code('cb646db087') ]]]

Luego `$emoji = self::$emojis` - para referenciar esa propiedad estática -`self::$emojis[$index] ?? self::emojis[0]`.

[[[ code('294d01d24f') ]]]

En otras palabras, si el índice existe, úsalo. Si no, vuelve a registrar una cookie... porque... a todo el mundo le gustan las cookies. Registra con`$this->logger->info('Important message! ')`y luego con `$emoji`.

[[[ code('9a1987843c') ]]]

La gran conclusión de este nuevo gestor de mensajes y mensajes es que, bueno, ¡no se diferencia en absoluto de cualquier otro gestor de mensajes y mensajes! A Messenger no le importa si el objeto `LogEmoji` se enviará manualmente desde nuestra propia aplicación o si un trabajador recibirá un mensaje de un sistema externo que se asignará a esta clase.

Para probarlo, sube a `ImagePostController`, busca el método `create()` y, sólo para asegurarte de que esto funciona, añade:`$messageBus->dispatch(new LogEmoji(2))`.

[[[ code('e9560eb72c') ]]]

Si esto funciona, deberíamos ver un mensaje en nuestros registros cada vez que subamos una foto. Busca tu terminal: veamos los logs con:

```terminal
tail -f var/log/dev.log
```

Este es el archivo de registro del entorno `dev`. Despejaré mi pantalla, luego me desplazaré, seleccionaré una foto y... retrocederé. Ahí está:

> ¡Mensaje importante! 🧀

¡Estoy de acuerdo! ¡Eso es importante! Esto es genial... pero no es lo que realmente queremos. Lo que realmente queremos es utilizar un trabajador para consumir un mensaje de una cola -probablemente un mensaje JSON- y transformarlo de forma inteligente en un objeto `LogEmoji` para que Messenger pueda manejarlo. ¿Cómo lo hacemos? Con un transporte dedicado y un serializador de clientes. ¡Hagamos eso a continuación!
