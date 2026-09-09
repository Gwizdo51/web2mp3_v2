<?php declare(strict_types=1);

namespace App\DoctrineDbalTypes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;

class DateTimeMillisecondsType extends DateTimeImmutableType {
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
        return 'DATETIME';
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s.v');
        }
        return parent::convertToDatabaseValue($value, $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTimeImmutable {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return $value;
        }
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.v', $value) ?: parent::convertToPHPValue($value, $platform);
    }
}
