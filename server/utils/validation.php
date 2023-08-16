<?php

class Validator
{
    /** @var Rule[] $rules */
    private array $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    public function validate(array $value): ?string
    {
        $keys = array_keys($this->rules);

        for ($i = 0; $i < count($keys); $i++)
            for ($j = 0; $j < count($this->rules[$keys[$i]]); $j++)
                if (!$this->rules[$keys[$i]][$j]->onRule($keys[$i], $value))
                    return $this->rules[$keys[$i]][$j]->getMessage();

        return null;
    }
}

abstract class Rule
{
    protected string $message;

    /**
     * Rule constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function getMessage(string $key): string
    {
        return sprintf($this->message, $key);
    }

    public abstract function onRule(string $key, array $value): bool;

    public static function required(string $message = 'Поле %s обязательно для заполнения'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                return array_key_exists($key, $value);
            }
        };
    }

    public static function bool(string $message = 'Поле %s должно быть булевым значением'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_BOOL) != false;
            }
        };
    }

    public static function int(string $message = 'Поле %s должно быть челочисленным значением'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_INT) != false;
            }
        };
    }

    public static function float(string $message = 'Поле %s должно быть числом с плавающей точкой'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_FLOAT) != false;
            }
        };
    }

    public static function min(int|float $min, string $message = 'Поле %s меньше %d'): static
    {
        return new class($min, $message) extends Rule {
            private int|float $min;

            public function __construct(int|float $min, string $message)
            {
                parent::__construct($message);

                $this->min = $min;
            }

            public function getMessage(string $key): string
            {
                return sprintf($this->message, $key, $this->min);
            }

            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                if (is_int($this->min))
                    return filter_var($value[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => $this->min]]) != false;
                else if (is_float($this->min))
                    return filter_var($value[$key], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => $this->min]]) != false;

                return false;
            }
        };
    }

    public static function max(int|float $max, string $message = 'Поле %s больше %d'): static
    {
        return new class($max, $message) extends Rule {
            private int|float $max;

            public function __construct(int|float $max, string $message)
            {
                parent::__construct($message);

                $this->max = $max;
            }

            public function getMessage(string $key): string
            {
                return sprintf($this->message, $key, $this->max);
            }

            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                if (is_int($this->max))
                    return filter_var($value[$key], FILTER_VALIDATE_INT, ['options' => ['max_range' => $this->max]]) != false;
                else if (is_float($this->max))
                    return filter_var($value[$key], FILTER_VALIDATE_FLOAT, ['options' => ['max_range' => $this->max]]) != false;

                return false;
            }
        };
    }

    public static function regexp(string $value, string $message = 'Поле %s должно быть определенного формата'): static
    {
        return new class($value, $message) extends Rule {
            private string $value;

            public function __construct(string $value, string $message)
            {
                parent::__construct($message);

                $this->value = $value;
            }

            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $this->value]]) != false;
            }
        };
    }

    public static function url(string $message = 'Поле %s должно быть формата ссылки', bool $path = false, bool $query = false): static
    {
        return new class($path, $query, $message) extends Rule {
            private bool $path;
            private bool $query;

            public function __construct(bool $path, bool $query, string $message)
            {
                parent::__construct($message);

                $this->path = $path;
                $this->query = $query;
            }

            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                if ($this->path || $this->query)
                    return filter_var($value[$key], FILTER_VALIDATE_URL, ['flags' => ($this->path ? FILTER_FLAG_PATH_REQUIRED : 0) | ($this->query ? FILTER_FLAG_QUERY_REQUIRED : 0)]) != false;

                return filter_var($value[$key], FILTER_VALIDATE_URL) != false;
            }
        };
    }

    public static function email(string $message = 'Поле %s должно быть формата почты'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_EMAIL) != false;
            }
        };
    }

    public static function ipV4(string $message = 'Поле %s должно быть формата ipV4'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV4]) != false;
            }
        };
    }

    public static function ipV6(string $message = 'Поле %s должно быть формата ipV6'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_IP, ['flags' => FILTER_FLAG_IPV6]) != false;
            }
        };
    }

    public static function mac(string $message = 'Поле %s должно быть формата MAC-адреса'): static
    {
        return new class($message) extends Rule {
            public function onRule(string $key, array $value): bool
            {
                if (!array_key_exists($key, $value))
                    return true;

                return filter_var($value[$key], FILTER_VALIDATE_MAC) != false;
            }
        };
    }

    public static function custom(callable $value, string $message = 'Поле %s не прошло проверку'): static
    {
        return new class($value, $message) extends Rule {
            /** @var callable $value */
            private $value;

            public function __construct(callable $value, string $message)
            {
                parent::__construct($message);

                $this->value = $value;
            }

            public function onRule(string $key, array $value): bool
            {
                return call_user_func($this->value, [$key, $value]);
            }
        };
    }
}
