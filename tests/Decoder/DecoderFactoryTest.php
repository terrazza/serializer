<?php
namespace Terrazza\Component\Serializer\Tests\Decoder;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Terrazza\Component\Serializer\Decoder\DecoderFactory;
use Terrazza\Component\Serializer\Decoder\JsonDecoder;
use Terrazza\Component\Serializer\Tests\_Mocks\Logger;

class DecoderFactoryTest extends TestCase {

    function testDecode() {
        $json = json_encode(
            $output = array(
                1 => array(
                    'English' => array(
                        'One',
                        'January'
                    ),
                    'French' => array(
                        'Une',
                        'Janvier'
                    )
                )
            )
        );
        $logger     = Logger::get();
        $factory    = new DecoderFactory($logger);
        $factory->addDecoder("my", new JsonDecoder(), "your");
        $response   = $factory->decode($json, "your");
        $this->assertEquals($output, $response);
    }

    public function testNullNoDecoder() {
        $logger     = Logger::get();
        $factory    = new DecoderFactory($logger);
        $this->assertNull($factory->decode(null, "your"));
    }

    public function testNoDecoderFound() {
        $logger     = Logger::get();
        $factory    = new DecoderFactory($logger);
        $this->expectException(RuntimeException::class);
        $factory->decode("", "your");
    }

    public function testGetAddEncoder() {
        $logger     = Logger::get();
        $factory    = new DecoderFactory($logger);
        $factory->addDecoder("my", $decoder = new JsonDecoder(), $pattern = "myPattern");
        $this->assertEquals([
            null,
            $decoder,
        ],[
            $factory->getDecoder("unknown"),
            $factory->getDecoder($pattern)
        ]);
    }
}