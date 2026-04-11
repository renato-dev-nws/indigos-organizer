<?php

namespace App\Http\Controllers;

use App\Models\SharedInfo;
use App\Models\SharedInfoDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class SharedInfoDocumentController extends Controller
{
    public function destroy(SharedInfo $sharedInfo, SharedInfoDocument $document): RedirectResponse
    {
        abort_unless($document->shared_info_id === $sharedInfo->id, 404);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Documento removido com sucesso.');
    }
}
