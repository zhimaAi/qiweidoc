<?php

namespace Alexkart\CurlBuilder\Tests;

use Alexkart\CurlBuilder\Command;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{
    public function getNewCommand(): Command
    {
        $command = new Command();
        $command->setUrl('http://example.com');

        return $command;
    }

    public function testBuildMinimal(): void
    {
        $command = new Command();
        $this->assertSame('curl', $command->build());
    }

    public function testBuildWithUrl(): void
    {
        $command = new Command();
        $command->setUrl('http://example.com/test');
        $this->assertSame('curl http://example.com/test', $command->build());
    }

    public function testBuildWithOption(): void
    {
        $command = new Command();
        $command->addOption('-v');
        $this->assertSame('curl -v', $command->build());
    }

    public function testBuildWithOptions(): void
    {
        $command = new Command();
        $command->addOption('-v');
        $command->addOption('-L');
        $this->assertSame('curl -v -L', $command->build());
    }

    public function testBuildWithUrlAndOption(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('-v');
        $this->assertSame('curl -v http://example.com', $command->build());
    }

    public function testBuildWithUrlAndOptions(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('-v');
        $command->addOption('-L');
        $this->assertSame('curl -v -L http://example.com', $command->build());


    }

    public function testBuildSetOptions(): void
    {
        $command = $this->getNewCommand();

        $command->setOptions([]);
        $this->assertSame('curl http://example.com', $command->build());

        $command->setOptions(['-L' => [null], '-v' => [null]]);
        $this->assertSame('curl -L -v http://example.com', $command->build());

        $command->setOptions(['-L' => null, '-v' => null]);
        $this->assertSame('curl -L -v http://example.com', $command->build());

        $command->setOptions(['-d' => 'test1', '-H' => 'test2']);
        $this->assertSame("curl -d 'test1' -H 'test2' http://example.com", $command->build());

        $command->setOptions(['-H' => ['test1', 'test2']]);
        $this->assertSame("curl -H 'test1' -H 'test2' http://example.com", $command->build());

        $command->setOptions(['-L', '-v']);
        $this->assertSame('curl -L -v http://example.com', $command->build());

        // mixed format
        $command->setOptions(['-L', '-v' => null, '--insecure' => [null], '-d' => 'test1', '-H' => ['test2']]);
        $this->assertSame("curl -L -v --insecure -d 'test1' -H 'test2' http://example.com", $command->build());
    }

    public function testBuildDuplicatedOptions(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('-v');
        $command->addOption('-L');
        $command->addOption('-L');
        $this->assertSame('curl -v -L -L http://example.com', $command->build());
    }

    public function testBuildDuplicatedOptionsAddOptions(): void
    {
        $command = $this->getNewCommand();
        $command->addOptions(['-v', '-v']);
        $this->assertSame('curl -v -v http://example.com', $command->build());
    }

    public function testBuildDuplicatedOptionsSetOptions(): void
    {
        $command = $this->getNewCommand();
        $command->setOptions(['-v', '-v']);
        $this->assertSame('curl -v -v http://example.com', $command->build());
    }

    public function testBuildSetTemplate(): void
    {
        $command = $this->getNewCommand();
        $command->setTemplate(Command::TEMPLATE_COMMAND_NAME . Command::TEMPLATE_URL . Command::TEMPLATE_OPTIONS);
        $command->addOption('-v');
        $command->addOption('-L');
        $this->assertSame('curl http://example.com -v -L', $command->build());

        $command->setTemplate(Command::TEMPLATE_COMMAND_NAME . Command::TEMPLATE_URL);
        $this->assertSame('curl http://example.com', $command->build());

        $command->setTemplate('');
        $this->assertSame('', $command->build());
    }

    public function testBuildWithLongOptions(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('--verbose');
        $this->assertSame('curl --verbose http://example.com', $command->build());

        $command->addOption('--location');
        $this->assertSame('curl --verbose --location http://example.com', $command->build());
    }

    public function testBuildWithArgumentsToOptions(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('-d', 'arbitrary');
        $this->assertSame("curl -d 'arbitrary' http://example.com", $command->build());

        // data from file
        $command = $this->getNewCommand();
        $command->addOption('-d', '@json.txt');
        $this->assertSame("curl -d '@json.txt' http://example.com", $command->build());

        // argument with spaces
        $command = $this->getNewCommand();
        $command->addOption('-d', 'I am your father');
        $this->assertSame("curl -d 'I am your father' http://example.com", $command->build());
    }

    public function testBuildSetQuoteCharacter(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('-d', 'arbitrary');

        // default is single
        $this->assertSame("curl -d 'arbitrary' http://example.com", $command->build());

        $command->setQuoteCharacter(Command::QUOTE_DOUBLE);
        $this->assertSame('curl -d "arbitrary" http://example.com', $command->build());

        $command->setQuoteCharacter(Command::QUOTE_SINGLE);
        $this->assertSame("curl -d 'arbitrary' http://example.com", $command->build());

        $command->setQuoteCharacter(Command::QUOTE_NONE);
        $this->assertSame('curl -d arbitrary http://example.com', $command->build());
    }

    public function testBuildEscapeArguments(): void
    {
        $argument = <<<ARG
x=test'1
ARG;
        $expected = <<<EXP
curl -d $'x=test\'1' http://example.com
EXP;
        $command = $this->getNewCommand();
        $command->setQuoteCharacter(Command::QUOTE_SINGLE);
        $command->addOption('-d', $argument);
        $this->assertSame($expected, $command->build());

        $argument = <<<ARG
x=test"2
ARG;
        $expected = <<<EXP
curl -d 'x=test"2' http://example.com
EXP;
        $command = $this->getNewCommand();
        $command->setQuoteCharacter(Command::QUOTE_SINGLE);
        $command->addOption('-d', $argument);
        $this->assertSame($expected, $command->build());

        $argument = <<<ARG
x=test'1"2
ARG;
        $expected = <<<EXP
curl -d $'x=test\'1"2' http://example.com
EXP;
        $command = $this->getNewCommand();
        $command->setQuoteCharacter(Command::QUOTE_SINGLE);
        $command->addOption('-d', $argument);
        $this->assertSame($expected, $command->build());

        $argument = <<<ARG
x=test'1
ARG;
        $expected = <<<EXP
curl -d "x=test'1" http://example.com
EXP;
        $command = $this->getNewCommand();
        $command->setQuoteCharacter(Command::QUOTE_DOUBLE);
        $command->addOption('-d', $argument);
        $this->assertSame($expected, $command->build());

        $argument = <<<ARG
x=test"2
ARG;
        $expected = <<<EXP
curl -d "x=test\"2" http://example.com
EXP;
        $command = $this->getNewCommand();
        $command->setQuoteCharacter(Command::QUOTE_DOUBLE);
        $command->addOption('-d', $argument);
        $this->assertSame($expected, $command->build());

        $argument = <<<ARG
x=test'1"2
ARG;
        $expected = <<<EXP
curl -d "x=test'1\"2" http://example.com
EXP;
        $command = $this->getNewCommand();
        $command->setQuoteCharacter(Command::QUOTE_DOUBLE);
        $command->addOption('-d', $argument);
        $this->assertSame($expected, $command->build());
    }

    public function testBuildMultipleOptions(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('-H', 'Connection: keep-alive');
        $command->addOption('-H', 'Cache-Control: max-age=0');
        $this->assertSame("curl -H 'Connection: keep-alive' -H 'Cache-Control: max-age=0' http://example.com", $command->build());
    }

    public function testBuildAddOptions(): void
    {
        $command = $this->getNewCommand();
        $command->addOption('-v');
        $command->addOptions([
            '-L',
            '-d' => 'test'
        ]);
        $this->assertSame("curl -v -L -d 'test' http://example.com", $command->build());
    }

    public function testBuildPsrHttpRequest(): void
    {
        $request = new Request('GET', 'http://example.com');
        $command = new Command();
        $command->setRequest($request);
        $this->assertSame('curl http://example.com', $command->build());

        $request = new Request('POST', 'http://example.com', [
            'Connection' => ['keep-alive'],
            'Accept' => [
                'text/html',
                'application/xhtml+xml',
            ],
        ], 'data');

        $command = new Command();
        $command->setRequest($request);
        $this->assertSame("curl -H 'Connection: keep-alive' -H 'Accept: text/html, application/xhtml+xml' -d 'data' http://example.com", $command->build());

        $command = new Command();
        $command->setRequest($request, false);
        $this->assertSame('curl', $command->build());
        $command->parseRequest();
        $this->assertSame("curl -H 'Connection: keep-alive' -H 'Accept: text/html, application/xhtml+xml' -d 'data' http://example.com", $command->build());
    }

    public function testBuildPsrHttpRequestHeaderExceptions(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Set-Cookie' => [
                'test1=1; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly',
                'test2=2; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly',
            ],
        ]);

        $command = new Command();
        $command->setRequest($request);
        $this->assertSame("curl -H 'Set-Cookie: test1=1; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly' -H 'Set-Cookie: test2=2; Expires=Thu, 01-Jan-1970 00:00:10 GMT; Path=/; Secure; HttpOnly' http://example.com", $command->build());
    }

    public function testBuildParseRequest(): void
    {
        $command = new Command();
        $this->assertFalse($command->parseRequest());

        $request = new Request('GET', 'http://example.com');
        $command->setRequest($request);
        $this->assertTrue($command->parseRequest());
    }

    public function testBuildPsrHttpRequestSkipHostHeader(): void
    {
        $request = new Request('GET', 'http://example.com', [
            'Host' => ['example.com'],
            'Custom' => ['value'],
        ]);

        $command = new Command();
        $command->setRequest($request);
        $this->assertSame("curl -H 'Custom: value' http://example.com", $command->build());
    }

    public function testBuildWithQuoteNoneAndSpaces(): void
    {
        $command = $this->getNewCommand();
        $command->setQuoteCharacter(Command::QUOTE_NONE);
        $command->addOption('-d', 'value with spaces');
        $this->assertSame('curl -d value\\ with\\ spaces http://example.com', $command->build());
    }
}
