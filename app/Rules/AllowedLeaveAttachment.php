<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class AllowedLeaveAttachment implements Rule
{
    private $extension;

    public function passes($attribute, $value)
    {
        if (!$value instanceof UploadedFile || !$value->isValid()) {
            return false;
        }

        $handle = @fopen($value->getRealPath(), 'rb');
        $header = $handle ? fread($handle, 12) : false;
        if ($handle) fclose($handle);
        if ($header === false) return false;

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($value->getRealPath());
        $types = [
            'jpg' => $mime === 'image/jpeg' && substr($header, 0, 3) === "\xFF\xD8\xFF",
            'png' => $mime === 'image/png' && substr($header, 0, 8) === "\x89PNG\r\n\x1A\n",
            'pdf' => $mime === 'application/pdf' && substr($header, 0, 5) === '%PDF-',
        ];

        foreach ($types as $extension => $valid) {
            if ($valid) {
                $this->extension = $extension;
                return true;
            }
        }

        return false;
    }

    public function message()
    {
        return 'Lampiran harus berupa JPG, PNG, atau PDF asli yang valid.';
    }

    public function extension(): string
    {
        return $this->extension ?: 'bin';
    }
}
