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
    private const NAME_MAX_WIDTH = 1240.0;
    private const NAME_TRACKING = 1.5;
    private ?array $playfair = null;
    private const NORMAL_WIDTHS = [761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,278,278,278,469,556,333,556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,334,260,334,584,761,556,600,222,556,333,1000,556,556,333,1000,667,333,1000,600,611,600,600,222,222,333,333,350,556,1000,333,1000,500,333,944,600,500,667,278,333,556,556,556,556,260,556,333,737,370,556,584,333,737,333,400,584,333,333,333,556,537,278,333,333,365,556,834,834,834,611,667,667,667,667,667,667,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,500,556,556,556,556,278,278,278,278,556,556,556,556,556,556,556,584,611,556,556,556,556,500,556,500];
    private const BOLD_WIDTHS = [761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,761,278,333,474,556,556,889,722,238,333,333,389,584,278,333,278,278,556,556,556,556,556,556,556,556,556,556,333,333,584,584,584,611,975,722,722,722,722,667,611,778,722,278,556,722,611,833,722,778,667,778,722,667,611,722,667,944,667,667,611,333,278,333,584,556,333,556,611,556,611,556,333,611,611,278,278,556,278,889,611,611,611,611,389,556,333,611,556,778,556,556,500,389,280,389,584,761,556,600,278,556,500,1000,556,556,333,1000,667,333,1000,600,611,600,600,278,278,500,500,350,556,1000,333,1000,556,333,944,600,500,667,278,333,556,556,556,556,280,556,333,737,370,556,584,333,737,333,400,584,333,333,333,611,556,278,333,333,365,556,834,834,834,611,722,722,722,722,722,722,1000,722,667,667,667,667,278,278,278,278,722,722,778,778,778,778,778,584,778,722,722,722,722,667,667,611,556,556,556,556,556,556,889,556,556,556,556,556,278,278,278,278,611,611,611,611,611,611,611,584,611,611,611,611,611,556,611,556];

    public function nameFontSize(string $name): float
    {
        return $this->nameLayout($name)['fontSize'];
    }

    public function uppercaseName(string $name): string
    {
        return mb_strtoupper(trim($name), 'UTF-8');
    }

    /** Display-only name layout shared by the SVG preview and embedded PDF renderer. */
    public function nameLayout(string $name): array
    {
        $text = $this->uppercaseName($name);
        $font = $this->playfairFont();
        $chosen = ['lines' => [$text], 'fontSize' => 110.0];
        for ($size = 110.0; $size >= 48.0; $size -= 1.0) {
            $lines = $this->wrapName($text, $font, $size);
            $capHeight = $font['capHeight'] / $font['unitsPerEm'] * $size;
            $leading = $size * .95;
            $height = $capHeight + max(0, count($lines) - 1) * $leading;
            if (count($lines) <= 3 && $height <= 165.0) {
                $chosen = ['lines' => $lines, 'fontSize' => $size];
                break;
            }
            $chosen = ['lines' => $lines, 'fontSize' => $size];
        }
        $chosen['tracking'] = self::NAME_TRACKING;
        $chosen['leading'] = $chosen['fontSize'] * .95;
        $baselineCenter = count($chosen['lines']) > 1 ? 715.0 : 760.0;
        $chosen['firstBaseline'] = $baselineCenter - max(0, count($chosen['lines']) - 1) * $chosen['leading'] / 2;
        return $chosen;
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
        $name = $this->uppercaseName((string) $certificate['display_name']);
        $font = $this->playfairFont();
        $layout = $this->nameLayout($name);
        $usedGlyphs = [];
        $baseline = $layout['firstBaseline'];
        foreach ($layout['lines'] as $line) {
            $content .= $this->playfairText(999.5, $baseline, $line, $layout['fontSize'], $font, $usedGlyphs);
            $baseline += $layout['leading'];
        }
        $stars = min(12, max(0, (int) $certificate['total_stars']));
        $content .= $this->text(999.5, 1017, 'The recipient has completed all four disaster scenarios and earned', 40, 'F3', $ink);
        $content .= $this->text(999.5, 1069, $stars.'/12 STARS'.($stars === 12 ? ' - PERFECT SCORE' : ''), 40, 'F4', $ink);
        $content .= $this->text(999.5, 1121, 'with all five preparedness achievements unlocked, demonstrating', 40, 'F3', $ink);
        $content .= $this->text(999.5, 1173, 'knowledge of recommended actions for different disaster scenarios.', 40, 'F3', $ink);
        $date = $this->toWinAnsi((string) $certificate['issue_date']);
        $content .= $this->text(600, 1266, $date, $this->fit($date, 48, 500, 'F2'), 'F2', $ink);
        $content .= $this->text(999.5, 1367, $this->toWinAnsi('CERTIFICATE ID  '.$certificate['certificate_id']), 28, 'F1', [.27, .30, .37]);
        return $this->document($jpeg, $content, $font, $usedGlyphs);
    }

    private function wrapName(string $text, array $font, float $size): array
    {
        if ($text === '') return [''];
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];
        $lines = [];
        $line = '';
        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($line === '' || $this->playfairWidth($candidate, $font) * $size + max(0, count($this->characters($candidate)) - 1) * self::NAME_TRACKING <= self::NAME_MAX_WIDTH) {
                if ($this->playfairWidth($candidate, $font) * $size + max(0, count($this->characters($candidate)) - 1) * self::NAME_TRACKING <= self::NAME_MAX_WIDTH) {
                    $line = $candidate;
                    continue;
                }
            }
            if ($line !== '') { $lines[] = $line; $line = ''; }
            if ($this->playfairWidth($word, $font) * $size + max(0, count($this->characters($word)) - 1) * self::NAME_TRACKING <= self::NAME_MAX_WIDTH) {
                $line = $word;
                continue;
            }
            foreach ($this->characters($word) as $character) {
                $candidate = $line.$character;
                if ($line !== '' && $this->playfairWidth($candidate, $font) * $size + (count($this->characters($candidate)) - 1) * self::NAME_TRACKING > self::NAME_MAX_WIDTH) {
                    $lines[] = $line;
                    $line = $character;
                } else $line = $candidate;
            }
        }
        if ($line !== '') $lines[] = $line;
        return $lines ?: [''];
    }

    private function characters(string $text): array
    {
        return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function unicodeValue(string $character): int
    {
        $encoded = iconv('UTF-8', 'UTF-32BE', $character);
        return $encoded === false ? 0 : unpack('Nvalue', $encoded)['value'];
    }

    private function playfairWidth(string $text, array $font): float
    {
        $width = 0.0;
        foreach ($this->characters($text) as $character) {
            $gid = $font['cmap'][$this->unicodeValue($character)] ?? 0;
            $width += ($font['widths'][$gid] ?? $font['defaultWidth']) / $font['unitsPerEm'];
        }
        return $width;
    }

    private function playfairText(float $center, float $baselineTop, string $text, float $size, array $font, array &$usedGlyphs): string
    {
        $glyphs = [];
        $width = 0.0;
        foreach ($this->characters($text) as $character) {
            $unicode = $this->unicodeValue($character);
            $gid = $font['cmap'][$unicode] ?? 0;
            $glyphs[] = sprintf('%04X', $gid);
            $width += ($font['widths'][$gid] ?? $font['defaultWidth']) / $font['unitsPerEm'] * $size;
            $usedGlyphs[$gid] ??= $this->utf16Hex($unicode);
        }
        $width += max(0, count($glyphs) - 1) * self::NAME_TRACKING;
        $x = ($center - $width / 2) * self::SCALE;
        $y = (self::IMAGE_HEIGHT - $baselineTop) * self::SCALE;
        return sprintf("BT /F5 %.5f Tf %.4f %.4f %.4f rg %.5f Tc 1 0 0 1 %.5f %.5f Tm <%s> Tj ET\n",
            $size * self::SCALE, .10, .12, .42, self::NAME_TRACKING * self::SCALE, $x, $y, implode('', $glyphs));
    }

    private function utf16Hex(int $codepoint): string
    {
        if ($codepoint <= 0xFFFF) return sprintf('%04X', $codepoint);
        $codepoint -= 0x10000;
        return sprintf('%04X%04X', 0xD800 + ($codepoint >> 10), 0xDC00 + ($codepoint & 0x3FF));
    }

    private function playfairFont(): array
    {
        if ($this->playfair !== null) return $this->playfair;
        $path = public_path('fonts/PlayfairDisplay-Bold.ttf');
        $bytes = @file_get_contents($path);
        if ($bytes === false) throw new RuntimeException('The embedded Playfair Display Bold font is unavailable.');
        $tables = [];
        $tableCount = $this->u16($bytes, 4);
        for ($i = 0; $i < $tableCount; $i++) {
            $record = 12 + $i * 16;
            $tag = substr($bytes, $record, 4);
            $tables[$tag] = ['offset' => $this->u32($bytes, $record + 8), 'length' => $this->u32($bytes, $record + 12)];
        }
        foreach (['head', 'hhea', 'maxp', 'hmtx', 'cmap'] as $tag) if (!isset($tables[$tag])) throw new RuntimeException('The Playfair Display Bold font is missing its '.$tag.' table.');
        $head = $tables['head']['offset'];
        $hhea = $tables['hhea']['offset'];
        $units = $this->u16($bytes, $head + 18);
        $numGlyphs = $this->u16($bytes, $tables['maxp']['offset'] + 4);
        $numMetrics = $this->u16($bytes, $hhea + 34);
        $hmtx = $tables['hmtx']['offset'];
        $widths = [];
        $lastWidth = 0;
        for ($gid = 0; $gid < $numGlyphs; $gid++) {
            if ($gid < $numMetrics) $lastWidth = $this->u16($bytes, $hmtx + $gid * 4);
            $widths[$gid] = $lastWidth;
        }
        $cmapTable = $tables['cmap']['offset'];
        $subtables = $this->u16($bytes, $cmapTable + 2);
        $format12 = $format4 = null;
        for ($i = 0; $i < $subtables; $i++) {
            $record = $cmapTable + 4 + $i * 8;
            $offset = $cmapTable + $this->u32($bytes, $record + 4);
            $format = $this->u16($bytes, $offset);
            $platform = $this->u16($bytes, $record);
            $encoding = $this->u16($bytes, $record + 2);
            if ($format === 12 && ($platform === 0 || ($platform === 3 && $encoding === 10))) $format12 = $offset;
            if ($format === 4 && ($platform === 0 || ($platform === 3 && $encoding === 1))) $format4 = $offset;
        }
        $cmap = [];
        if ($format12 !== null) {
            $groups = $this->u32($bytes, $format12 + 12);
            for ($i = 0; $i < $groups; $i++) {
                $group = $format12 + 16 + $i * 12;
                $start = $this->u32($bytes, $group);
                $end = $this->u32($bytes, $group + 4);
                $startGid = $this->u32($bytes, $group + 8);
                for ($code = $start; $code <= $end; $code++) $cmap[$code] = $startGid + $code - $start;
            }
        } elseif ($format4 !== null) {
            $segments = (int) ($this->u16($bytes, $format4 + 6) / 2);
            $endCodes = $format4 + 14;
            $startCodes = $endCodes + $segments * 2 + 2;
            $deltas = $startCodes + $segments * 2;
            $ranges = $deltas + $segments * 2;
            for ($i = 0; $i < $segments; $i++) {
                $start = $this->u16($bytes, $startCodes + $i * 2);
                $end = $this->u16($bytes, $endCodes + $i * 2);
                $delta = $this->s16($bytes, $deltas + $i * 2);
                $range = $this->u16($bytes, $ranges + $i * 2);
                if ($start === 0xFFFF) continue;
                for ($code = $start; $code <= $end; $code++) {
                    $gid = $range === 0 ? ($code + $delta) & 0xFFFF : $this->u16($bytes, $ranges + $i * 2 + $range + ($code - $start) * 2);
                    if ($range !== 0 && $gid !== 0) $gid = ($gid + $delta) & 0xFFFF;
                    if ($gid !== 0) $cmap[$code] = $gid;
                }
            }
        }
        if ($cmap === []) throw new RuntimeException('The Playfair Display Bold font has no supported Unicode cmap.');
        $os2 = $tables['OS/2']['offset'] ?? null;
        $capHeight = $os2 !== null && $tables['OS/2']['length'] >= 90 ? $this->s16($bytes, $os2 + 88) : $this->s16($bytes, $hhea + 4);
        $this->playfair = [
            'bytes' => $bytes, 'cmap' => $cmap, 'widths' => $widths, 'unitsPerEm' => $units,
            'defaultWidth' => $widths[0] ?? 600, 'numGlyphs' => $numGlyphs, 'capHeight' => $capHeight,
            'ascent' => $this->s16($bytes, $hhea + 4), 'descent' => $this->s16($bytes, $hhea + 6),
            'bbox' => [$this->s16($bytes, $head + 36), $this->s16($bytes, $head + 38), $this->s16($bytes, $head + 40), $this->s16($bytes, $head + 42)],
        ];
        return $this->playfair;
    }

    private function u16(string $bytes, int $offset): int { return unpack('nvalue', substr($bytes, $offset, 2))['value']; }
    private function s16(string $bytes, int $offset): int { $value = $this->u16($bytes, $offset); return $value > 32767 ? $value - 65536 : $value; }
    private function u32(string $bytes, int $offset): int { return unpack('Nvalue', substr($bytes, $offset, 4))['value']; }

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

    private function document(string $jpeg, string $content, array $font, array $usedGlyphs): string
    {
        ksort($usedGlyphs, SORT_NUMERIC);
        $cmapEntries = [];
        $widthEntries = [];
        foreach ($usedGlyphs as $gid => $unicodeHex) {
            $cmapEntries[] = sprintf('<%04X> <%s>', $gid, $unicodeHex);
            $glyphWidth = $font['widths'][$gid] ?? $font['defaultWidth'];
            $widthEntries[] = sprintf('%d [%d]', $gid, (int) round($glyphWidth * 1000 / $font['unitsPerEm']));
        }
        $toUnicode = "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def\n/CMapName /MANGNOPlayfairToUnicode def\n/CMapType 2 def\n1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n";
        foreach (array_chunk($cmapEntries, 100) as $chunk) {
            $toUnicode .= count($chunk)." beginbfchar\n".implode("\n", $chunk)."\nendbfchar\n";
        }
        $toUnicode .= "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
        $bbox = implode(' ', $font['bbox']);
        $ascent = (int) round($font['ascent'] * 1000 / $font['unitsPerEm']);
        $descent = (int) round($font['descent'] * 1000 / $font['unitsPerEm']);
        $capHeight = (int) round($font['capHeight'] * 1000 / $font['unitsPerEm']);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.6f %.6f] /Resources << /XObject << /Template 4 0 R >> /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R /F4 9 0 R /F5 14 0 R >> >> /Contents 8 0 R >>', self::WIDTH, self::HEIGHT),
            '<< /Type /XObject /Subtype /Image /Width 1999 /Height 1414 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>',
            '<< /Length '.strlen($content).">>\nstream\n".$content."endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-BoldOblique /Encoding /WinAnsiEncoding >>',
            '<< /Type /FontDescriptor /FontName /PlayfairDisplay-Bold /Flags 34 /FontBBox ['.$bbox.'] /ItalicAngle 0 /Ascent '.$ascent.' /Descent '.$descent.' /CapHeight '.$capHeight.' /StemV 120 /FontFile2 11 0 R >>',
            '<< /Length '.strlen($font['bytes']).' /Length1 '.strlen($font['bytes']).">>\nstream\n".$font['bytes']."\nendstream",
            '<< /Type /Font /Subtype /CIDFontType2 /BaseFont /PlayfairDisplay-Bold /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor 10 0 R /DW 1000 /W ['.implode(' ', $widthEntries).'] /CIDToGIDMap /Identity >>',
            '<< /Length '.strlen($toUnicode).">>\nstream\n".$toUnicode."\nendstream",
            '<< /Type /Font /Subtype /Type0 /BaseFont /PlayfairDisplay-Bold /Encoding /Identity-H /DescendantFonts [12 0 R] /ToUnicode 13 0 R >>',
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
