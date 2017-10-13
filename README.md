# jaeger-php

## principle of Distributed Tracing

<img src="https://upload.cc/i/OhsjA0.jpg" width="700px" height="650px" />

## install
> install via composer

> vim composer.json 

```
{
  "minimum-stability": "dev",
  "require":           {
    "jukylin/jaeger-php" : "^1.0",
    "opentracing/opentracing":"dev-master"
  }
}
```

> composer update


##  Server

```
use OpenTracing\GlobalTracer;
use OpenTracing\Propagator;
use OpenTracing\Carriers\TextMap;
use OpenTracing\SpanReference;
use JaegerPhp\Jaeger;

//init jaeger-php
$traceObj = new Jaeger("server", $jaegerAgentHost, $jaegerAgentHost);
GlobalTracer::set($traceObj);

//extract from Superglobal 
$mapText = array_merge($_REQUEST, $_SERVER);
$spanContext = $this->traceObj->extract(Propagator::TEXT_MAP, TextMap::create($mapText));
$parseUrl = parse_url($mapText['REQUEST_URI']);
//start server span
$spanObj = $traceObj->startSpan($mapText['REQUEST_METHOD'].' '.$parseUrl['path'], SpanReference::createAsChildOf($spanContext));
$spanObj->setIsServer();
//inject to Superglobal
$traceObj->injectJaeger($spanObj->spanContext, Propagator::TEXT_MAP, $_SERVER);

......
//business process
......

//end server span
$spanObj->finish();
//send thrift to jaeger-agent
$traceObj->flush();
```

## Client

```
$urlArg = [];
$tracer = GlobalTracer::get();
$mapText = array_merge($_REQUEST, $_SERVER);
$spanContext = $tracer->extract(Propagator::TEXT_MAP, TextMap::create($mapText));
$span = $tracer->startSpan("client", SpanReference::createAsFollowsFrom($spanContext));
//inject TracerStateHeaderName into $urlArg
$tracer->injectJaeger($span->spanContext, Propagator::TEXT_MAP, $urlArg);

......
//business process
......

$span->finish();
```

## Tags and Log


```
//can search in Jaeger UI
$span->addTags(['http.status' => "200"]);

//log record
$span->log(['error' => "HTTP request timeout"]);

```

## Reference

[OpenTracing](http://opentracing.io/)

[Jaeger](https://uber.github.io/jaeger/)

## Testing


[Get composer and install dependencies](https://getcomposer.org/)

    ./composer.phar install

[Start a jaeger server on localhost](http://jaeger.readthedocs.io/en/latest/getting_started/#all-in-one-docker-image)

    docker run -d -e COLLECTOR_ZIPKIN_HTTP_PORT=9411 -p5775:5775/udp -p6831:6831/udp -p6832:6832/udp -p5778:5778 -p16686:16686 -p14268:14268 -p9411:9411 jaegertracing/all-in-one:latest

Run the tests

    ./vendor/bin/phpunit

An autoflushing tracer and span usage:

    $tracer = new SimpleTracer(
        'server.com',
        'root span name',
        '0.0.0.0:5775',
        $_SERVER,
        TRUE
    );

    $span1 = $tracer->createSpan('span1');

    $childOfSpan1 = $tracer->createSpan('childOfSpan1', [...tags...], $span1);

    ...

    $tracer->finishSpan($childOfSpan1, [...tags..]);

    ...

    $tracer->finishSpan($span1, [...tags..]);