<?php

namespace Terrazza\Component\Serializer\Denormalizer;

use DateTime;
use Exception;
use InvalidArgumentException;

trait DenormalizerTrait {
    private array $traceKey                         = [];
    private array $builtIn                          = ["int", "integer", "float", "double", "string", "DateTime"];

    /**
     * @param string $type
     * @return bool
     */
    private function isBuiltIn(string $type) : bool {
        return in_array($type, $this->builtIn);
    }

    /**
     * @param string $traceKey
     */
    private function pushTraceKey(string $traceKey) : void {
        array_push($this->traceKey, $traceKey);
    }

    private function popTraceKey() : void {
        array_pop($this->traceKey);
    }

    /**
     * @return string
     */
    private function getTraceKeys() : string {
        $response                                   = join(".",$this->traceKey);
        return strtr($response, [".[" => "["]);
    }

    /**
     * @param string|null $parameterType
     * @param mixed $inputValue
     * @return mixed
     * @throws Exception
     */
    private function getApprovedBuiltInValue(?string $parameterType, $inputValue) {
        if ($parameterType) {
            $inputType                              = gettype($inputValue);
            $inputType                              = strtr($inputType, [
                "integer"                           => "int",
                "double"                            => "float"
            ]);
            if ($parameterType === $inputType) {
                return $inputValue;
            } elseif ($parameterType == "string" && ($inputType === "int" || $inputType === "float")) {
                return $inputValue;
            } elseif ($parameterType == "float" && $inputType === "int") {
                return $inputValue;
            }
            else {
                $user_callback                      = [$this, "getApprovedBuiltInValue_".$parameterType];
                if (is_callable($user_callback, false, $callback)) {
                    return call_user_func($user_callback, $inputValue);
                }
            }
            throw new InvalidArgumentException("argument ".$this->getTraceKeys()." expected type ".$parameterType.", given ".$inputType);
        } else {
            return $inputValue;
        }
    }

    private function getApprovedBuiltInValue_DateTime(string $inputValue) : DateTime {
        try {
            $result                                 = new DateTime($inputValue);
            $lastErrors                             = DateTime::getLastErrors();
            if ($lastErrors["warning_count"] || $lastErrors["error_count"]) {
                throw new InvalidArgumentException("argument ".$this->getTraceKeys()." value is not a valid date, given ".$inputValue);
            }
            return $result;
        } catch (Exception $exception) {
            throw new InvalidArgumentException("argument ".$this->getTraceKeys()." value is not a valid date, given ".$inputValue);
        }
    }

    /**
     * @param string $annotation
     * @return string|null
     */
    private function extractTypeFromAnnotation(string $annotation) :?string {
        $annotation                             = strtr($annotation, [
            "[]" => ""
        ]);
        $annotationTypes                        = explode("|", $annotation);
        $annotationTypes                        = array_diff($annotationTypes, ['array']);
        if (count($annotationTypes) > 1) {
            throw new InvalidArgumentException("unable to return a unique type, multiple types given");
        }
        return array_shift($annotationTypes);
    }
}