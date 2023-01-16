<?php

declare(strict_types=1);

namespace steevanb\PhpUrlTest\ResultReader;

use steevanb\PhpUrlTest\{
    Configuration\Event,
    UrlTest
};

class ConsoleResultReader implements ResultReaderInterface
{
    use ShowUrlTestTrait;

    public function read(array $urlTests, bool $showSuccess, bool $showError, int $verbosity): void
    {
        if ($verbosity >= ResultReaderService::VERBOSITY_NORMAL) {
            /** @var UrlTest $urlTest */
            foreach ($urlTests as $urlTest) {
                if ($this->showUrlTest($urlTest, $showSuccess, $showError)) {
                    if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE) {
                        echo "\n";
                    }

                    $this
                        ->writeResult($urlTest, $verbosity)
                        ->writeEvents($urlTest, Event::EVENT_BEFORE_TEST, $verbosity)
                        ->writeEvents($urlTest, Event::EVENT_AFTER_TEST, $verbosity);

                    if (is_string($urlTest->getResponse()->getErrorMessage())) {
                        continue;
                    }

                    $responseConfiguration = $urlTest->getConfiguration()->getResponse();
                    $this
                        ->writeDiff(
                            'Url',
                            $responseConfiguration->getUrl(),
                            $urlTest->getResponse()->getUrl(),
                            $verbosity
                        )
                        ->writeDiff(
                            'Http code',
                            $responseConfiguration->getCode(),
                            $urlTest->getResponse()->getCode(),
                            $verbosity
                        )
                        ->writeDiff(
                            'Connections',
                            $responseConfiguration->getNumConnects(),
                            $urlTest->getResponse()->getNumConnects(),
                            $verbosity
                        )
                        ->writeDiff(
                            'Size',
                            $responseConfiguration->getSize(),
                            $urlTest->getResponse()->getSize(),
                            $verbosity
                        )
                        ->writeDiff(
                            'Content type',
                            $responseConfiguration->getContentType(),
                            $urlTest->getResponse()->getContentType(),
                            $verbosity
                        )
                        ->writeDiff(
                            'Header size',
                            $responseConfiguration->getHeaderSize(),
                            $urlTest->getResponse()->getHeaderSize(),
                            $verbosity
                        )
                        ->writeHeadersDiff(
                            $urlTest->getConfiguration()->getResponse()->getHeaders(),
                            $urlTest->getConfiguration()->getResponse()->getUnallowedHeaders(),
                            $urlTest->getResponse()->getHeaders(),
                            $verbosity
                        )
                        ->writeDiff(
                            'Body size',
                            $responseConfiguration->getBodySize(),
                            $urlTest->getResponse()->getBodySize(),
                            $verbosity
                        )
                        ->writeBodyDiff($urlTest, $verbosity)
                        ->writeRedirectionDiff($urlTest, $verbosity)
                        ->writeRedirectionCountDiff($urlTest, $verbosity)
                        ->writeCurlErrors($urlTest, $verbosity);
                }
            }
        }
    }

    protected function writeRedirectionDiff(UrlTest $urlTest, int $verbosity): self
    {
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE) {
            if ($urlTest->getConfiguration()->getRequest()->isAllowRedirect() === false) {
                echo 'Redirection: ';
                if ($urlTest->getResponse()->getCode() === 302) {
                    $this->writeExpectedValue('not allowed');
                    echo ', but http code ';
                    $this->writeBadValue(302);
                    echo ' returned';
                } else {
                    $this->writeOkValue('none');
                }
                echo "\n";
            } else {
                echo 'Redirection: ';
                echo ($urlTest->getResponse()->getRedirectCount() === 0)
                    ? 'none' :
                    $urlTest->getResponse()->getRedirectCount();
                echo "\n";
            }
        }

        return $this;
    }

    protected function writeCurlErrors(UrlTest $urlTest, int $verbosity): self
    {
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE && !empty($urlTest->getResponse()->getErrorMessage())) {
            echo sprintf(
                'Client error: [%s] %s',
                $urlTest->getResponse()->getErrorCode() ?? '-',
                $urlTest->getResponse()->getErrorMessage() ?? '-'
            );
            echo "\n";
        }

        return $this;
    }

    protected function writeRedirectionCountDiff(UrlTest $urlTest, int $verbosity): self
    {
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE) {
            $responseConfiguration = $urlTest->getConfiguration()->getResponse();
            if ($responseConfiguration->getRedirectCount() !== null) {
                $this->writeDiff(
                    'Redirections count',
                    $responseConfiguration->getRedirectCount(),
                    $urlTest->getResponse()->getRedirectCount(),
                    $verbosity
                );
            } elseif (
                $responseConfiguration->getRedirectMin() !== null
                || $responseConfiguration->getRedirectMax() !== null
            ) {
                echo 'Redirection count: ';
                if ($responseConfiguration->getRedirectMin() === null) {
                    $redirectionLabel = 'max ' . $responseConfiguration->getRedirectMax();
                    $isError = $responseConfiguration->getRedirectMax() < $urlTest->getResponse()->getRedirectCount();
                } elseif ($responseConfiguration->getRedirectMax() === null) {
                    $redirectionLabel = 'min ' . $responseConfiguration->getRedirectMin();
                    $isError = $responseConfiguration->getRedirectMin() > $urlTest->getResponse()->getRedirectCount();
                } else {
                    $redirectionLabel =
                        'between ' . $responseConfiguration->getRedirectMin()
                        . ' and ' . $responseConfiguration->getRedirectMax();
                    $isError =
                        $responseConfiguration->getRedirectMax() < $urlTest->getResponse()->getRedirectCount()
                        || $responseConfiguration->getRedirectMin() > $urlTest->getResponse()->getRedirectCount();
                }
                if ($isError) {
                    $this->writeExpectedValue($redirectionLabel);
                    echo ', got ';
                    $this->writeBadValue($urlTest->getResponse()->getRedirectCount());
                } else {
                    echo $redirectionLabel;
                }
                echo "\n";
            } elseif ($verbosity >= ResultReaderService::VERBOSITY_VERY_VERBOSE) {
                echo 'Redirections count: ' . $urlTest->getResponse()->getRedirectCount() . "\n";
            }

            if ($urlTest->getResponse()->getRedirectUrl() !== null) {
                echo 'Redirect URL: ' . $urlTest->getResponse()->getRedirectUrl() . "\n";
            }
        }

        return $this;
    }

    protected function writeBodyDiff(UrlTest $urlTest, int $verbosity): self
    {
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE) {
            $responseConfiguration = $urlTest->getConfiguration()->getResponse();
            if ($responseConfiguration->getBody() !== null) {
                $expectedBody = $urlTest->getTransformedBody(
                    $responseConfiguration->getBody(),
                    $responseConfiguration->getBodyTransformerName()
                );
                echo 'Body: ';
                if (
                    $expectedBody === $urlTest->getResponse()->getTransformedBody()
                ) {
                    if ($verbosity < ResultReaderService::VERBOSITY_DEBUG) {
                        $this->writeOkValue('ok');
                    } else {
                        if ($urlTest->getResponse()->getTransformedBody() === null) {
                            $this->writeOkValue('<empty>');
                        } else {
                            echo "\n";
                            $this->writeOkValue($urlTest->getResponse()->getTransformedBody());
                        }
                    }
                } else {
                    if ($verbosity < ResultReaderService::VERBOSITY_DEBUG) {
                        $this->writeBadValue('fail');
                    } else {
                        $this->writeBadValue('fail');
                        echo "\n" . 'Expected body: ';
                        if ($expectedBody === null || trim($expectedBody) === '') {
                            $this->writeExpectedValue('<empty>');
                        } else {
                            echo "\n";
                            $this->writeExpectedValue($expectedBody);
                        }
                        echo "\n";
                        echo 'Response body: ';
                        if ($urlTest->getResponse()->getTransformedBody() === null) {
                            $this->writeBadValue('<empty>');
                        } else {
                            echo "\n";
                            $this->writeBadValue($urlTest->getResponse()->getTransformedBody());
                        }
                    }
                }
                echo "\n";
            } elseif ($verbosity === ResultReaderService::VERBOSITY_DEBUG) {
                if ($urlTest->getResponse()->getTransformedBody() === null) {
                    echo 'Body: <empty>' . "\n";
                } else {
                    echo 'Body:' . "\n" . $urlTest->getResponse()->getTransformedBody() . "\n";
                }
            }
        }

        return $this;
    }

    protected function writeDiff(string $label, $expectedValue, $value, int $verbosity): self
    {
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE) {
            if ($expectedValue !== null) {
                echo $label . ': ';
                if ($expectedValue !== $value) {
                    echo 'expected ';
                    $this->writeExpectedValue($expectedValue);
                    echo ', got ';
                    $this->writeBadValue($value);
                } else {
                    $this->writeOkValue($value);
                }
                echo "\n";
            } elseif ($expectedValue === null && $verbosity >= ResultReaderService::VERBOSITY_VERY_VERBOSE) {
                echo $label . ': ' . $value;
                echo "\n";
            }
        }

        return $this;
    }

    protected function writeHeadersDiff(
        ?array $allowedHeaders,
        ?array $unallowedHeaders,
        array $responseHeaders,
        int $verbosity
    ): self {
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE && count($allowedHeaders) > 0) {
            echo 'Headers:' . "\n";
            foreach ($responseHeaders as $headerName => $headerValue) {
                $headerWrited = false;
                foreach ($allowedHeaders ?? [] as $allowedHeaderName => $allowedHeaderValue) {
                    if ($allowedHeaderName === $headerName) {
                        if ((string) $allowedHeaderValue === $headerValue) {
                            $this->writeOkValue('  ' . $headerName . ': ' . $headerValue);
                        } else {
                            echo '  ' . $headerName . ': expected ';
                            $this->writeExpectedValue($allowedHeaderValue);
                            echo ', got ';
                            $this->writeBadValue($headerValue);
                        }
                        echo "\n";
                        $headerWrited = true;
                        break;
                    }
                }
                if (in_array($headerName, $unallowedHeaders ?? [])) {
                    echo '  ';
                    $this->writeBadValue($headerName);
                    echo ': ' . $headerValue . ' ' . "\n";
                    $headerWrited = true;
                }

                if ($verbosity >= ResultReaderService::VERBOSITY_VERY_VERBOSE && $headerWrited === false) {
                    echo '  ' . $headerName . ': ' . $headerValue . "\n";
                }
            }

            $responseHeaderNames = array_keys($responseHeaders);
            foreach ($allowedHeaders ?? [] as $headerName => $headerValue) {
                if (in_array($headerName, $responseHeaderNames) === false) {
                    echo '  expected ';
                    $this->writeExpectedValue($headerName . ': ' . $headerValue);
                    echo ', ';
                    $this->writeBadValue('but header not found');
                    echo "\n";
                }
            }
        }

        return $this;
    }

    protected function writeOkValue($value): self
    {
        echo "\e[32m" . $value . "\e[00m";

        return $this;
    }

    protected function writeExpectedValue($value): self
    {
        echo "\e[33m" . $value . "\e[00m";

        return $this;
    }

    protected function writeBadValue($value): self
    {
        echo "\e[31m" . (is_null($value) ? 'NULL' : $value) . "\e[00m";

        return $this;
    }

    protected function writeResult(UrlTest $urlTest, int $verbosity): self
    {
        if ($urlTest->isValid()) {
            echo "\e[42m\e[1;37m OK \e[0m";
        } else {
            echo "\e[41m\e[1;37m FAIL \e[0m";
        }

        echo
            " \e[1m" . $urlTest->getId() . "\e[00m "
            . "\e[1;49;90m" . $urlTest->getConfiguration()->getRequest()->getMethod() . "\e[00m" . ' '
            . $this->getUrlWithPort(
                $urlTest->getConfiguration()->getRequest()->getUrl(),
                $urlTest->getConfiguration()->getRequest()->getPort()
            )
            . ' '
            . "\e[3m" . $urlTest->getResponse()->getTime() . 'ms' . "\e[00m";
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE) {
            echo " \e[00m";
        }

        echo "\n";

        if (
            is_string($urlTest->getResponse()->getErrorMessage())
            && $verbosity >= ResultReaderService::VERBOSITY_VERBOSE
        ) {
            echo "\e[31m" . $urlTest->getResponse()->getErrorMessage() . "\e[00m\n";
        }

        return $this;
    }

    protected function writeEvents(UrlTest $urlTest, string $eventName, int $verbosity): self
    {
        if ($verbosity >= ResultReaderService::VERBOSITY_VERBOSE) {
            $events = $urlTest->getResponse()->getTriggeredEventsByName($eventName);
            $expectedEvents = $urlTest->getConfiguration()->getEventsByName($eventName);
            if (count($events) === 0) {
                echo "Event $eventName: ";
                if (count($expectedEvents) === 0) {
                    $this->writeOkValue('none');
                } else {
                    $this->writeBadValue('none (' . count($expectedEvents) . ' expected)');
                }
                echo '.' . "\n";
            } else {
                echo "Event $eventName (";
                $countOk = count($events);
                if ($events[$countOk - 1]->getProcess()->getExitCode() !== 0) {
                    $countOk--;
                }
                if ($countOk !== count($expectedEvents)) {
                    $this->writeBadValue($countOk . '/' . count($expectedEvents));
                } else {
                    $this->writeOkValue($countOk . '/' . count($expectedEvents));
                }
                echo '):' . "\n";

                foreach ($events as $event) {
                    echo '  ' . $event->getCommand() . ':';
                    if ($verbosity === ResultReaderService::VERBOSITY_VERBOSE) {
                        if ($event->getProcess()->getExitCode() === 0) {
                            $this->writeOkValue(' Ok');
                        } else {
                            $this->writeBadValue(' Fail (' . $event->getProcess()->getExitCode() . ')');
                        }
                        echo '.';
                    } elseif ($verbosity >= ResultReaderService::VERBOSITY_VERY_VERBOSE) {
                        echo "\n";
                        echo '    Exit code: ';
                        if ($event->getProcess()->getExitCode() === 0) {
                            $this->writeOkValue($event->getProcess()->getExitCode());
                        } else {
                            $this->writeBadValue($event->getProcess()->getExitCode());
                        }
                        echo ".\n";

                        $this
                            ->writeProcessOutput($event->getProcess()->getOutput(), 'Output')
                            ->writeProcessOutput($event->getProcess()->getErrorOutput(), 'Error output');
                    }
                    echo "\n";
                }
            }
        }

        return $this;
    }

    protected function writeProcessOutput(string $output, string $name): self
    {
        $outputLines = explode("\n", $output);
        if (count($outputLines) > 0 && $outputLines[count($outputLines) - 1] === '') {
            array_pop($outputLines);
        }
        $countOutputLines = count($outputLines);
        if ($countOutputLines > 0) {
            echo "    $name:\n";
            foreach ($outputLines as $lineIndex => $line) {
                echo "      $line";
                if ($lineIndex < $countOutputLines - 1) {
                    echo "\n";
                }
            }
        }

        return $this;
    }

    protected function getUrlWithPort(string $url, int $port): string
    {
        if ($port === 80) {
            return $url;
        }

        $posDomainEnd = strpos(
            $url,
            '/',
            strpos($url, '//') + 2
        );
        if ($posDomainEnd === false) {
            return $url;
        }

        return substr($url, 0, $posDomainEnd) . ':' . (string) $port . substr($url, $posDomainEnd);
    }
}
