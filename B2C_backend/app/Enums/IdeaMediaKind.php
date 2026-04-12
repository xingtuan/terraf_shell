<?php

namespace App\Enums;

enum IdeaMediaKind: string
{
    case Sketch = 'sketch';
    case ConceptImage = 'concept_image';
    case RenderImage = 'render_image';
    case PdfPresentation = 'pdf_presentation';
    case SpecSheet = 'spec_sheet';
    case ReferenceDocument = 'reference_document';
    case Model3d = 'model_3d';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function uploadValues(): array
    {
        return [
            self::Sketch->value,
            self::ConceptImage->value,
            self::RenderImage->value,
            self::PdfPresentation->value,
            self::SpecSheet->value,
            self::ReferenceDocument->value,
        ];
    }

    public static function externalValues(): array
    {
        return [
            self::Model3d->value,
        ];
    }

    public static function imageValues(): array
    {
        return [
            self::Sketch->value,
            self::ConceptImage->value,
            self::RenderImage->value,
        ];
    }

    public static function documentValues(): array
    {
        return [
            self::PdfPresentation->value,
            self::SpecSheet->value,
            self::ReferenceDocument->value,
        ];
    }

    public static function defaultForType(IdeaMediaType $type): self
    {
        return match ($type) {
            IdeaMediaType::Image => self::ConceptImage,
            IdeaMediaType::Document => self::ReferenceDocument,
            IdeaMediaType::External3d => self::Model3d,
        };
    }

    public function supportsType(IdeaMediaType $type): bool
    {
        return in_array($this->value, match ($type) {
            IdeaMediaType::Image => self::imageValues(),
            IdeaMediaType::Document => self::documentValues(),
            IdeaMediaType::External3d => self::externalValues(),
        }, true);
    }
}
