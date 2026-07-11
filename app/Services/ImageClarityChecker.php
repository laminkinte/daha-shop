<?php

namespace App\Services;

/**
 * Detects whether a captured/uploaded photo is too blurry to be usable for
 * identity verification, using the variance-of-Laplacian technique: a sharp
 * image has strong edges (high variance in the second-derivative response),
 * a blurry one is smooth (low variance). This is a real, well-established
 * blur metric (the same one OpenCV's cv2.Laplacian().var() implements) -
 * not a placeholder.
 */
class ImageClarityChecker
{
    private const BLUR_VARIANCE_THRESHOLD = 60.0;

    private const MAX_ANALYSIS_DIMENSION = 300;

    public function isClear(string $absolutePath): bool
    {
        return $this->laplacianVariance($absolutePath) >= self::BLUR_VARIANCE_THRESHOLD;
    }

    public function laplacianVariance(string $absolutePath): float
    {
        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            return 0.0;
        }

        $source = @imagecreatefromstring($contents);

        if (! $source) {
            return 0.0;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_ANALYSIS_DIMENSION / max($width, $height));
        $targetWidth = max(3, (int) round($width * $scale));
        $targetHeight = max(3, (int) round($height * $scale));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        imagefilter($resized, IMG_FILTER_GRAYSCALE);
        imagedestroy($source);

        $responses = [];

        for ($y = 1; $y < $targetHeight - 1; $y++) {
            for ($x = 1; $x < $targetWidth - 1; $x++) {
                $center = imagecolorat($resized, $x, $y) & 0xFF;
                $top = imagecolorat($resized, $x, $y - 1) & 0xFF;
                $bottom = imagecolorat($resized, $x, $y + 1) & 0xFF;
                $left = imagecolorat($resized, $x - 1, $y) & 0xFF;
                $right = imagecolorat($resized, $x + 1, $y) & 0xFF;

                $responses[] = ($top + $bottom + $left + $right) - (4 * $center);
            }
        }

        imagedestroy($resized);

        if (empty($responses)) {
            return 0.0;
        }

        $mean = array_sum($responses) / count($responses);
        $sumSquaredDeviation = array_sum(array_map(
            static fn (int $value) => ($value - $mean) ** 2,
            $responses
        ));

        return $sumSquaredDeviation / count($responses);
    }
}
