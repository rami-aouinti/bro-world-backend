<?php

declare(strict_types=1);

namespace App\Media\Domain\Enum;

/**
 * Class FileType
 *
 * @package App\Media\Domain\Enum
 * @author  Rami Aouinti <rami.aouinti@tkdeutschland.de>
 */
enum FileType: string
{
    case PDF = 'pdf';
    case WORD = 'word';
    case EXCEL = 'excel';
    case POWERPOINT = 'powerpoint';
    case IMAGE = 'image';
    case TEXT = 'text';
    case AUDIO = 'audio';
    case VIDEO = 'video';
    case ARCHIVE = 'archive';
    case OTHER = 'other';

    public static function fromExtension(string $extension): FileType
    {
        return match (strtolower($extension)) {
            'pdf' => self::PDF,
            'doc', 'docx' => self::WORD,
            'xls', 'xlsx' => self::EXCEL,
            'ppt', 'pptx' => self::POWERPOINT,
            'txt', 'md' => self::TEXT,
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp' => self::IMAGE,
            'mp3', 'wav', 'ogg', 'aac' => self::AUDIO,
            'mp4', 'mov', 'avi', 'mkv', 'webm' => self::VIDEO,
            'zip', 'rar', '7z', 'tar', 'gz' => self::ARCHIVE,
            default => self::OTHER,
        };
    }
}
