<?php
namespace Terrazza\Component\Serializer\Tests\_Examples\Serializer;
use Terrazza\Component\Serializer\NormalizerConverterInterface;
use Terrazza\Component\Serializer\Tests\_Examples\Model\SerializerRealLifeProductLabel;

class SerializerRealLifePresentLabel implements NormalizerConverterInterface {
    private SerializerRealLifeProductLabel $value;

    public function __construct(SerializerRealLifeProductLabel $value) {
        $this->value = $value;
    }

    public function getValue() : string {
        return $this->value->getValue();
    }
}