<?php

namespace Tests\Unit;

use App\Services\ImageClarityChecker;
use Tests\TestCase;

class ImageClarityCheckerTest extends TestCase
{
    private function blurredCopyOf(string $sourcePath): string
    {
        $image = imagecreatefromjpeg($sourcePath);

        for ($i = 0; $i < 15; $i++) {
            imagefilter($image, IMG_FILTER_GAUSSIAN_BLUR);
        }

        $destination = tempnam(sys_get_temp_dir(), 'blur_test').'.jpg';
        imagejpeg($image, $destination, 90);
        imagedestroy($image);

        return $destination;
    }

    public function test_a_sharp_real_photo_is_detected_as_clear(): void
    {
        $checker = new ImageClarityChecker;
        $path = database_path('seeders/images/products/samsung-galaxy.jpg');

        $this->assertTrue($checker->isClear($path));
        $this->assertGreaterThan(200, $checker->laplacianVariance($path));
    }

    public function test_a_heavily_blurred_photo_is_detected_as_unclear(): void
    {
        $checker = new ImageClarityChecker;
        $sharpPath = database_path('seeders/images/products/samsung-galaxy.jpg');
        $blurredPath = $this->blurredCopyOf($sharpPath);

        try {
            $this->assertFalse($checker->isClear($blurredPath));
            $this->assertLessThan(
                $checker->laplacianVariance($sharpPath),
                $checker->laplacianVariance($blurredPath)
            );
        } finally {
            @unlink($blurredPath);
        }
    }

    public function test_an_unreadable_file_is_treated_as_unclear_rather_than_erroring(): void
    {
        $checker = new ImageClarityChecker;
        $garbagePath = tempnam(sys_get_temp_dir(), 'not_an_image');
        file_put_contents($garbagePath, 'this is not image data');

        try {
            $this->assertFalse($checker->isClear($garbagePath));
        } finally {
            @unlink($garbagePath);
        }
    }
}
