<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Response;

use steevanb\PhpUrlTest\UrlTest;

class Response
{
    /** @var UrlTest */
    protected $urlTest;

    /** @var ?int */
    protected $code;

    /** @var ?int */
    protected $numConnects;

    /** @var ?int */
    protected $size;

    /** @var ?string */
    protected $contentType;

    /** @var ?int */
    protected $connectTime;

    /** @var ?int */
    protected $preTranferTime;

    /** @var ?int */
    protected $startTranferTime;

    /** @var ?int */
    protected $time;

    /** @var ?int */
    protected $redirectCount;

    /** @var ?int */
    protected $redirectTime;

    /** @var ?string */
    protected $redirectUrl;

    /** @var ?int */
    protected $url;

    /** @var ?string */
    protected $header;

    /** @var string[] */
    protected $headers = [];

    /** @var ?int */
    protected $headerSize;

    /** @var ?string */
    protected $body;

    /** @var ?int */
    protected $bodySize;

    /** @var ?int */
    protected $errorCode;

    /** @var ?string */
    protected $errorMessage;

    /** @var Event[] */
    protected $triggeredEvents = [];

    public function __construct(
        UrlTest $urlTest,
        array $triggeredEvents,
        $curl = null,
        ?string $response = null,
        ?int $time = null,
        ?int $errorCode = null,
        ?string $errorMessage = null
    ) {
        $this->urlTest = $urlTest;
        $this->triggeredEvents = $triggeredEvents;
        
        if (is_resource($curl) || $curl instanceof \CurlHandle) {
            $this->time = $time;
            $this->numConnects = curl_getinfo($curl, CURLINFO_NUM_CONNECTS);
            $this->contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            $this->contentType = $this->contentType === false ? '' : $this->contentType;
            $this->connectTime = curl_getinfo($curl, CURLINFO_CONNECT_TIME);
            $this->preTranferTime = curl_getinfo($curl, CURLINFO_PRETRANSFER_TIME);
            $this->startTranferTime = curl_getinfo($curl, CURLINFO_STARTTRANSFER_TIME);
            $this->url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            $this->redirectCount = curl_getinfo($curl, CURLINFO_REDIRECT_COUNT);
            $this->redirectTime = curl_getinfo($curl, CURLINFO_REDIRECT_TIME);
            $this->headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

            if (!empty($response)) {
                $this->code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                $this->size = strlen($response);

                if ($this->headerSize > 0) {
                    $this->defineHeaders(substr($response, 0, $this->headerSize));
                }
                if (is_string($response) === true) {
                    $this->body = substr($response, $this->headerSize);
                    $this->bodySize = strlen($this->body);
                }
            }
        }
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function getNumConnects(): ?int
    {
        return $this->numConnects;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getConnectTime(): ?int
    {
        return $this->connectTime;
    }

    public function getPreTranferTime(): ?int
    {
        return $this->preTranferTime;
    }

    public function getStartTranferTime(): ?int
    {
        return $this->startTranferTime;
    }

    public function getTime(): ?int
    {
        return intval($this->time);
    }

    public function getRedirectCount(): ?int
    {
        return $this->redirectCount;
    }

    public function getRedirectTime(): ?int
    {
        return $this->redirectTime;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeaderValue(string $name): ?string
    {
        return $this->getHeaders()[$name] ?? null;
    }

    public function getHeaderSize(): ?int
    {
        return $this->headerSize;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getTransformedBody(): ?string
    {
        return $this->urlTest->getTransformedBody(
            $this->getBody(),
            $this->urlTest->getConfiguration()->getResponse()->getRealResponseBodyTransformerName()
        );
    }

    public function getBodySize(): ?int
    {
        return $this->bodySize;
    }

    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /** @return array */
    public function getTriggeredEvents(): array
    {
        return $this->triggeredEvents;
    }

    /** @return Event[] */
    public function getTriggeredEventsByName(string $name): array
    {
        return $this->getTriggeredEvents()[$name] ?? [];
    }

    protected function defineHeaders(string $header): self
    {
        $this->header = $header;
        $this->headers = [];
        foreach (explode("\r\n", substr($header, stripos($header, "\r\n"))) as $line) {
            if ($line === null || trim($line) === '') {
                continue;
            }
            if (strpos($line, ':') === false) {
                $this->headers[$line] = null;
                continue;
            }
            [$name, $value] = explode(': ', $line);
            $this->headers[$name] = $value;
        }

        return $this;
    }
}
