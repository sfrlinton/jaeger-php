<?php namespace JaegerPhp;

use OpenTracing\Carriers\TextMap;
use OpenTracing\Propagator;
use OpenTracing\Reference;
use JaegerPhp\Sampler\ProbabilisticSampler;
use JaegerPhp\Sampler\ConstSampler;

/**
 * Create an interface to reduce instrumentation lines of code as much as possible.
 * Usage:
 *
 *  $tracer = new SimpleTracer(
 *      'server.com',
 *      'root span name',
 *      '0.0.0.0:5775',
 *      [],
 *      TRUE
 *  );
 *
 *  $span1 = $tracer->createSpan('span1');
 *  $childOfSpan1 = $tracer->createSpan('childOfSpan1', [...tags...], $span1);
 *  ...
 *  $tracer->finishSpan($childOfSpan1, [...tags..]);
 *  ...
 *  $tracer->finishSpan($span1, [...tags..]);
 */

class SimpleTracer {

    public function __construct($traceLabel, $traceSpanLabel, $agentUri, $injectTarget = [], $debugTrace = FALSE, $environment = NULL) {
        if (!$agentUri) {
            throw new \InvalidArgumentException('$agentUri cannot be null');
        }

        try {
            if ($environment) {
                $_SERVER['JAEGER_TAGS'] = 'app.environment='.$environment;
            }

            $traceConfig = Config::getInstance();

            if ($debugTrace) {
                $sampler = new ConstSampler();
            } else {
                $sampler = new ProbabilisticSampler();
            }

            $traceConfig->setSampler($sampler);
            $this->tracer = $traceConfig->initTrace($traceLabel, $agentUri);
            $textMap = TextMap::create($injectTarget);
            $spanContext = $this->tracer->extract(Propagator::TEXT_MAP, $textMap);
            $span = $this->tracer->startSpan($traceSpanLabel, Reference::create(Reference::CHILD_OF, $spanContext));

            register_shutdown_function(function() use ($traceConfig, $span) {
                $span->finish();
                $traceConfig->flushTrace();
            });
        } catch (\Exception $e) {
            if ($debugTrace) {
                throw $e;
            }
        }
    }

    public function createSpan($spanLabel, $parentSpan = NULL, $tags = []) {
        if (count($this->tracer->getSpans()) < 1) {
            //no root span, not sampling
            return $this->tracer->startSpan($spanLabel, Reference::create(Reference::CHILD_OF, new JSpanContext(0, 0, 0, 0, null, 0)));
        }

        if ($parentSpan) {
            $spanContext = $parentSpan->getContext();
        } else {
            $spanContext = $this->tracer->getSpans()[0]->getContext();
        }

        $span = $this->tracer->startSpan($spanLabel, Reference::create(Reference::CHILD_OF, $spanContext));
        $span->setTags($tags);
        return $span;
    }

    public function getSpanHeaders($span) {
        return [Helper::TracerStateHeaderName =>  $span->getContext()->buildString()];
    }

    public function finishSpan($span, $tags = []){
        $span->setTags($tags);
        $span->finish();
    }
}