<?php

use Webkul\Core\Traits\PDFHandler;

/**
 * An object that renders PDFs through the shared handler trait.
 */
function pdfHandler(): object
{
    return new class
    {
        use PDFHandler;
    };
}

/**
 * Whether the given PDF bytes were produced by the mPDF (complex-script) engine.
 */
function isMpdfDocument(string $bytes): bool
{
    return stripos($bytes, 'mpdf') !== false;
}

/**
 * Whether the given PDF bytes embed a font whose name contains the marker.
 */
function embedsFont(string $bytes, string $marker): bool
{
    return stripos($bytes, $marker) !== false;
}

// ============================================================================
// Engine Routing
// ============================================================================

it('renders a latin-only document through dompdf', function () {
    expect(isMpdfDocument(pdfHandler()->generatePdf('<p>Urban Slate-28 168.96</p>')))->toBeFalse();
});

it('renders non-latin content through mpdf', function () {
    expect(isMpdfDocument(pdfHandler()->generatePdf('<p>'."\u{4f60}\u{597d}".'</p>')))->toBeTrue();
});

// ============================================================================
// Per-Script Fonts
// ============================================================================

it('embeds the matching font for each supported script', function (string $text, string $marker) {
    expect(embedsFont(pdfHandler()->generatePdf('<p>'.$text.'</p>'), $marker))->toBeTrue();
})->with([
    'arabic' => ["\u{0642}\u{0645}\u{064a}\u{0635}", 'XBRiyaz'],
    'hebrew' => ["\u{05e9}\u{05dc}\u{05d5}\u{05dd}", 'DejaVu'],
    'cyrillic' => ["\u{041f}\u{0440}\u{0438}\u{0432}\u{0435}\u{0442}", 'DejaVu'],
    'devanagari' => ["\u{0928}\u{092e}\u{0938}\u{094d}\u{0924}\u{0947}", 'Hind'],
    'bengali' => ["\u{0986}\u{09ae}\u{09bf}", 'NotoSansBengali'],
    'sinhala' => ["\u{0d86}\u{0dba}\u{0dd4}", 'NotoSansSinhala'],
    'chinese' => ["\u{4f60}\u{597d}", 'NotoSansSC'],
    'japanese' => ["\u{3053}\u{3093}\u{306b}\u{3061}\u{306f}", 'NotoSansJP'],
]);

// ============================================================================
// Mixed Scripts
// ============================================================================

it('embeds every needed font when one document mixes scripts', function () {
    $html = '<p>Invoice '."\u{4f60}\u{597d}".' '."\u{0928}\u{092e}\u{0938}\u{094d}\u{0924}\u{0947}".' '."\u{0642}\u{0645}\u{064a}\u{0635}".'</p>';

    $bytes = pdfHandler()->generatePdf($html);

    expect(embedsFont($bytes, 'NotoSansSC'))->toBeTrue()
        ->and(embedsFont($bytes, 'Hind'))->toBeTrue()
        ->and(embedsFont($bytes, 'XBRiyaz'))->toBeTrue();
});
