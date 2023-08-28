<?php

namespace Selpol\Validator;

abstract class Filter extends ValidatorItem
{
    public static function default(mixed $value, bool $string = false, string $message = 'Ошибка фильтрации данных'): static
    {
        return new class($value, $string, $message) extends Filter {
            private mixed $value;
            private bool $string;

            public function __construct(mixed $value, bool $string, string $message)
            {
                parent::__construct($message);

                $this->value = $value;
                $this->string = $string;
            }

            public function onItem(string $key, array $value): mixed
            {
                if (array_key_exists($key, $value) && (!is_null($value[$key])) || !$this->string && $value[$key] != '')
                    return $value[$key];

                return $this->value;
            }
        };
    }

    public static function encoded(string $message = 'Ошибка фильтрации данных'): static
    {
        return new class($message) extends Filter {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_SANITIZE_ENCODED);
            }
        };
    }

    public static function slashes(string $message = 'Ошибка фильтрации данных'): static
    {
        return new class($message) extends Filter {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_SANITIZE_ADD_SLASHES);
            }
        };
    }

    public static function specialChars(string $message = 'Ошибка фильтрации данных'): static
    {
        return new class($message) extends Filter {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        };
    }

    public static function fullSpecialChars(string $message = 'Ошибка фильтрации данных'): static
    {
        return new class($message) extends Filter {
            public function onItem(string $key, array $value): mixed
            {
                return $this->filter($key, $value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }
        };
    }
}