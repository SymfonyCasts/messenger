# JSON, cabeceras de mensajes y opciones del serializador

Además de la carga útil, un mensaje en RabbitMQ también puede tener "cabeceras". Comprueba esa clave en nuestro mensaje. ¡Woh! ¡Contiene una gran estructura JSON con el nombre de la clase original y los datos y nombres de clase de los sellos adjuntos al mensaje!

¿Por qué ha hecho esto Messenger? Bueno, busca tu terminal y consume el transporte `async`:

```terminal
php bin/console messenger:consume -vv async
```

Esto sigue funcionando. Internamente, el serializador de Symfony utiliza la información de`headers` para averiguar cómo tomar esta simple cadena JSON y convertirla en el objeto correcto. Utilizó la cabecera `type` para saber que el JSON debía convertirse en un objeto`ImagePostDeletedEvent` y luego hizo un bucle sobre los sellos y convirtió cada uno de ellos en un objeto sello para el sobre.

Lo bueno de utilizar el serializador de Symfony en Messenger es que el `payload` es una estructura JSON simple y pura que puede ser consumida por cualquier aplicación en cualquier lenguaje. Contiene algo de información de la clase PHP en las cabeceras, pero otra aplicación puede simplemente ignorar eso. Pero gracias a esas cabeceras, si la misma app envía y consume un mensaje, el serializador Symfony puede seguir utilizándose.

## ¿No deberíamos utilizar siempre el serializador de Symfony?

Pero espera... si eso es cierto - si el serializador de Symfony crea mensajes que pueden ser consumidos por sistemas externos o por nuestra misma app - entonces ¿por qué no es el serializador por defecto en Messenger? ¡Una excelente pregunta! La razón es que el serializador de Symfony requiere que tus clases sigan algunas reglas para ser serializadas y des-serializadas correctamente - como que cada propiedad necesita un método setter o un argumento constructor donde el nombre coincida con el nombre de la propiedad. Si tu clase no sigue esas reglas, puedes acabar con una propiedad que está establecida en el objeto original, pero que de repente se convierte en nula cuando se lee del transporte. No es divertido.

En otras palabras, el serializador de PHP es más fácil y fiable cuando todo lo hace la misma aplicación.

## Configurar el serializador de Symfony

De todos modos, si estás utilizando el serializador de Symfony, también hay algunas cosas que se pueden configurar. Busca tu terminal y ejecuta:

```terminal
php bin/console config:dump framework messenger
```

Comprueba la clave `symfony_serializer`. Aquí es donde configuras el comportamiento del serializador: el formato - `json`, `xml` o algo más, y el`context`, que es una matriz de opciones para el serializador.

Por supuesto, también puedes crear un servicio de serializador totalmente personalizado. Y si tienes el flujo de trabajo opuesto al que acabamos de describir -uno en el que tu aplicación consume mensajes que fueron enviados a Rabbit desde algún otro sistema- un serializador personalizado es exactamente lo que necesitas. Hablemos de eso a continuación.
