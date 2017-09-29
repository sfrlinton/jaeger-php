<?php

namespace JaegerPhp\ThriftGen\Agent;

use JaegerPhp\Jaeger;
use JaegerPhp\JSpan;


class JaegerThriftSpan
{
    public function buildJaegerProcessThrift(Jaeger $jaeger)
    {
        $tags = [];
        $ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
        if ($ip) {
            $tags['ip'] = $ip;
        }

        $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '';
        if ($port) {
            $tags['port'] = $port;
        }
        $tags = array_merge($tags, $jaeger->tags);
        $tagsObj = Tags::getInstance();
        $tagsObj->setTags($tags);
        $thriftTags = $tagsObj->buildTags();

        $processThrift = [
            'serverName' => $jaeger->serverName,
            'tags' => $thriftTags,
        ];

        return $processThrift;
    }

    public function buildJaegerSpanThrift(JSpan $Jspan)
    {
        $spContext = $Jspan->getContext();
        $span = [
            'traceIdLow' => hexdec($spContext->getTraceId()),
            'traceIdHigh' => 0,
            'spanId' => hexdec($spContext->getSpanId()),
            'parentSpanId' => hexdec($spContext->getParentId()),
            'operationName' => $Jspan->getOperationName(),
            'flags' => intval($spContext->getFlags()),
            'startTime' => $Jspan->getStartTime(),
            'duration' => $Jspan->getDuration(),
            'tags' => $this->buildTags($Jspan->getTags()),
            'logs' => $this->buildLogs($Jspan->getLogs()),
        ];

        if ($spContext->getParentId() != 0) {
            $span['references'] = [
                [
                    'refType' => 1,
                    'traceIdLow' => hexdec($spContext->getTraceId()),
                    'traceIdHigh' => 0,
                    'spanId' => hexdec($spContext->getParentId()),
                ],
            ];
        }

        return $span;
    }

    private function buildTags($tags)
    {

        $resultTags = [];
        if ($tags) {
            $tagsObj = Tags::getInstance();
            $tagsObj->setTags($tags);
            $resultTags = $tagsObj->buildTags();
        }

        return $resultTags;
    }

    private function buildLogs($logs)
    {
        $resultLogs = [];
        if ($logs) {
            $tagsObj = Tags::getInstance();
            foreach ($logs as $log) {
                $tagsObj->setTags($log['fields']);
                $fields = $tagsObj->buildTags();
                $resultLogs[] = [
                    "timestamp" => $log['timestamp'],
                    "fields" => $fields,
                ];
            }
        }

        return $resultLogs;
    }
}