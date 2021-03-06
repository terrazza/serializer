<?php
namespace Terrazza\Component\Serializer\Decoder;
use Terrazza\Component\Serializer\Decoder\Exception\DecoderException;
use Terrazza\Component\Serializer\DecoderInterface;

class JsonDecoder implements DecoderInterface {
    private int $decodeDepth;
    private int $decodeFlags;
    public function __construct(int $decodeDepth=512, int $decodeFlags = 0) {
        $this->decodeDepth                          = $decodeDepth;
        $this->decodeFlags                          = $decodeFlags;
    }

    /**
     * @param mixed $data
     * @return array|null
     * @throws DecoderException
     */
    function decode($data) : ?array {
        if (is_null($data)) {
            return null;
        }
        $response                                   = json_decode($data, true, $this->decodeDepth, $this->decodeFlags);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DecoderException("unable to convert json: ".json_last_error_msg());
        }
        return $response;
    }
}