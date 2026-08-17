<?php

namespace App\Enums;

enum FileCategory: string
{
    case Image = 'image';
    case Document = 'document';
    case Contract = 'contract';
    case Video = 'video';
    case Zip = 'zip';
    case SourceFile = 'source_file';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Document => 'Document',
            self::Contract => 'Contract',
            self::Video => 'Video',
            self::Zip => 'ZIP File',
            self::SourceFile => 'Source File',
        };
    }
}
