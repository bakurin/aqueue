# A queue

Small PHP library with allows to setup queue message worker. RabbitMQ
is supported out of the box. To get basic overview see `example/` folder

Except some implementation the library provides set of tools to build
and adjust it specific cases and requirements. Using it you can build
a worker to listen different queues and process messages of different
formats.

## Middlewares

The processing flow can we adjusted by appending a worker with existing
middlewares or new ones. The only rule is that any middleware have to
implement `Middleware` interface

There are following middleware are provided:
 - ErrorHandlerMiddleware
 - LoggerMiddleware
 - MessageAskMiddleware
 - ForkMiddleware

### ForkMiddleware

ForkMiddleware is used to fork current PHP process and proceed processing
in an isolated child process to avoid memory leaks.
