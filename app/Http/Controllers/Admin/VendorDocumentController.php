<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorDocumentController extends Controller
{
    public function show(Vendor $vendor, string $type): StreamedResponse
    {
        abort_unless(in_array($type, ['id', 'selfie'], true), 404);

        $path = $type === 'id' ? $vendor->id_document_path : $vendor->selfie_path;

        abort_unless($path, 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
