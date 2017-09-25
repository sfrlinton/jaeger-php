<?php

namespace JaegerPhp;

use OpenTracing\SpanContext;

class JSpanContext implements SpanContext
{
    // traceID represents globally unique ID of the trace.
    // Usually generated as a random number.
    protected $traceId;

    // spanID represents span ID that must be unique within its trace,
    // but does not have to be globally unique.
    protected $spanId;

    // parentID refers to the ID of the parent span.
    // Should be 0 if the current span is a root span.
    protected $parentId;

    // flags is a bitmap containing such bits as 'sampled' and 'debug'.
    protected $flags;

    // Distributed Context baggage. The is a snapshot in time.
    protected $baggage;

    // debugID can be set to some correlation ID when the context is being
    // extracted from a TextMap carrier.
    protected $debugId;


    public function __construct($traceId, $spanId, $parentId, $flags, $baggage = null, $debugId = 0, $obj = null)
    {
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->parentId = $parentId;
        $this->flags = $flags;
        $this->baggage = $baggage;
        $this->debugId = $debugId;
    }

//    public static function getInstance($traceId, $spanId, $parentId, $flags, $baggage = null, $debugId = 0){
//        return new self($traceId, $spanId, $parentId, $flags, $baggage, $debugId);
//    }

    public function getBaggageItem($key)
    {

    }

    public function withBaggageItem($key, $value)
    {

    }

    public function getIterator()
    {
        // TODO: Implement getIterator() method.
    }

    public function buildString()
    {
        return $this->traceId . ':' . $this->spanId . ':' . $this->parentId . ':' . $this->flags;
    }

    /**
     * 是否取样
     * @return mixed
     */
    public function isSampled()
    {
        return $this->flags;
    }

    /**
     * @return mixed
     */
    public function getTraceId()
    {
        return $this->traceId;
    }

    /**
     * @return mixed
     */
    public function getSpanId()
    {
        return $this->spanId;
    }

    /**
     * @return mixed
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @return mixed
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return null
     */
    public function getBaggage()
    {
        return $this->baggage;
    }

    /**
     * @return int
     */
    public function getDebugId()
    {
        return $this->debugId;
    }

}