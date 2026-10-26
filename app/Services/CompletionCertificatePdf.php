<?php

namespace App\Services;

use RuntimeException;

/** Exact supplied JPEG plus clipped paper patches and vector account-specific text. */
class CompletionCertificatePdf
{
    private const IMAGE_WIDTH = 1999;
    private const IMAGE_HEIGHT = 1414;
    private const WIDTH = 842.0;
    private const SCALE = self::WIDTH / self::IMAGE_WIDTH;
    private const HEIGHT = self::IMAGE_HEIGHT * self::SCALE;
    private const NORMAL_WIDTHS = [761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584,761,556,600,222,556,333,1000,556,556,333,1000,667,333,1000,600,611,600,600,222,222,333,333,350,556,1000,333,1000,500,333,944,600,500,667,278,333,556,556,556,556,260,556,333,737,370,556,584,333,737,333,400,584,333,333,333,556,537,278,333,333,365,556,834,834,834,611,667,667,667,667,667,667,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,500,556,556,556,556,278,278,278,278,556,556,556,556,556,556,556,584,611,556,556,556,556,500,556,500];
    private const BOLD_WIDTHS = [761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584,761,556,600,278,556,500,1000,556,556,333,1000,667,333,1000,600,611,600,600,278,278,500,500,350,556,1000,333,1000,556,333,944,600,500,667,278,333,556,556,556,556,280,556,333,737,370,556,584,333,737,333,400,584,333,333,333,611,556,278,333,333,365,556,834,834,834,611,722,722,722,722,722,722,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,556,556,556,556,556,278,278,278,278,611,611,611,611,611,611,611,584,611,611,611,611,611,556,611,556];

    public function nameFontSize(string $name): float
    {
        return $this->fit($this->toWinAnsi(trim($name)), 110, 1240, 'F2');
    }

    public function render(array $certificate): string
    {
        $jpeg = @file_get_contents(public_path('images/MANGNO_Certificate_Template.jpg'));
        $size = $jpeg === false ? false : @getimagesizefromstring($jpeg);
        if (!$size || $size[2] !== IMAGETYPE_JPEG || $size[0] !== self::IMAGE_WIDTH || $size[1] !== self::IMAGE_HEIGHT) {
            throw new RuntimeException('The official certificate.jpg artwork is unavailable.');
        }
        $content = sprintf("q %.6f 0 0 %.6f 0 0 cm /Template Do Q\n", self::WIDTH, self::HEIGHT);
        $content .= $this->paperPatch(350, 630, 1300, 170);
        $content .= $this->paperPatch(300, 978, 1420, 212);
        $content .= $this->paperPatch(400, 1200, 400, 85);
        $ink = [.10, .12, .42];
        $name = trim((string) $certificate['display_name']);
        $content .= $this->text(999.5, 760, $this->toWinAnsi($name), $this->nameFontSize($name), 'F2', [.02, .02, .02]);
        $stars = min(12, max(0, (int) $certificate['total_stars']));
        $content .= $this->text(999.5, 1017, 'The recipient has completed all four disaster scenarios and earned', 40, 'F3', $ink);
        $content .= $this->text(999.5, 1069, $stars.'/12 STARS'.($stars === 12 ? ' - PERFECT SCORE' : ''), 40, 'F4', $ink);
        $content .= $this->text(999.5, 1121, 'with all five preparedness achievements unlocked, demonstrating', 40, 'F3', $ink);
        $content .= $this->text(999.5, 1173, 'knowledge of recommended actions for different disaster scenarios.', 40, 'F3', $ink);
        $date = $this->toWinAnsi((string) $certificate['issue_date']);
        $content .= $this->text(600, 1266, $date, $this->fit($date, 48, 500, 'F2'), 'F2', $ink);
        $content .= $this->text(999.5, 1367, $this->toWinAnsi('CERTIFICATE ID  '.$certificate['certificate_id']), 28, 'F1', [.27, .30, .37]);
        return $this->document($jpeg, $content);
    }

    private function paperPatch(float $x, float $y, float $width, float $height): string
    {
        // Resample only unprinted paper at y=480..630; never edit/recompress the JPEG.
        $cropWidth = 150 * $width / $height;
        $sourceX = (self::IMAGE_WIDTH - $cropWidth) / 2;
        $factor = $height / 150;
        $s = self::SCALE;
        return sprintf("q %.6f %.6f %.6f %.6f re W n %.6f 0 0 %.6f %.6f %.6f cm /Template Do Q\n",
            $x*$s, (self::IMAGE_HEIGHT-$y-$height)*$s, $width*$s, $height*$s,
            self::IMAGE_WIDTH*$factor*$s, self::IMAGE_HEIGHT*$factor*$s,
            ($x-$sourceX*$factor)*$s, (self::IMAGE_HEIGHT-$y+480*$factor-self::IMAGE_HEIGHT*$factor)*$s);
    }

    private function width(string $text, float $size, string $font): float
    {
        $widths = in_array($font, ['F2', 'F4'], true) ? self::BOLD_WIDTHS : self::NORMAL_WIDTHS;
        $units = 0;
        for ($i = 0; $i < strlen($text); $i++) $units += $widths[ord($text[$i])] ?? 600;
        return $units * $size / 1000;
    }

    private function fit(string $text, float $maximum, float $width, string $font): float
    {
        return min($maximum, $width / max(.001, $this->width($text, 1, $font)));
    }

    private function text(float $center, float $baselineTop, string $text, float $size, string $font, array $color): string
    {
        $x = ($center - $this->width($text, $size, $font)/2) * self::SCALE;
        $y = (self::IMAGE_HEIGHT - $baselineTop) * self::SCALE;
        return sprintf("BT /%s %.5f Tf %.4f %.4f %.4f rg 1 0 0 1 %.5f %.5f Tm (%s) Tj ET\n",
            $font, $size*self::SCALE, $color[0], $color[1], $color[2], $x, $y, $this->escape($text));
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
            sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.6f %.6f] /Resources << /XObject << /Template 4 0 R >> /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R /F4 9 0 R >> >> /Contents 8 0 R >>', self::WIDTH, self::HEIGHT),
            '<< /Type /XObject /Subtype /Image /Width 1999 /Height 1414 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($content).">>\nstream\n".$content."endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique /Encoding /WinAnsiEncoding >>',
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index+1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf."trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
