# Messenger vs EventDispatcher

Si alguna vez has creado un oyente de eventos o un suscriptor de eventos en Symfony, estás creando un "oyente" para un evento que se envía a través de un servicio llamado "despachador de eventos". El propósito del despachador de eventos es permitir que un trozo de código "notifique" a la aplicación que ha ocurrido algo y que cualquier otro "escuche" ese evento y ejecute algún código.

Lo cual... eh... es exactamente el mismo propósito de despachar un evento en Messenger ¿Qué demonios? Si quiero enviar un evento en mi código, ¿debo utilizar el EventDispatcher o el Messenger? ¿Los archivos de imágenes animadas se pronuncian "jif" o "gif"? ¿El papel higiénico debe colgar "encima" del rollo o "debajo"? ¡Ah!

## Messenger puede ser asíncrono

En primer lugar, hay una diferencia práctica entre enviar un evento al EventDispatcher o al Messenger: Messenger permite llamar a sus manejadores de forma asíncrona, mientras que los oyentes de los eventos del EventDispatcher son siempre síncronos.

## El EventDispatcher se comunica de nuevo

Y esto nos lleva a una buena regla general. Siempre que envíes un evento, si quieres que los oyentes de ese evento puedan comunicarse contigo, para que puedas hacer algo basándote en su respuesta, utiliza el EventDispatcher. Pero si simplemente quieres decir "ha pasado esto" y no necesitas ninguna respuesta de los posibles oyentes o manejadores, utiliza Messenger.

Por ejemplo, en `AddPonkaToImageHandler`, supongamos que queremos enviar un evento aquí para que otras partes del sistema nos digan exactamente qué imagen Ponka debe añadirse a esta foto. En ese caso, necesitamos que esos oyentes puedan comunicarse con nosotros. Para ello, crearíamos una clase de Evento que contenga el objeto `ImagePost` y que tenga un definidor al que los oyentes puedan llamar, tal vez`setPonkaImageToUse()`. Entonces utilizaríamos el `EventDispatcher` y enviaríamos el mensaje antes de añadir realmente Ponka a la imagen. Una vez llamados todos los oyentes, podríamos ver si alguno de ellos llama a ese método `setPonkaImageToUse()`.

Pero, ¿y si simplemente quisiéramos decir:

> ¡Oye! ¡Acabamos de añadir a Ponka a una imagen!

... y no necesitáramos ninguna información de los posibles manejadores? En ese caso, crearíamos una clase de evento similar, omitiríamos el método `setPonkaImageToUse()` y lo despacharíamos con Messenger. Messenger es perfecto si no necesitas ninguna información de vuelta de tus manejadores porque... ¡esos manejadores podrían acabar siendo llamados de forma asíncrona!

Si todavía no lo tienes claro, utiliza el que quieras. ¿Por qué? Porque si al final quieres que tu código se ejecute de forma asíncrona, acabarás eligiendo Messenger. Y si quieres que tus oyentes puedan comunicarse con el código que envía los mensajes, utilizarás EventDispatcher. De lo contrario, cualquiera de los dos funcionará.

A continuación, vamos a utilizar algunos trucos de configuración del servicio para ajustar cómo hemos organizado nuestros comandos, manejadores de comandos, eventos y manejadores de eventos.
