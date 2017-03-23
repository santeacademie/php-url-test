<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\Test;

class ExpectedResponse
{
    /** @var UrlTest */
    protected $urlTest;

    /** @var ?int */
    protected $url;

    /** @var ?int */
    protected $code;

    /** @var ?int */
    protected $numConnects;

    /** @var ?int */
    protected $size;

    /** @var ?string */
    protected $contentType;

    /** @var string[] */
    protected $headers = [];

    /** @var ?int */
    protected $headerSize;

    /** @var ?string */
    protected $body;

    /** @var ?int */
    protected $bodySize;

    /** @var ?string */
    protected $bodyTransformerName;

    /** @var ?string */
    protected $bodyFileName;

    public function __construct(UrlTest $urlTest)
    {
        $this->urlTest = $urlTest;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setCode(?int $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function setNumConnects(?int $numConnects): self
    {
        $this->numConnects = $numConnects;

        return $this;
    }

    public function getNumConnects(): ?int
    {
        return $this->numConnects;
    }

    public function setSize(?int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setContentType(?string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setHeaders(?array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function setHeaderSize(?int $headerSize): self
    {
        $this->headerSize = $headerSize;

        return $this;
    }

    public function getHeaderSize(): ?int
    {
        return $this->headerSize;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getTransformedBody(): ?string
    {
        return $this->urlTest->getTransformedBody($this->getBody(), $this->getBodyTransformerName());
    }

    public function setBodySize(?int $bodySize): self
    {
        $this->bodySize = $bodySize;

        return $this;
    }

    public function getBodySize(): ?int
    {
        return $this->bodySize;
    }

    public function setBodyTransformerName(?string $transformer): self
    {
        $this->bodyTransformerName = $transformer;

        return $this;
    }

    public function getBodyTransformerName(): ?string
    {
        return $this->bodyTransformerName;
    }

    public function setBodyFileName(?string $bodyFileName): self
    {
        $this->bodyFileName = $bodyFileName;

        return $this;
    }

    public function getBodyFileName(): ?string
    {
        return $this->bodyFileName;
    }
}