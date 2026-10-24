<?php

namespace App\Services;

use RuntimeException;

/** Small dependency-free PDF renderer that uses the exact MANGNO certificate artwork as its page image. */
class CompletionCertificatePdf
{
    private const WIDTH = 864.0;
    private const HEIGHT = 576.0;
    private const IMAGE_SCALE = 0.5625;

    public function render(array $certificate): string
    {
        $path = public_path('images/MANGNO_Certificate_Template.jpg');
        $jpeg = @file_get_contents($path);
        $size = $jpeg === false ? false : @getimagesizefromstring($jpeg);
        if (! $size || $size[2] !== IMAGETYPE_JPEG || $size[0] !== 1536 || $size[1] !== 1024) {
            throw new RuntimeException('The MANGNO certificate artwork is unavailable.');
        }

        $content = "q\n864 0 0 576 0 0 cm\n/Template Do\nQ\n";
        // Cover the reference placeholder, fixed 12/12 ribbon, baked-in ten stars and sample date.
        $content .= $this->rectPixels(350, 346, 835, 116, [0.965, 0.944, 0.89]);
        $content .= $this->rectPixels(367, 450, 800, 3, [0.70, 0.45, 0.05]);
        $content .= $this->polygonPixels([[1205, 310], [1235, 258], [1410, 258], [1436, 310], [1410, 360], [1235, 360]], [0.91, 0.68, 0.12]);
        $content .= $this->rectPixels(430, 664, 690, 70, [0.012, 0.07, 0.15]);
        $content .= $this->rectPixels(370, 894, 315, 46, [0.965, 0.944, 0.89]);

        $name = trim((string) ($certificate['display_name'] ?? ''));
        $name = $this->toWinAnsi($name);
        $nameSize = min(34, 450 / max(1, strlen($name) * 0.53));
        $content .= $this->centeredText(432, 344, $name, max(12, $nameSize), 'F3', [0.035, 0.11, 0.20]);

        $stars = min(12, max(0, (int) ($certificate['total_stars'] ?? 0)));
        $perfect = $stars === 12;
        $content .= $this->centeredText(744, 398, $perfect ? 'PERFECT SCORE' : 'TOTAL STARS', 12, 'F2', [0.035, 0.10, 0.17]);
        $content .= $this->centeredText(744, 381, $stars.'/12 STARS', 13, 'F2', [0.035, 0.10, 0.17]);

        $startX = 270.0;
        for ($i = 0; $i < 12; $i++) {
            $content .= $this->star($startX + ($i * 28.0), 186.0, $i < $stars ? [1.0, 0.843, 0.0] : [0.392, 0.455, 0.545]);
        }

        $date = trim((string) ($certificate['issue_date'] ?? ''));
        $content .= $this->centeredText(268, 55, $this->toWinAnsi($date), 13, 'F1', [0.08, 0.13, 0.19]);
        $id = 'CERTIFICATE ID  '.(string) ($certificate['certificate_id'] ?? '');
        $content .= $this->centeredText(432, 24, $this->toWinAnsi($id), 7, 'F2', [0.08, 0.13, 0.19]);

        return $this->document($jpeg, $content);
    }

    private function rectPixels(float $left, float $top, float $width, float $height, array $color): string
    {
        $x = $left * self::IMAGE_SCALE;
        $y = (1024 - $top - $height) * self::IMAGE_SCALE;
        $w = $width * self::IMAGE_SCALE;
        $h = $height * self::IMAGE_SCALE;
        return sprintf("q %.4f %.4f %.4f rg %.3f %.3f %.3f %.3f re f Q\n", $color[0], $color[1], $color[2], $x, $y, $w, $h);
    }

    private function polygonPixels(array $points, array $color): string
    {
        $path = '';
        foreach ($points as $index => [$x, $top]) {
            $path .= sprintf('%.3f %.3f %s ', $x * self::IMAGE_SCALE, (1024 - $top) * self::IMAGE_SCALE, $index === 0 ? 'm' : 'l');
        }
        return sprintf("q %.4f %.4f %.4f rg %s h f Q\n", $color[0], $color[1], $color[2], $path);
    }

    private function centeredText(float $centerX, float $baselineY, string $text, float $size, string $font, array $color): string
    {
        $width = strlen($text) * $size * 0.52;
        $x = $centerX - ($width / 2);
        return sprintf("BT /%s %.2f Tf %.4f %.4f %.4f rg 1 0 0 1 %.3f %.3f Tm (%s) Tj ET\n",
            $font, $size, $color[0], $color[1], $color[2], $x, $baselineY, $this->escape($text));
    }

    private function star(float $centerX, float $centerY, array $color): string
    {
        $outer = 8.1;
        $inner = 3.6;
        $path = '';
        for ($i = 0; $i < 10; $i++) {
            $angle = (-M_PI / 2) + ($i * M_PI / 5);
            $radius = $i % 2 === 0 ? $outer : $inner;
            $x = $centerX + cos($angle) * $radius;
            $y = $centerY + sin($angle) * $radius;
            $path .= sprintf('%.3f %.3f %s ', $x, $y, $i === 0 ? 'm' : 'l');
        }
        return sprintf("q %.4f %.4f %.4f rg %s h f Q\n", $color[0], $color[1], $color[2], $path);
    }

    private function toWinAnsi(string $text): string
    {
        $encoded = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        return $encoded === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) : $encoded;
    }

    private function escape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ''], $text);
    }

    private function document(string $jpeg, string $content): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 864 576] /Resources << /XObject << /Template 4 0 R >> /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R >> >> /Contents 8 0 R >>',
            '<< /Type /XObject /Subtype /Image /Width 1536 /Height 1024 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($content).">>\nstream\n".$content."endstream",
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
        return $pdf;
    }
}
