<?php

namespace Terrazza\Component\Serializer\Tests\Examples;

use DateTime;
use Terrazza\Component\Serializer\IDeserializer;
use Terrazza\Component\Serializer\Factory\Json\JsonDeserializer;
use Terrazza\Component\Serializer\Factory\Json\JsonISerializer;
use Terrazza\Component\Serializer\ISerializer;
use Terrazza\Component\Serializer\Tests\Examples\Model\SerializerRealLifeProductAmount;
use Terrazza\Component\Serializer\Tests\Examples\Model\SerializerRealLifeProductLabel;
use Terrazza\Component\Serializer\Tests\Examples\Model\SerializerRealLifeUUID;
use Terrazza\Component\Serializer\Tests\Examples\Serializer\SerializerRealLifePresentAmount;
use Terrazza\Component\Serializer\Tests\Examples\Serializer\SerializerRealLifePresentDateTime;
use Terrazza\Component\Serializer\Tests\Examples\Serializer\SerializerRealLifePresentLabel;
use Terrazza\Component\Serializer\Tests\Examples\Serializer\SerializerRealLifePresentUUID;

class JsonArrayUnit {

    public static function getDeserializer(bool $logLevel=false) : IDeserializer {
        return new JsonDeserializer(
            LoggerUnit::getLogger("Deserializer", $logLevel)
        );
    }

    public static function getNameConverter() : array {
        return [
            SerializerRealLifeUUID::class => SerializerRealLifePresentUUID::class,
            SerializerRealLifeProductAmount::class => SerializerRealLifePresentAmount::class,
            SerializerRealLifeProductLabel::class => SerializerRealLifePresentLabel::class,
            DateTime::class => SerializerRealLifePresentDateTime::class
        ];
    }

    public static function getSerializer(bool $logLevel=false) : ISerializer {
        return new JsonISerializer(
            LoggerUnit::getLogger("Serializer", $logLevel),
            self::getNameConverter()
        );
    }
}