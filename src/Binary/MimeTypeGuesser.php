<?php

namespace JoliCode\MediaBundle\Binary;

use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypesInterface;

class MimeTypeGuesser
{
    public function __construct(
        private readonly MimeTypesInterface $mimeTypes,
        private readonly MimeTypeGuesserInterface $mimeTypeGuesser,
    ) {
    }

    public function getPossibleExtension(string $mimeType): string
    {
        $possibleExtensions = $this->mimeTypes->getExtensions($mimeType);

        return $possibleExtensions[0] ?? $mimeType;
    }

    public function guessMimeTypeFromContent(string $content): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'media');
        file_put_contents($temporaryFile, $content);

        try {
            // MimeTypes::guessMimeType() skips the guessers that the runtime
            // does not support (eg. FileBinaryMimeTypeGuesser without the
            // "file" binary), while calling a guesser directly throws in this
            // case: use it first, and keep the injected guesser as a fallback
            // when the chain came up empty-handed
            $mimeType = $this->mimeTypes->guessMimeType($temporaryFile);
            $mimeType ??= $this->mimeTypeGuesser->guessMimeType($temporaryFile);
        } finally {
            unlink($temporaryFile);
        }

        return $mimeType ?? 'application/octet-stream';
    }
}
