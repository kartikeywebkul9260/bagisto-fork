<?php

namespace Webkul\Core\Traits;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

trait PDFHandler
{
    /**
     * Download PDF as a streamed response.
     */
    public function downloadPDF(string $html, ?string $fileName = null)
    {
        $fileName = $this->resolvePdfFileName($fileName);

        if ($this->requiresComplexEngine($html)) {
            $mpdf = $this->buildMpdf($this->preparePdfHtml($this->tagScriptRuns($html)));

            return response()->streamDownload(
                fn () => print ($mpdf->Output('', 'S')),
                $fileName.'.pdf'
            );
        }

        return Pdf::loadHTML($this->preparePdfHtml($html))
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', 'Courier')
            ->download($fileName.'.pdf');
    }

    /**
     * Generate pdf content.
     */
    public function generatePdf(string $html): string
    {
        if ($this->requiresComplexEngine($html)) {
            return $this->buildMpdf($this->preparePdfHtml($this->tagScriptRuns($html)))->Output('', 'S');
        }

        return Pdf::loadHTML($this->preparePdfHtml($html))
            ->setPaper('A4', 'portrait')
            ->setOption('defaultFont', 'Courier')
            ->output();
    }

    /**
     * Build and configure an mPDF instance with the given HTML.
     */
    private function buildMpdf(string $html): Mpdf
    {
        $defaultConfig = (new ConfigVariables)->getDefaults();

        $defaultFontConfig = (new FontVariables)->getDefaults();

        $mpdf = new Mpdf([
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'fontDir' => array_merge($defaultConfig['fontDir'], [public_path('fonts')]),
            'fontdata' => $defaultFontConfig['fontdata'] + $this->bundledScriptFonts(),
        ]);

        if ($this->isRtlLocale()) {
            $mpdf->SetDirectionality('rtl');
        }

        $mpdf->SetDisplayMode('fullpage');

        $mpdf->WriteHTML($html);

        return $mpdf;
    }

    /**
     * The fonts shipped in public/fonts for the scripts mPDF does not bundle.
     */
    private function bundledScriptFonts(): array
    {
        return [
            'notosanssc' => ['R' => 'NotoSansSC-Regular.ttf', 'B' => 'NotoSansSC-Bold.ttf'],
            'notosansjp' => ['R' => 'NotoSansJP-Regular.ttf', 'B' => 'NotoSansJP-Bold.ttf'],
            'hind' => ['R' => 'Hind-Regular.ttf', 'B' => 'Hind-Bold.ttf'],
            'notosansbengali' => ['R' => 'NotoSansBengali-Regular.ttf', 'B' => 'NotoSansBengali-Bold.ttf'],
            'notosanssinhala' => ['R' => 'NotoSansSinhala-Regular.ttf', 'B' => 'NotoSansSinhala-Bold.ttf'],
        ];
    }

    /**
     * Prepare HTML for PDF rendering: encode and adjust Arabic/Persian glyphs.
     */
    private function preparePdfHtml(string $html): string
    {
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        return $this->adjustArabicAndPersianContent($html);
    }

    /**
     * Determine if the current locale uses RTL direction.
     */
    private function isRtlLocale(): bool
    {
        return core()->getCurrentLocale()->direction === 'rtl';
    }

    /**
     * Determine whether the HTML needs mPDF, by RTL locale or by any non-Latin script.
     */
    private function requiresComplexEngine(string $html): bool
    {
        return $this->isRtlLocale()
            || (bool) preg_match('/[\p{Arabic}\p{Hebrew}\p{Cyrillic}\p{Greek}\p{Thai}\p{Devanagari}\p{Bengali}\p{Sinhala}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $html);
    }

    /**
     * Wrap each non-Latin text run in a span naming the font that covers its script.
     */
    private function tagScriptRuns(string $html): string
    {
        $segments = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $inRawBlock = false;

        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if ($segment[0] === '<') {
                $inRawBlock = $this->tracksRawBlock($segment, $inRawBlock);

                continue;
            }

            if (! $inRawBlock) {
                $segments[$index] = $this->wrapScriptRuns($segment);
            }
        }

        return implode('', $segments);
    }

    /**
     * Track whether the parser sits inside a style or script block after the given tag.
     */
    private function tracksRawBlock(string $tag, bool $current): bool
    {
        if (preg_match('/^<\s*(style|script)[\s>]/i', $tag)) {
            return true;
        }

        if (preg_match('/^<\s*\/\s*(style|script)\s*>/i', $tag)) {
            return false;
        }

        return $current;
    }

    /**
     * Wrap every non-Latin script run in the given text with its font-family span.
     */
    private function wrapScriptRuns(string $text): string
    {
        return preg_replace_callback(
            '/(?:[\p{Arabic}\p{Hebrew}\p{Cyrillic}\p{Greek}\p{Thai}\p{Devanagari}\p{Bengali}\p{Sinhala}\p{Han}\p{Hiragana}\p{Katakana}]|\p{M})+/u',
            fn (array $matches) => '<span style="font-family: '.$this->fontForRun($matches[0]).'">'.$matches[0].'</span>',
            $text
        );
    }

    /**
     * Pick the bundled or mPDF font that covers the given script run.
     */
    private function fontForRun(string $run): string
    {
        return match (true) {
            (bool) preg_match('/\p{Arabic}/u', $run) => 'xbriyaz',
            (bool) preg_match('/\p{Devanagari}/u', $run) => 'hind',
            (bool) preg_match('/\p{Bengali}/u', $run) => 'notosansbengali',
            (bool) preg_match('/\p{Sinhala}/u', $run) => 'notosanssinhala',
            (bool) preg_match('/[\p{Hiragana}\p{Katakana}]/u', $run) => 'notosansjp',
            (bool) preg_match('/\p{Han}/u', $run) => 'notosanssc',
            default => 'dejavusans',
        };
    }

    /**
     * Resolve a PDF file name, generating a random one if not provided.
     */
    private function resolvePdfFileName(?string $fileName): string
    {
        return $fileName ?? Str::random(32);
    }

    /**
     * Adjust Arabic and Persian glyph rendering for PDF output.
     */
    private function adjustArabicAndPersianContent(string $html): string
    {
        $arabic = new Arabic;

        $positions = $arabic->arIdentify($html);

        for ($i = count($positions) - 1; $i >= 0; $i -= 2) {
            $segment = substr($html, $positions[$i - 1], $positions[$i] - $positions[$i - 1]);

            $converted = $arabic->utf8Glyphs($segment);

            $html = substr_replace($html, $converted, $positions[$i - 1], $positions[$i] - $positions[$i - 1]);
        }

        return $html;
    }
}
