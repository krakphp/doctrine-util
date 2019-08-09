<?php

namespace Krak\DoctrineUtil\DataFixture;


trait DataFixtureConstructors
{
    private function __construct() {
        foreach (self::$dateTimeFields as $fieldName) {
            $this->{$fieldName} = new \DateTimeImmutable();
        }
    }

    public static function create(array $data): self {
        $self = new self();
        $self->fill($data);
        return $self;
    }

    public function fill(array $data): void {
        foreach ($data as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \RuntimeException("Property {$key} does not exist for class: " . get_class());
            }
            $this->{$key} = $value;
        }
    }
}
