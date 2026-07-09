<?php

if (! function_exists('naira')) {
    function naira(int $kobo): string
    {
        return '₦'.number_format($kobo / 100);
    }
}
